<?php

declare(strict_types=1);

namespace App\Core\Auth\Actions;

use App\Core\Audit\DTOs\AuditLogDTO;
use App\Core\Audit\Enums\AuditActionEnum;
use App\Core\Audit\Enums\AuditEntityTypeEnum;
use App\Core\Audit\Services\AuditLogger;
use App\Core\Auth\DTOs\LoginDTO;
use App\Core\Auth\Models\User;
use App\Core\Tenancy\Models\TenantSettings;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

final readonly class LoginAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private Request     $request,
    ) {}

    public function execute(LoginDTO $dto): array
    {
        $query = User::where('email', $dto->email)->where('is_active', true);

        if ($dto->tenantId !== null) {
            $query->where('tenant_id', $dto->tenantId);
        }

        $user = $query->first();

        // Conta inexistente: nega sem revelar qual campo está errado
        if ($user === null) {
            $this->auditFailedLogin($dto->email);
            throw new BusinessException('Invalid credentials.');
        }

        // Conta bloqueada por excesso de tentativas
        if ($user->locked_until !== null && $user->locked_until->isFuture()) {
            $until = $user->locked_until->format('H:i');
            throw new BusinessException(
                "Conta bloqueada até {$until}. Tente novamente mais tarde."
            );
        }

        // Senha inválida — incrementa contador e bloqueia se necessário
        if (! Hash::check($dto->password, $user->password)) {
            $this->handleFailedAttempt($user);
            $this->auditFailedLogin($dto->email, $user->uuid, $user->tenant_id);
            throw new BusinessException('Invalid credentials.');
        }

        // Login bem-sucedido — reseta contadores
        $user->tokens()->where('name', $dto->deviceName)->delete();
        $token = $user->createToken($dto->deviceName)->plainTextToken;

        $user->update([
            'last_login_at'      => now(),
            'failed_login_count' => 0,
            'locked_until'       => null,
        ]);

        $this->auditLogger->record(new AuditLogDTO(
            entityType: AuditEntityTypeEnum::User,
            entityUuid: $user->uuid,
            action:     AuditActionEnum::Login,
            tenantId:   $user->tenant_id ?? null,
            userId:     $user->uuid,
            metadata:   ['device_name' => $dto->deviceName],
            ip:         $this->request->ip(),
            userAgent:  $this->request->userAgent(),
        ));

        return [
            'token' => $token,
            'user'  => $user,
        ];
    }

    private function handleFailedAttempt(User $user): void
    {
        $settings = $user->tenant_id
            ? TenantSettings::forTenant($user->tenant_id)
            : null;

        $maxAttempts   = $settings?->maxLoginAttempts() ?? 5;
        $lockoutMinutes = $settings?->lockoutMinutes() ?? 15;

        $newCount = $user->failed_login_count + 1;

        if ($newCount >= $maxAttempts) {
            $lockedUntil = now()->addMinutes($lockoutMinutes);

            $user->update([
                'failed_login_count' => 0,
                'locked_until'       => $lockedUntil,
            ]);

            $this->auditLogger->record(new AuditLogDTO(
                entityType: AuditEntityTypeEnum::User,
                entityUuid: $user->uuid,
                action:     AuditActionEnum::AuthAccountLocked,
                tenantId:   $user->tenant_id ?? null,
                userId:     $user->uuid,
                metadata:   [
                    'locked_until'    => $lockedUntil->toIso8601String(),
                    'lockout_minutes' => $lockoutMinutes,
                ],
                ip:        $this->request->ip(),
                userAgent: $this->request->userAgent(),
            ));
        } else {
            $user->update(['failed_login_count' => $newCount]);
        }
    }

    private function auditFailedLogin(string $email, ?string $userId = null, ?string $tenantId = null): void
    {
        $this->auditLogger->record(new AuditLogDTO(
            entityType: AuditEntityTypeEnum::User,
            entityUuid: $userId ?? '00000000-0000-0000-0000-000000000000',
            action:     AuditActionEnum::FailedLogin,
            tenantId:   $tenantId,
            userId:     $userId,
            metadata:   ['email' => $email],
            ip:         $this->request->ip(),
            userAgent:  $this->request->userAgent(),
        ));
    }
}
