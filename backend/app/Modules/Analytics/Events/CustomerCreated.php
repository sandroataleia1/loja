<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CustomerCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $customerId,
        public readonly ?string $acquisitionChannelId,
        public readonly ?string $campaignId,
        public readonly ?string $source,
        public readonly ?string $medium,
        public readonly \DateTimeInterface $occurredAt,
    ) {}
}
