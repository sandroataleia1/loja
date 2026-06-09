<?php

declare(strict_types=1);

namespace App\Modules\Sync\Actions;

use App\Core\Auth\Services\TenantAuthorizationService;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Variant;
use App\Modules\Customers\Models\Customer;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Sync\DTOs\SyncPullDTO;
use App\Modules\Sync\Enums\SyncEntityTypeEnum;
use App\Modules\Sync\Models\SyncDevice;
use App\Modules\Sync\Models\SyncLog;
use App\Modules\Sync\Models\SyncPullCheckpoint;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Processa pull incremental do PDV.
 *
 * Para cada entity_type solicitado:
 * 1. Resolve checkpoint (last_synced_at) do device × entity_type
 * 2. Queries entidades ATIVAS modificadas após o checkpoint
 * 3. Queries entidades DELETADAS (tombstones) para remoção no SQLite local
 * 4. Avança o checkpoint somente após gravar o log (ack-pending)
 *
 * O PDV deve confirmar o recebimento via POST /sync/pull/ack para que
 * o checkpoint seja oficialmente avançado. O campo pulled_at no response
 * é o valor a enviar no ack.
 *
 * Estrutura do response por entity_type:
 * { entities: [...], tombstones: [{ uuid, deleted_at }, ...] }
 */
