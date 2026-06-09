<?php

declare(strict_types=1);

namespace App\Modules\Sync\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Customers\Actions\CreateCustomerAction;
use App\Modules\Customers\DTOs\CreateCustomerDTO;
use App\Modules\Customers\Models\Customer;
use App\Modules\Sales\Actions\AddPaymentAction;
use App\Modules\Sales\Actions\CreateSaleAction;
use App\Modules\Sales\Actions\CreateSupplyAction;
use App\Modules\Sales\Actions\CreateWithdrawalAction;
use App\Modules\Sales\DTOs\AddPaymentDTO;
use App\Modules\Sales\DTOs\CreateMovementDTO;
use App\Modules\Sales\DTOs\CreateSaleDTO;
use App\Modules\Sales\DTOs\CreateSaleItemDTO;
use App\Modules\Sales\Enums\CashMovementTypeEnum;
use App\Modules\Sales\Enums\PaymentMethodEnum;
use App\Modules\Sales\Enums\SalesChannelEnum;
use App\Modules\Sales\Models\CashMovement;
use App\Modules\Sales\Models\CashRegisterSession;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sync\DTOs\SyncOperationItemDTO;
use App\Modules\Sync\Enums\SyncEntityTypeEnum;
use App\Modules\Sync\Enums\SyncOperationStatusEnum;
use App\Modules\Sync\Enums\SyncOperationTypeEnum;
use App\Modules\Sync\Events\SyncConflictDetected;
use App\Modules\Sync\Events\SyncOperationProcessed;
use App\Modules\Sync\Models\SyncDevice;
use App\Modules\Sync\Models\SyncOperation;
use Illuminate\Support\Facades\Log;

/**
 * Processa uma operação de sincronização do PDV.
 *
 * Ownership-based routing:
 * - PDV-owned (Sale, Payment, CashMovement, Customer) → aceito via Actions corretas
 * - Backend-owned (Product, Variant, Price, Inventory)  → CONFLICT
 *
 * Todos os handlers delegam para Actions existentes para garantir validação
 * de negócio, geração de código interno e disparo de eventos.
 */
