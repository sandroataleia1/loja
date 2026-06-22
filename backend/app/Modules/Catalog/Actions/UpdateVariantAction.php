<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\UpdateVariantDTO;
use App\Modules\Catalog\Events\VariantUpdated;
use App\Modules\Catalog\Models\Attribute;
use App\Modules\Catalog\Models\Variant;
use App\Shared\Exceptions\ConflictException;
use Illuminate\Support\Facades\DB;

final readonly class UpdateVariantAction
{
    public function execute(Variant $variant, UpdateVariantDTO $dto): Variant
    {
        // Valida unicidade de SKU (ignorando a própria variante)
        if ($dto->sku !== null && $dto->sku !== $variant->sku) {
            if (Variant::where('sku', $dto->sku)->where('uuid', '!=', $variant->uuid)->exists()) {
                throw new ConflictException("SKU '{$dto->sku}' já está em uso nesta empresa.");
            }
        }

        return DB::transaction(function () use ($variant, $dto): Variant {
            // Quando is_default muda para true, remove o default das outras variantes do produto
            if ($dto->isDefault === true && ! $variant->is_default) {
                Variant::where('product_id', $variant->product_id)
                    ->where('uuid', '!=', $variant->uuid)
                    ->update(['is_default' => false]);
            }

            // Constrói apenas os campos que foram enviados na requisição
            $fields = array_filter([
                'sku'              => $dto->sku,
                'price_cents'      => $dto->priceCents,
                'name'             => $dto->name,
                'barcode'          => $dto->barcode,
                'gtin'             => $dto->gtin,
                'cost_cents'       => $dto->costCents,
                'compare_at_cents' => $dto->compareAtCents,
                'weight_g'         => $dto->weightG,
                'dimensions'       => $dto->dimensions,
                'is_active'        => $dto->isActive,
                'is_default'       => $dto->isDefault,
                'sort_order'       => $dto->sortOrder,
                'ncm'              => $dto->ncm,
                'cest'             => $dto->cest,
                'cfop_default'     => $dto->cfopDefault,
                'origin_code'      => $dto->originCode,
                'tax_profile_id'   => $dto->taxProfileId,
            ], fn ($v) => $v !== null);

            if (! empty($fields)) {
                $variant->update($fields);
            }

            // Sincroniza atributos e reconstrói nome e grid_combination se fornecidos
            if ($dto->attributeIds !== null) {
                $attributes = Attribute::whereIn('uuid', $dto->attributeIds)->get();

                $pivot = $attributes->mapWithKeys(
                    fn (Attribute $a, int $i) => [$a->uuid => ['sort_order' => $i]],
                );
                $variant->attributes()->sync($pivot->all());

                $variant->load('attributes');

                // Reconstrói nome automático se o nome não foi enviado explicitamente
                if ($dto->name === null && $variant->attributes->isNotEmpty()) {
                    $variant->update(['name' => $variant->buildNameFromAttributes()]);
                }

                // Reconstrói snapshot grid_combination
                $gridCombination = $variant->attributes()
                    ->with('group')
                    ->get()
                    ->map(fn ($attr) => [
                        'group_id'   => $attr->attribute_group_id,
                        'group_name' => $attr->group?->name,
                        'attr_id'    => $attr->uuid,
                        'attr_value' => $attr->label ?: $attr->value,
                    ])
                    ->values()
                    ->all();

                $variant->update(['grid_combination' => $gridCombination]);
            }

            VariantUpdated::dispatch($variant->refresh());

            return $variant->load('attributes');
        });
    }
}
