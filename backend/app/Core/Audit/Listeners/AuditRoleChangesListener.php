<?php

declare(strict_types=1);

namespace App\Core\Audit\Listeners;

use App\Core\Audit\DTOs\AuditLogDTO;
use App\Core\Audit\Enums\AuditActionEnum;
use App\Core\Audit\Enums\AuditEntityTypeEnum;
use App\Core\Audit\Services\AuditLogger;
use App\Core\Auth\Events\RoleAssigned;
use App\Core\Auth\Events\RoleRevoked;
use App\Core\Auth\Events\StoreAccessGranted;
use App\Core\Auth\Events\StoreAccessRevoked;
use Illuminate\Http\Request;

final class AuditRoleChangesListener
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly Request     $request,
    ) {}

    public function handleRoleAssigned(RoleAssigned $event): void
    {
        $this->auditLogger->record(new AuditLogDTO(
            entityType: AuditEntityTypeEnum::TenantUser,
            entityUuid: $event->tenantUser->uuid,
            action:     AuditActionEnum::RoleAssigned,
            tenantId:   $event->tenantUser->tenant_id,
            userId:     $event->assignedBy,
            metadata:   ['role_id' => $event->role->uuid, 'role_slug' => $event->role->slug],
            ip:         $this->request->ip(),
            userAgent:  $this->request->userAgent(),
        ));
    }

    public function handleRoleRevoked(RoleRevoked $event): void
    {
        $this->auditLogger->record(new AuditLogDTO(
            entityType: AuditEntityTypeEnum::TenantUser,
            entityUuid: $event->tenantUser->uuid,
            action:     AuditActionEnum::RoleRevoked,
            tenantId:   $event->tenantUser->tenant_id,
            userId:     $event->revokedBy,
            ip:         $this->request->ip(),
            userAgent:  $this->request->userAgent(),
        ));
    }

    public function handleStoreAccessGranted(StoreAccessGranted $event): void
    {
        $this->auditLogger->record(new AuditLogDTO(
            entityType: AuditEntityTypeEnum::TenantUser,
            entityUuid: $event->tenantUser->uuid,
            action:     AuditActionEnum::StoreAccessGranted,
            tenantId:   $event->tenantUser->tenant_id,
            metadata:   ['store_id' => $event->storeId],
            ip:         $this->request->ip(),
            userAgent:  $this->request->userAgent(),
        ));
    }

    public function handleStoreAccessRevoked(StoreAccessRevoked $event): void
    {
        $this->auditLogger->record(new AuditLogDTO(
            entityType: AuditEntityTypeEnum::TenantUser,
            entityUuid: $event->tenantUser->uuid,
            action:     AuditActionEnum::StoreAccessRevoked,
            tenantId:   $event->tenantUser->tenant_id,
            metadata:   ['store_id' => $event->storeId],
            ip:         $this->request->ip(),
            userAgent:  $this->request->userAgent(),
        ));
    }
}
