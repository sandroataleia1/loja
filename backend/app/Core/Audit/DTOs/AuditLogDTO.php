<?php

declare(strict_types=1);

namespace App\Core\Audit\DTOs;

use App\Core\Audit\Enums\AuditActionEnum;
use App\Core\Audit\Enums\AuditEntityTypeEnum;

final readonly class AuditLogDTO
{
    public function __construct(
        public readonly AuditEntityTypeEnum $entityType,
        public readonly string              $entityUuid,
        public readonly AuditActionEnum     $action,
        public readonly ?string             $tenantId      = null,
        public readonly ?string             $storeId       = null,
        public readonly ?string             $userId        = null,
        public readonly ?string             $deviceId      = null,
        public readonly ?string             $correlationId = null,
        public readonly ?array              $oldValues     = null,
        public readonly ?array              $newValues     = null,
        public readonly ?array              $metadata      = null,
        public readonly ?string             $ip            = null,
        public readonly ?string             $userAgent     = null,
    ) {}
}
