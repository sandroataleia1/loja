<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Enums\ProductStatusEnum;
use App\Modules\Catalog\Events\ProductArchived;
use App\Modules\Catalog\Models\Product;
use App\Shared\Exceptions\BusinessException;

final readonly class ArchiveProductAction
{
    public function execute(Product $product): Product
    {
        if ($product->status === ProductStatusEnum::Archived) {
            throw new BusinessException('Produto já está arquivado.');
        }

        $product->update(['status' => ProductStatusEnum::Archived]);

        ProductArchived::dispatch($product->refresh());

        return $product;
    }
}
