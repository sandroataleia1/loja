<?php

declare(strict_types=1);

namespace App\Core\Auth\Actions;

use App\Core\Auth\DTOs\RegisterDTO;
use App\Core\Auth\Events\UserRegistered;
use App\Core\Auth\Models\User;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Inventory\Actions\CreateDefaultStoreAction;
use App\Modules\Inventory\Models\Store;
use App\Modules\Omnichannel\Actions\CreateDefaultChannelAction;
use App\Modules\Omnichannel\Models\Channel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fluxo completo de registro de nova empresa.
 *
 * Executado dentro de uma única transação:
 *  1. Cria Tenant (→ TenantCreated → CreateDefaultConsumer sincronamente)
 *  2. Cria User administrador
 *  3. Bootstrap RBAC: copia roles de sistema + atribui OWNER ao admin
 *  4. Cria Loja Matriz (→ StoreCreated)
 *  5. Cria Canal PDV Principal (→ ChannelCreated)
 *  6. Emite UserRegistered
 *  7. Gera token Sanctum
 */
final readonly class RegisterAction
{
    public function __construct(
        private CreateDefaultStoreAction   $createStore,
        private CreateDefaultChannelAction $createChannel,
        private BootstrapTenantAction      $bootstrapTenant,
    ) {}

    public function execute(RegisterDTO $dto): array
    {
        $result = DB::transaction(function () use ($dto): array {

            // ── 1. Tenant ──────────────────────────────────────────────────────
            $code = $this->generateTenantCode();
            // Deterministic slug: base slug + tenant code suffix (e.g. "minha-loja-0001")
            $slug = Str::slug($dto->tenantName) . '-' . strtolower(substr($code, 3));

            /** @var Tenant $tenant */
            $tenant = Tenant::create([
                'code'       => $code,
                'trade_name' => $dto->tenantName,
                'legal_name' => $dto->legalName,
                'document'   => $dto->document,
                'email'      => $dto->email,
                'phone'      => $dto->phone,
                'name'       => $dto->tenantName,   // legacy field used by existing code
                'slug'       => $slug,
                'is_active'  => true,
            ]);

            // ── 2. User ────────────────────────────────────────────────────────
            /** @var User $user */
            $user = User::create([
                'tenant_id'         => $tenant->uuid,
                'name'              => $dto->name,
                'email'             => $dto->email,
                'password'          => $dto->password,
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);

            // Estabelece o contexto do tenant recém-criado para as operações
            // escopadas seguintes (membership, loja, canal). O registro não passa
            // pelo middleware ResolveTenant, então sem isto o TenantScope (fail
            // closed) lançaria TenantContextMissingException.
            return TenantContext::runFor($tenant->uuid, function () use ($tenant, $user, $dto): array {
                // ── 3. Bootstrap RBAC (roles + OWNER membership) ──────────────
                $this->bootstrapTenant->execute($tenant->uuid, $user);

                // ── 4. Loja Matriz ─────────────────────────────────────────────
                /** @var Store $store */
                $store = $this->createStore->execute($tenant->uuid);

                // ── 5. Canal PDV ───────────────────────────────────────────────
                /** @var Channel $channel */
                $channel = $this->createChannel->execute($tenant->uuid, $store);

                // ── 6. Events ──────────────────────────────────────────────────
                UserRegistered::dispatch($user, $tenant);

                // ── 7. Token ───────────────────────────────────────────────────
                $token = $user->createToken($dto->deviceName)->plainTextToken;

                return compact('token', 'user', 'tenant', 'store', 'channel');
            });
        });

        return $result;
    }

    /**
     * Gera código de tenant com lock de transação (advisory lock) para evitar colisão.
     * lockForUpdate() em COUNT(*) não é suportado pelo PostgreSQL.
     */
    private function generateTenantCode(): string
    {
        // Advisory lock: garante que apenas uma transação por vez gera o próximo código
        DB::statement('SELECT pg_advisory_xact_lock(1)');
        $count = DB::table('tenants')->count();

        return 'TEN' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
