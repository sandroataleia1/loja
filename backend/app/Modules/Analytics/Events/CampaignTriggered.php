<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CampaignTriggered
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $campaignId,
        public readonly ?string $segmentId,
        public readonly ?string $channelId,
        public readonly int $customerCount,
        public readonly \DateTimeInterface $occurredAt,
    ) {}
}
