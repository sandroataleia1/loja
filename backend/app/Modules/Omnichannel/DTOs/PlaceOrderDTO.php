<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\DTOs;

use DateTimeImmutable;

final readonly class PlaceOrderDTO
{
    public function __construct(
        public readonly string             $tenantId,
        public readonly string             $channelId,
        public readonly float              $totalAmount,
        public readonly ?string            $customerId = null,
        public readonly ?string            $storeId    = null,
        public readonly ?array             $metadata   = null,   // line items snapshot, shipping, notes
        public readonly ?DateTimeImmutable $placedAt   = null,   // defaults to now()
    ) {}
}
