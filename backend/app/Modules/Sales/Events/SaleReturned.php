<?php

declare(strict_types=1);

namespace App\Modules\Sales\Events;

use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleReturn;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SaleReturned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SaleReturn $saleReturn,
        public readonly Sale       $originalSale,
    ) {}
}
