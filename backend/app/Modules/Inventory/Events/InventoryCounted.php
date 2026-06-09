<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Events;

use App\Modules\Inventory\Models\StockCount;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class InventoryCounted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly StockCount $count,
    ) {}
}
