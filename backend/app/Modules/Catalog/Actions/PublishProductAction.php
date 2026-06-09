<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Enums\ProductStatusEnum;
use App\Modules\Catalog\Enums\ProductTypeEnum;
use App\Modules\Catalog\Events\ProductPublished;
use App\Modules\Catalog\Models\Product;
use App\Shared\Exceptions\BusinessException;

final readonly class PublishProductAction
{
    public function execute(Product $product): Product
    {
        if ($product->status === ProductStatusEnum::Active) {
            throw new BusinessException('Produto já está publicado.');
        }

        if ($product->status === ProductStatusEnum::Archived) {
            throw new BusinessException('Produto arquivado não pode ser publicado. Restaure-o primeiro.');
        }

        if ($product->type === ProductTypeEnum::Variable && $product->activeVariants()->doesntExist()) {
            throw new BusinessException('Produto com variantes precisa de ao menos uma variante ativa antes de ser publicado.');
        }

        $product->update(['status' => ProductStatusEnum::Active]);

        ProductPublished::dispatch($product->refresh());

        return $product;
    }
}
