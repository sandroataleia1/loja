<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Events;

use App\Modules\Inventory\Models\Store;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class StoreCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Store $store,
    ) {}
}
