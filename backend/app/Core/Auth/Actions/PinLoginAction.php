<?php

declare(strict_types=1);

namespace App\Core\Auth\Actions;

use App\Core\Audit\DTOs\AuditLogDTO;
use App\Core\Audit\Enums\AuditActionEnum;
use App\Core\Audit\Enums\AuditEntityTypeEnum;
use App\Core\Audit\Services\AuditLogger;
use App\Core\Auth\Models\TenantUser;
use App\Core\Auth\Models\User;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Http\Request;

/**
 * Autentica um usuário pelo PIN numérico para acesso rápido no PDV.
 *
 * O PIN identifica e autentica o usuário dentro do tenant.
 * A busca é segura: o hash é calculado antes de qualquer query,
 * e a comparação é feita no banco contra o campo pin já hashed.
 *
 * Throttle: 10 tentativas / 5 minutos por [tenant_id + IP] — configurado
 * no RouteServiceProvider com o nome 'pin-login'.
 */
final readonly class PinLoginAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private Request     $request,
    ) {}

    public function execute(string $tenantId, string $pin, string $deviceName): array
    {
        $tenant = Tenant::where('uuid', $tenantId)
            ->where('is_active', true)
            ->firstOrFail();

        TenantContext::set($tenant->uuid);

        // Hash do PIN antes de qualquer query (SHA-256 com pepper = APP_KEY)
        $hashedPin = hash('sha256', config('app.key', '') . $pin);

        // Busca usuário ativo no tenant que possua o PIN
        $user = User::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNotNull('pin')
            ->where('pin', $hashedPin)
            ->first();

        if ($user === null) {
            $this->auditPinFailed($tenantId);
            throw new BusinessException('PIN inválido.');
        }

        // Conta bloqueada (mesmo mecanismo do login por senha)
        if ($user->locked_until !== null && $user->locked_until->isFuture()) {
            $until = $user->locked_until->format('H:i');
            throw new BusinessException(
                "Conta bloqueada até {$until}. Tente novamente mais tarde."
            );
        }

        // Verifica membership ativa no tenant
        $membership = TenantUser::where('tenant_id', $tenantId)
            ->where('user_id', $user->uuid)
            ->where('is_active', true)
            ->first();

        if ($membership === null) {
            $this->auditPinFailed($tenantId);
            throw new BusinessException('PIN inválido.');
        }

        // Login bem-sucedido
        $user->tokens()->where('name', $deviceName)->delete();
        $token = $user->createToken($deviceName)->plainTextToken;

        $user->update([
            'last_login_at'      => now(),
            'failed_login_count' => 0,
            'locked_until'       => null,
        ]);

        $this->auditLogger->record(new AuditLogDTO(
            entityType: AuditEntityTypeEnum::User,
            entityUuid: $user->uuid,
            action:     AuditActionEnum::AuthPinLogin,
            tenantId:   $tenantId,
            userId:     $user->uuid,
            metadata:   ['device_name' => $deviceName],
            ip:         $this->request->ip(),
            userAgent:  $this->request->userAgent(),
        ));

        return [
            'token' => $token,
            'user'  => $user,
        ];
    }

    private function auditPinFailed(string $tenantId): void
    {
        $this->auditLogger->record(new AuditLogDTO(
            entityType: AuditEntityTypeEnum::User,
            entityUuid: '00000000-0000-0000-0000-000000000000',
            action:     AuditActionEnum::AuthPinFailed,
            tenantId:   $tenantId,
            metadata:   [],
            ip:         $this->request->ip(),
            userAgent:  $this->request->userAgent(),
        ));
    }
}
