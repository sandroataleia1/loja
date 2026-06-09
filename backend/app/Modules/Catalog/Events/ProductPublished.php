<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Events;

use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;

final class ProductPublished
{
    use Dispatchable;

    public function __construct(public readonly Product $product) {}
}
