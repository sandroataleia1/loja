<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Events;

use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class StockAdjusted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly InventoryBalance $balance,
        public readonly StockMovement    $movement,
    ) {}
}
