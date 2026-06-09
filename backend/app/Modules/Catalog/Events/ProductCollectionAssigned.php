<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Events;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductCollection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ProductCollectionAssigned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Product           $product,
        public readonly ProductCollection $collection,
    ) {}
}
