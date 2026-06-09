<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Conversão atribuída a uma campanha.
 * sale_id: conversão via PDV. order_id: conversão via omnichannel.
 */
final class CampaignConverted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $campaignId,
        public readonly string $customerId,
        public readonly float $revenue,
        public readonly ?string $saleId,
        public readonly ?string $orderId,
        public readonly \DateTimeInterface $occurredAt,
    ) {}
}
