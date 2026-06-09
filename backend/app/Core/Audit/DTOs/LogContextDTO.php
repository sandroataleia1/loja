<?php

declare(strict_types=1);

namespace App\Core\Audit\DTOs;

final readonly class LogContextDTO
{
    public function __construct(
        public readonly ?string $correlationId = null,
        public readonly ?string $requestId     = null,
        public readonly ?string $tenantId      = null,
        public readonly ?string $userId        = null,
        public readonly ?string $deviceId      = null,
        public readonly ?string $storeId       = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'correlation_id' => $this->correlationId,
            'request_id'     => $this->requestId,
            'tenant_id'      => $this->tenantId,
            'user_id'        => $this->userId,
            'device_id'      => $this->deviceId,
            'store_id'       => $this->storeId,
        ]);
    }
}
