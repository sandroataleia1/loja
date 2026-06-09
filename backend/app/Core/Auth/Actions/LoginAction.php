<?php

declare(strict_types=1);

namespace App\Core\Auth\Actions;

use App\Core\Audit\DTOs\AuditLogDTO;
use App\Core\Audit\Enums\AuditActionEnum;
use App\Core\Audit\Enums\AuditEntityTypeEnum;
use App\Core\Audit\Services\AuditLogger;
use App\Core\Auth\DTOs\LoginDTO;
use App\Core\Auth\Models\User;
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

        if ($user === null || ! Hash::check($dto->password, $user->password)) {
            $this->auditFailedLogin($dto->email);

            throw new BusinessException('Invalid credentials.');
        }

        if (! $user->hasVerifiedEmail()) {
            throw new BusinessException('E-mail não verificado. Verifique sua caixa de entrada e confirme o e-mail antes de fazer login.');
        }

        $user->tokens()->where('name', $dto->deviceName)->delete();

        $token = $user->createToken($dto->deviceName)->plainTextToken;

        $user->update(['last_login_at' => now()]);

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

    private function auditFailedLogin(string $email): void
    {
        $this->auditLogger->record(new AuditLogDTO(
            entityType: AuditEntityTypeEnum::User,
            entityUuid: '00000000-0000-0000-0000-000000000000',
            action:     AuditActionEnum::FailedLogin,
            metadata:   ['email' => $email],
            ip:         $this->request->ip(),
            userAgent:  $this->request->userAgent(),
        ));
    }
}
