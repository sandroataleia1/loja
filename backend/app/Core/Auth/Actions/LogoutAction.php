<?php

declare(strict_types=1);

namespace App\Core\Auth\Actions;

use App\Core\Audit\DTOs\AuditLogDTO;
use App\Core\Audit\Enums\AuditActionEnum;
use App\Core\Audit\Enums\AuditEntityTypeEnum;
use App\Core\Audit\Services\AuditLogger;
use App\Core\Auth\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

final readonly class LogoutAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private Request     $request,
    ) {}

    public function execute(User $user): void
    {
        $token = $user->currentAccessToken();

        // TransientToken is returned in tests with actingAs() — has no delete()
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        $this->auditLogger->record(new AuditLogDTO(
            entityType: AuditEntityTypeEnum::User,
            entityUuid: $user->uuid,
            action:     AuditActionEnum::Logout,
            tenantId:   $user->tenant_id ?? null,
            userId:     $user->uuid,
            ip:         $this->request->ip(),
            userAgent:  $this->request->userAgent(),
        ));
    }
}
