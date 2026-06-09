<?php

declare(strict_types=1);

namespace App\Modules\Sales\Events;

use App\Modules\Sales\Models\Sale;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SaleCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Sale $sale,
    ) {}
}
