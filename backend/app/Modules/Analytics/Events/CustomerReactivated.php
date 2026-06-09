<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Disparado quando um cliente dormant (90+ dias sem compra) volta a comprar.
 * Permite medir eficácia de campanhas de reativação.
 */
final class CustomerReactivated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $customerId,
        public readonly string $saleId,
        public readonly int $daysSinceLastPurchase,
        public readonly ?string $campaignId,
        public readonly \DateTimeInterface $occurredAt,
    ) {}
}
