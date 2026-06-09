<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Events;

use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ProductTagged
{
    use Dispatchable;
    use SerializesModels;

    /** @param string[] $tagNames */
    public function __construct(
        public readonly Product $product,
        public readonly array   $tagNames,
    ) {}
}
