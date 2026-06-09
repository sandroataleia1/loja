<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Events;

use App\Modules\Omnichannel\Models\ChannelProduct;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ChannelSyncFailed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ChannelProduct $channelProduct,
        public readonly string         $reason,
        public readonly int            $attempt,
    ) {}
}