final readonly class ProcessSyncOperationAction
{
    public function __construct(
        private CreateSaleAction      $createSaleAction,
        private AddPaymentAction      $addPaymentAction,
        private CreateSupplyAction    $createSupplyAction,
        private CreateWithdrawalAction $createWithdrawalAction,
        private CreateCustomerAction  $createCustomerAction,
    ) {}

    /**
     * @return array{status: SyncOperationStatusEnum, entity_uuid: ?string}
     */
    public function execute(SyncOperationItemDTO $dto, SyncDevice $device, string $batchId): array
    {
        $tenantId = TenantContext::getIdOrFail();

        // ── Idempotência: operação já processada em estado terminal? ──────────
        $existing = SyncOperation::where('tenant_id', $tenantId)
            ->where('idempotency_key', $dto->idempotencyKey)
            ->first();

        if ($existing !== null && $existing->isTerminal()) {
            SyncOperationProcessed::dispatch($existing, $existing->status);
            return ['status' => $existing->status, 'entity_uuid' => $existing->entity_uuid];
        }

        // ── Persist a operação com batch_id já correto ────────────────────────
        $operation = $existing ?? SyncOperation::create([
            'operation_uuid'  => $dto->operationUuid,
            'tenant_id'       => $tenantId,
            'store_id'        => $device->store_id,
            'device_id'       => $device->uuid,
            'entity_type'     => $dto->entityType,
            'entity_uuid'     => $dto->entityUuid,
            'operation_type'  => $dto->operationType,
            'batch_id'        => $batchId,
            'payload'         => $dto->payload,
            'status'          => SyncOperationStatusEnum::Processing,
            'idempotency_key' => $dto->idempotencyKey,
            'retry_count'     => 0,
            'created_at'      => $dto->createdAt,
            'received_at'     => now(),
        ]);

        // ── Ownership check ───────────────────────────────────────────────────
        if (!$dto->entityType->isPdvOwned()) {
            $operation->transitionTo(SyncOperationStatusEnum::Conflict);
            SyncConflictDetected::dispatch($operation, 'backend_owned_entity');
            SyncOperationProcessed::dispatch($operation, SyncOperationStatusEnum::Conflict);
            return ['status' => SyncOperationStatusEnum::Conflict, 'entity_uuid' => null];
        }

        // ── Route to entity handler ───────────────────────────────────────────
        try {
            $entityUuid = $this->route($dto, $device);
            $operation->transitionTo(SyncOperationStatusEnum::Synced);
            SyncOperationProcessed::dispatch($operation, SyncOperationStatusEnum::Synced, $entityUuid);
            return ['status' => SyncOperationStatusEnum::Synced, 'entity_uuid' => $entityUuid];
        } catch (\Throwable $e) {
            Log::warning('sync.operation.failed', [
                'operation_uuid'  => $dto->operationUuid,
                'entity_type'     => $dto->entityType->value,
                'idempotency_key' => $dto->idempotencyKey,
                'error'           => $e->getMessage(),
            ]);

            $operation->transitionTo(SyncOperationStatusEnum::Failed, $e->getMessage());
            SyncOperationProcessed::dispatch($operation, SyncOperationStatusEnum::Failed);
            return ['status' => SyncOperationStatusEnum::Failed, 'entity_uuid' => null];
        }
    }

    private function route(SyncOperationItemDTO $dto, SyncDevice $device): ?string
    {
        return match ($dto->entityType) {
            SyncEntityTypeEnum::Sale               => $this->handleSale($dto, $device),
            SyncEntityTypeEnum::PaymentTransaction  => $this->handlePaymentTransaction($dto),
            SyncEntityTypeEnum::CashMovement        => $this->handleCashMovement($dto, $device),
            SyncEntityTypeEnum::Customer            => $this->handleCustomer($dto),
            default                                 => null,
        };
    }

    private function handleSale(SyncOperationItemDTO $dto, SyncDevice $device): ?string
    {
        $payload = $dto->payload;

        if ($dto->operationType === SyncOperationTypeEnum::Delete) {
            Sale::where('sync_uuid', $dto->entityUuid)->delete();
            return $dto->entityUuid;
        }

        // Create e Update ambos passam pelo CreateSaleAction — idempotência via sync_uuid
        $saleDto = new CreateSaleDTO(
            storeId:      $device->store_id,
            sessionId:    $payload['session_id'] ?? null,
            customerId:   $payload['customer_id'] ?? null,
            sellerId:     $payload['seller_id'] ?? null,
            items:        array_map(
                fn (array $i) => CreateSaleItemDTO::fromArray($i),
                $payload['items'] ?? [],
            ),
            syncUuid:     $dto->entityUuid,
            notes:        $payload['notes'] ?? null,
            metadata:     $payload['metadata'] ?? null,
            salesChannel: SalesChannelEnum::tryFrom($payload['sales_channel'] ?? '') ?? SalesChannelEnum::Pdv,
        );

        $sale = $this->createSaleAction->execute($saleDto);
        return $sale->uuid;
    }

    private function handlePaymentTransaction(SyncOperationItemDTO $dto): ?string
    {
        $payload = $dto->payload;

        if ($dto->operationType === SyncOperationTypeEnum::Delete) {
            // Pagamentos não são deletados — ignorar silenciosamente
            return null;
        }

        // Idempotência: se já existe, retornar
        $existingByUuid = \App\Modules\Sales\Models\PaymentTransaction::where('uuid', $dto->entityUuid)->first();
        if ($existingByUuid !== null) {
            return $existingByUuid->uuid;
        }

        $sale = Sale::where('uuid', $payload['sale_id'] ?? '')
            ->orWhere('sync_uuid', $payload['sale_sync_uuid'] ?? '')
            ->first();

        if ($sale === null) {
            throw new \RuntimeException(
                "Venda não encontrada para pagamento sync (sale_id={$payload['sale_id']}). "
                . 'Processe a operação de Sale antes do Payment.'
            );
        }

        $addPaymentDto = new AddPaymentDTO(
            method:            PaymentMethodEnum::from($payload['method']),
            amountCents:       (int) $payload['amount_cents'],
            externalReference: $payload['external_reference'] ?? null,
            notes:             $payload['notes'] ?? null,
            metadata:          $payload['metadata'] ?? null,
        );

        $payment = $this->addPaymentAction->execute($sale, $addPaymentDto);
        return $payment->uuid;
    }

    private function handleCashMovement(SyncOperationItemDTO $dto, SyncDevice $device): ?string
    {
        $payload = $dto->payload;

        if ($dto->operationType === SyncOperationTypeEnum::Delete) {
            return null; // Movimentos de caixa são imutáveis
        }

        // Idempotência: se já existe por UUID, retornar
        $existing = CashMovement::where('uuid', $dto->entityUuid)->first();
        if ($existing !== null) {
            return $existing->uuid;
        }

        $session = CashRegisterSession::where('uuid', $payload['cash_register_session_id'] ?? '')
            ->orWhere('store_id', $device->store_id)
            ->where('status', 'open')
            ->first();

        if ($session === null) {
            throw new \RuntimeException('Sessão de caixa aberta não encontrada para movimentação.');
        }

        $movementDto = new CreateMovementDTO(
            amountCents: (int) abs($payload['amount_cents']),
            description: $payload['description'] ?? null,
        );

        $type = CashMovementTypeEnum::tryFrom($payload['type'] ?? '');

        $movement = match ($type) {
            CashMovementTypeEnum::Supply     => $this->createSupplyAction->execute($session, $movementDto),
            CashMovementTypeEnum::Withdrawal => $this->createWithdrawalAction->execute($session, $movementDto),
            default                          => throw new \RuntimeException("Tipo de movimentação inválido: {$payload['type']}"),
        };

        return $movement->uuid;
    }

    private function handleCustomer(SyncOperationItemDTO $dto): ?string
    {
        $payload = $dto->payload;

        // Idempotência: customer já existe por UUID (criado anteriormente via sync)
        $existing = Customer::withoutGlobalScopes()
            ->where('uuid', $dto->entityUuid)
            ->where('tenant_id', TenantContext::getIdOrFail())
            ->first();

        if ($dto->operationType === SyncOperationTypeEnum::Delete) {
            $existing?->delete();
            return $dto->entityUuid;
        }

        if ($existing !== null) {
            if ($dto->operationType === SyncOperationTypeEnum::Update) {
                // Apenas atualizar campos seguros — não sobrescrever com payload bruto
                $existing->update(array_filter([
                    'name'    => $payload['name'] ?? null,
                    'email'   => $payload['email'] ?? null,
                    'phone'   => $payload['phone'] ?? null,
                    'notes'   => $payload['notes'] ?? null,
                    'address' => $payload['address'] ?? null,
                ]));
            }
            return $existing->uuid;
        }

        // Criar via Action para garantir código interno e validações
        $customerDto = new CreateCustomerDTO(
            name:    $payload['name'] ?? 'Cliente PDV',
            cpf:     $payload['cpf'] ?? null,
            cnpj:    $payload['cnpj'] ?? null,
            email:   $payload['email'] ?? null,
            phone:   $payload['phone'] ?? null,
            notes:   $payload['notes'] ?? null,
            address: $payload['address'] ?? null,
        );

        $customer = $this->createCustomerAction->execute($customerDto);

        // Preservar o UUID enviado pelo PDV para rastreabilidade futura
        $customer->update(['uuid' => $dto->entityUuid]);

        return $customer->uuid;
    }
}
