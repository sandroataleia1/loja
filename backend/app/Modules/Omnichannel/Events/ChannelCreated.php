<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Events;

use App\Modules\Omnichannel\Models\Channel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ChannelCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Channel $channel,
    ) {}
}
