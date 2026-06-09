<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Events;

use App\Modules\Omnichannel\Enums\FulfillmentTypeEnum;
use App\Modules\Omnichannel\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OrderFulfilled
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Order               $order,
        public readonly FulfillmentTypeEnum $fulfillmentType,
        public readonly ?string             $saleUuid = null,   // linked Sale if created at PDV
    ) {}
}
