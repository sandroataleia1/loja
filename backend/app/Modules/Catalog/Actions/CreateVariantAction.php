<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\DTOs\CreateVariantDTO;
use App\Modules\Catalog\Events\VariantCreated;
use App\Modules\Catalog\Models\Attribute;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Variant;
use App\Shared\Actions\GenerateVariantCodeAction;
use App\Shared\Exceptions\ConflictException;
use App\Shared\Exceptions\NotFoundException;
use Illuminate\Support\Facades\DB;

final readonly class CreateVariantAction
{
    public function __construct(
        private GenerateVariantCodeAction $generateVariantCode,
    ) {}

    public function execute(CreateVariantDTO $dto): Variant
    {
        $product = Product::where('uuid', $dto->productId)->first();

        if ($product === null) {
            throw new NotFoundException("Produto '{$dto->productId}' não encontrado.");
        }

        if (Variant::where('sku', $dto->sku)->exists()) {
            throw new ConflictException("SKU '{$dto->sku}' já está em uso nesta empresa.");
        }

        return DB::transaction(function () use ($dto, $product): Variant {
            if ($dto->isDefault) {
                Variant::where('product_id', $product->uuid)->update(['is_default' => false]);
            }

            $code = $this->generateVariantCode->execute(
                tenantId: TenantContext::getIdOrFail(),
                product:  $product,
            );

            $variant = Variant::create([
                'product_id'       => $product->uuid,
                'code'             => $code,
                'sku'              => $dto->sku,
                'name'             => $dto->name,
                'barcode'          => $dto->barcode,
                'gtin'             => $dto->gtin,
                'price_cents'      => $dto->priceCents,
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
            ]);

            if ($dto->attributeIds !== []) {
                $attributes = Attribute::whereIn('uuid', $dto->attributeIds)->get();
                $pivot      = $attributes->mapWithKeys(fn (Attribute $a, int $i) => [
                    $a->uuid => ['sort_order' => $i],
                ]);
                $variant->attributes()->sync($pivot->all());

                if ($variant->name === null) {
                    $variant->update(['name' => $variant->load('attributes')->buildNameFromAttributes()]);
                }

                // Build grid_combination JSONB snapshot
                $combination = $variant->attributes()
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

                $variant->update(['grid_combination' => $combination]);
            }

            VariantCreated::dispatch($variant->refresh());

            return $variant->load('attributes');
        });
    }
}
