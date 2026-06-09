<?php

declare(strict_types=1);

namespace App\Core\Auth\DTOs;

final readonly class AssignRoleDTO
{
    public function __construct(
        public readonly string  $tenantId,
        public readonly string  $userId,
        public readonly string  $roleId,
        public readonly ?string $assignedBy = null,
    ) {}
}
