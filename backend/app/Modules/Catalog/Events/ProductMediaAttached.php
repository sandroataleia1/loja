<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Events;

use App\Modules\Catalog\Models\Product;
use App\Modules\Media\Models\MediaAsset;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ProductMediaAttached
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Product    $product,
        public readonly MediaAsset $mediaAsset,
        public readonly bool       $isPrimary,
    ) {}
}