final readonly class SyncPullAction
{
    /**
     * @return array{
     *   log: SyncLog,
     *   pulled_at: string,
     *   data: array<string, array{entities: array, tombstones: array}>,
     *   checkpoints: array<string, string>
     * }
     */
    public function __construct(private readonly TenantAuthorizationService $authService) {}

    public function execute(SyncPullDTO $dto, Request $request): array
    {
        $tenantId  = TenantContext::getIdOrFail();
        $startedAt = microtime(true);

        $device = $this->resolveDevice($dto->deviceUuid, $tenantId);
        $device->markSeen();

        $log = SyncLog::create([
            'tenant_id'       => $tenantId,
            'device_id'       => $device->uuid,
            'batch_id'        => $dto->batchId,
            'direction'       => 'pull',
            'operation_count' => count($dto->entityTypes),
            'synced_count'    => 0,
            'failed_count'    => 0,
            'conflict_count'  => 0,
            'request_summary' => [
                'entity_types' => array_map(fn ($t) => $t->value, $dto->entityTypes),
                'checkpoints'  => $dto->checkpoints,
            ],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        $data           = [];
        $newCheckpoints = [];
        $totalSynced    = 0;
        $pulledAt       = now();

        foreach ($dto->entityTypes as $entityType) {
            $since = $this->resolveCheckpoint($dto, $device, $entityType);

            ['entities' => $entities, 'tombstones' => $tombstones] =
                $this->pullEntities($entityType, $device, $tenantId, $since);

            $data[$entityType->value] = [
                'entities'   => $entities,
                'tombstones' => $tombstones,
            ];

            $totalSynced += count($entities) + count($tombstones);
            $newCheckpoints[$entityType->value] = $pulledAt->toIso8601String();

            // Checkpoint avançado otimisticamente mas registrado como "pending ack"
            // para que o ack endpoint possa confirmar ou o cron pode limpar órfãos
            $this->advanceCheckpoint($tenantId, $device, $entityType, $pulledAt);
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        $log->update([
            'synced_count'     => $totalSynced,
            'response_summary' => ['entity_counts' => array_map(
                fn ($d) => ['entities' => count($d['entities']), 'tombstones' => count($d['tombstones'])],
                $data,
            )],
            'completed_at' => now(),
            'duration_ms'  => $durationMs,
        ]);

        // Inclui permissões do usuário no response para cache offline no PDV
        $userId      = $request->user()?->uuid;
        $userContext = $userId
            ? $this->authService->buildPermissionSet($userId, $tenantId)?->toArray()
            : null;

        return [
            'log'          => $log,
            'pulled_at'    => $pulledAt->toIso8601String(),
            'data'         => $data,
            'checkpoints'  => $newCheckpoints,
            'user_context' => $userContext,
        ];
    }

    /** Resolve o checkpoint: DTO override → DB checkpoint → epoch. */
    private function resolveCheckpoint(SyncPullDTO $dto, SyncDevice $device, SyncEntityTypeEnum $entityType): Carbon
    {
        $raw = $dto->checkpoints[$entityType->value] ?? null;

        if ($raw !== null) {
            return Carbon::parse($raw);
        }

        $cp = SyncPullCheckpoint::where('tenant_id', TenantContext::getIdOrFail())
            ->where('device_id', $device->uuid)
            ->where('entity_type', $entityType)
            ->first();

        return $cp?->last_synced_at ?? Carbon::createFromTimestamp(0);
    }

    /**
     * @return array{
     *   entities: array<int, array<string, mixed>>,
     *   tombstones: array<int, array{uuid: string, deleted_at: string}>
     * }
     */
    private function pullEntities(
        SyncEntityTypeEnum $entityType,
        SyncDevice         $device,
        string             $tenantId,
        Carbon             $since,
    ): array {
        return match ($entityType) {
            SyncEntityTypeEnum::Product        => $this->pullProducts($tenantId, $since),
            SyncEntityTypeEnum::ProductVariant => $this->pullVariants($tenantId, $since),
            SyncEntityTypeEnum::Price          => $this->pullPrices($tenantId, $since),
            SyncEntityTypeEnum::Inventory      => $this->pullInventory($tenantId, $device->store_id, $since),
            SyncEntityTypeEnum::Customer       => $this->pullCustomers($tenantId, $since),
            default                            => ['entities' => [], 'tombstones' => []],
        };
    }

    /**
     * @return array{entities: array, tombstones: array}
     */
    private function pullProducts(string $tenantId, Carbon $since): array
    {
        $rows = Product::where('tenant_id', $tenantId)
            ->where('updated_at', '>=', $since)
            // Categorias são N:N (ADR-006) — não existe products.category_id.
            ->select(['uuid', 'name', 'description', 'status', 'brand_id', 'is_publishable', 'updated_at', 'deleted_at'])
            ->withTrashed()
            ->get();

        return $this->splitEntitiesAndTombstones($rows);
    }

    /**
     * @return array{entities: array, tombstones: array}
     */
    private function pullVariants(string $tenantId, Carbon $since): array
    {
        $rows = Variant::where('tenant_id', $tenantId)
            ->where('updated_at', '>=', $since)
            // catalog_variants usa is_active (não status).
            ->select(['uuid', 'product_id', 'sku', 'name', 'price_cents', 'is_active', 'updated_at', 'deleted_at'])
            ->withTrashed()
            ->get();

        return $this->splitEntitiesAndTombstones($rows);
    }

    /**
     * Price = variant prices (subconjunto do pull de variantes).
     * @return array{entities: array, tombstones: array}
     */
    private function pullPrices(string $tenantId, Carbon $since): array
    {
        $rows = Variant::where('tenant_id', $tenantId)
            ->where('updated_at', '>=', $since)
            ->select(['uuid', 'product_id', 'sku', 'price_cents', 'updated_at', 'deleted_at'])
            ->withTrashed()
            ->get();

        return $this->splitEntitiesAndTombstones($rows);
    }

    /**
     * Inventory é sempre scoped pela loja do device.
     * @return array{entities: array, tombstones: array}
     */
    private function pullInventory(string $tenantId, string $storeId, Carbon $since): array
    {
        // InventoryBalance sem SoftDeletes — não há tombstones
        $entities = InventoryBalance::where('tenant_id', $tenantId)
            ->where('store_id', $storeId)
            ->where('updated_at', '>=', $since)
            ->select(['uuid', 'variant_id', 'store_id', 'quantity', 'reserved_quantity', 'updated_at'])
            ->get()
            ->toArray();

        return ['entities' => $entities, 'tombstones' => []];
    }

    /**
     * Customers: PDV recebe atualizações feitas no backend (ex: CRM).
     * @return array{entities: array, tombstones: array}
     */
    private function pullCustomers(string $tenantId, Carbon $since): array
    {
        $rows = Customer::where('tenant_id', $tenantId)
            ->where('updated_at', '>=', $since)
            ->where('is_default_consumer', false) // Consumidor padrão não é sincronizado
            ->select(['uuid', 'name', 'email', 'phone', 'document', 'updated_at', 'deleted_at'])
            ->withTrashed()
            ->get();

        return $this->splitEntitiesAndTombstones($rows);
    }

    /**
     * Separa coleção em entidades ativas e tombstones.
     * Tombstones contêm apenas uuid + deleted_at para o PDV remover do SQLite.
     *
     * @param  \Illuminate\Database\Eloquent\Collection $rows
     * @return array{entities: array, tombstones: array<int, array{uuid: string, deleted_at: string}>}
     */
    private function splitEntitiesAndTombstones($rows): array
    {
        $entities   = [];
        $tombstones = [];

        foreach ($rows as $row) {
            if ($row->deleted_at !== null) {
                $tombstones[] = [
                    'uuid'       => $row->uuid,
                    'deleted_at' => $row->deleted_at->toIso8601String(),
                ];
            } else {
                $entities[] = $row->toArray();
            }
        }

        return ['entities' => $entities, 'tombstones' => $tombstones];
    }

    private function advanceCheckpoint(
        string             $tenantId,
        SyncDevice         $device,
        SyncEntityTypeEnum $entityType,
        Carbon             $to,
    ): void {
        // tenant_id explícito na busca para evitar ambiguidade cross-tenant
        SyncPullCheckpoint::where('tenant_id', $tenantId)
            ->updateOrCreate(
                ['device_id' => $device->uuid, 'entity_type' => $entityType],
                ['tenant_id' => $tenantId, 'last_synced_at' => $to, 'updated_at' => now()],
            );
    }

    private function resolveDevice(string $deviceUuid, string $tenantId): SyncDevice
    {
        $device = SyncDevice::where('tenant_id', $tenantId)
            ->where('device_uuid', $deviceUuid)
            ->where('is_active', true)
            ->first();

        if ($device === null) {
            throw new UnprocessableEntityHttpException(
                "Dispositivo '{$deviceUuid}' não registrado ou inativo neste tenant."
            );
        }

        return $device;
    }
}
