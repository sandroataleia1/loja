<?php

declare(strict_types=1);

namespace App\Core\Auth\DTOs;

final readonly class GrantStoreAccessDTO
{
    public function __construct(
        public readonly string $tenantUserId,
        public readonly string $storeId,
    ) {}
}
