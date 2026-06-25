<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Enums\ProductStatusEnum;
use App\Modules\Catalog\Events\ProductCreated;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Variant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class DuplicateProductAction
{
    public function execute(Product $product): Product
    {
        return DB::transaction(function () use ($product): Product {
            $copy = $product->replicate(['uuid', 'slug', 'status', 'search_vector']);
            $copy->uuid   = (string) Str::uuid();
            $copy->slug   = $product->slug . '-copia-' . now()->format('YmdHis');
            $copy->status = ProductStatusEnum::Draft;
            $copy->save();

            // Duplicar variantes sem estoque (estoque pertence ao módulo Inventory)
            $product->load('variants.attributes');

            foreach ($product->variants as $variant) {
                $newVariant = $variant->replicate(['uuid', 'sku', 'is_default']);
                $newVariant->uuid       = (string) Str::uuid();
                $newVariant->product_id = $copy->uuid;
                $newVariant->sku        = $variant->sku . '-C';
                $newVariant->is_default = false;
                $newVariant->save();

                $pivotData = $variant->attributes->mapWithKeys(fn (Attribute $a, int $i) => [
                    $a->uuid => ['sort_order' => $i],
                ]);
                $newVariant->attributes()->sync($pivotData->all());
            }

            ProductCreated::dispatch($copy);

            return $copy->load('variants');
        });
    }
}
