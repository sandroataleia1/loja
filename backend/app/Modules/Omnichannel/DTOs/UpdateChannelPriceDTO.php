<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\DTOs;

use DateTimeImmutable;

final readonly class UpdateChannelPriceDTO
{
    public function __construct(
        public readonly string              $tenantId,
        public readonly string              $channelId,
        public readonly string              $productId,
        public readonly float               $price,
        public readonly ?float              $promotionalPrice = null,
        public readonly ?DateTimeImmutable  $startsAt         = null,
        public readonly ?DateTimeImmutable  $endsAt           = null,
    ) {}
}
