<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ProductAddedToCart
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $productId,
        public readonly ?string $variantId,
        public readonly ?string $customerId,
        public readonly ?string $channelId,
        public readonly ?string $campaignId,
        public readonly int $quantity,
        public readonly float $unitPrice,
        public readonly \DateTimeInterface $occurredAt,
    ) {}
}
