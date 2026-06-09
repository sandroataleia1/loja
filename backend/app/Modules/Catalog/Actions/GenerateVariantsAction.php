<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\GenerateVariantsDTO;
use App\Modules\Catalog\Enums\ProductTypeEnum;
use App\Modules\Catalog\Events\VariantCreated;
use App\Modules\Catalog\Models\Attribute;
use App\Modules\Catalog\Models\Grid;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Variant;
use App\Shared\Exceptions\BusinessException;
use App\Shared\Exceptions\NotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final readonly class GenerateVariantsAction
{
    /** @return Variant[] */
    public function execute(GenerateVariantsDTO $dto): array
    {
        $product = Product::where('uuid', $dto->productId)->first();

        if ($product === null) {
            throw new NotFoundException("Produto '{$dto->productId}' não encontrado.");
        }

        if ($product->type !== ProductTypeEnum::Variable) {
            throw new BusinessException('Somente produtos do tipo "variable" suportam geração automática de variantes.');
        }

        $grid = Grid::where('uuid', $dto->gridId)->with('attributes')->first();

        if ($grid === null) {
            throw new NotFoundException("Grade '{$dto->gridId}' não encontrada.");
        }

        /** @var Collection<int, Attribute> $gridAttributes */
        $gridAttributes = $grid->attributes;

        if ($gridAttributes->isEmpty()) {
            throw new BusinessException('A grade não possui atributos configurados.');
        }

        return DB::transaction(function () use ($dto, $product, $gridAttributes): array {
            $product->update(['grid_id' => $dto->gridId]);

            $created  = [];
            $existing = Variant::where('product_id', $product->uuid)
                ->get()
                ->pluck('sku')
                ->all();

            foreach ($gridAttributes as $i => $attribute) {
                $sku = "{$dto->skuPrefix}-{$attribute->value}";

                if (in_array($sku, $existing, true)) {
                    continue;
                }

                $variant = Variant::create([
                    'product_id'  => $product->uuid,
                    'sku'         => $sku,
                    'name'        => $attribute->label,
                    'price_cents' => $dto->basePriceCents,
                    'is_active'   => true,
                    'is_default'  => $i === 0,
                    'sort_order'  => $i,
                ]);

                $variant->attributes()->sync([$attribute->uuid => ['sort_order' => 0]]);

                // Build grid_combination JSONB snapshot for this single attribute
                $combination = [[
                    'group_id'   => $attribute->attribute_group_id,
                    'group_name' => $attribute->relationLoaded('group') ? $attribute->group?->name : null,
                    'attr_id'    => $attribute->uuid,
                    'attr_value' => $attribute->label ?: $attribute->value,
                ]];
                $variant->update(['grid_combination' => $combination]);

                VariantCreated::dispatch($variant);

                $created[] = $variant->load('attributes');
            }

            return $created;
        });
    }
}
