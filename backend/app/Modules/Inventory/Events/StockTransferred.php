<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Events;

use App\Modules\Inventory\Models\StockTransfer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class StockTransferred
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly StockTransfer $transfer,
    ) {}
}
