<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\DTOs\UpdateProductDTO;
use App\Modules\Catalog\Events\ProductUpdated;
use App\Modules\Catalog\Models\CatalogBarcode;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class UpdateProductAction
{
    public function execute(Product $product, UpdateProductDTO $dto): Product
    {
        return DB::transaction(function () use ($product, $dto): Product {
            $data = array_filter([
                'name'              => $dto->name,
                'slug'              => $dto->name !== null ? Str::slug($dto->name) : null,
                'type'              => $dto->type?->value,
                'unit_of_measure'   => $dto->unitId === null ? $dto->unitOfMeasure?->value : null,
                'unit_id'           => $dto->unitId,
                'base_price_cents'  => $dto->basePriceCents,
                'cost_price_cents'  => $dto->costPriceCents,
                'ncm'               => $dto->ncm,
                'cest'              => $dto->cest,
                'cfop_default'      => $dto->cfopDefault,
                'origin_code'       => $dto->originCode,
                'status'            => $dto->status?->value,
                'brand_id'          => $dto->brandId,
                'collection_id'     => $dto->collectionId,
                'grid_id'           => $dto->gridId,
                'description'       => $dto->description,
                'short_description' => $dto->shortDescription,
                'is_featured'       => $dto->isFeatured,
                'is_digital'        => $dto->isDigital,
                'seo'               => $dto->seo,
                'internal_notes'    => $dto->internalNotes,
                'location'          => $dto->location,
            ], fn ($v) => $v !== null);

            $rawOriginal = collect(array_keys($data))
                ->mapWithKeys(fn ($k) => [$k => $product->getRawOriginal($k)])
                ->all();
            $changed = array_keys(array_diff_assoc($data, $rawOriginal));

            $product->update($data);

            if ($dto->categoryUuids !== null) {
                $product->categories()->sync($dto->categoryUuids);
            }

            if ($dto->tags !== null) {
                $this->syncTags($product, $dto->tags);
            }

            if ($dto->barcodes !== null) {
                $this->syncBarcodes($product, $dto->barcodes);
            }

            if ($changed !== []) {
                ProductUpdated::dispatch($product->refresh(), $changed);
            }

            return $product->refresh();
        });
    }

    /** @param array<array{value:string,type:string,is_primary:bool}> $barcodes */
    private function syncBarcodes(Product $product, array $barcodes): void
    {
        $tenantId = TenantContext::getIdOrFail();
        $product->barcodes()->delete();
        foreach ($barcodes as $barcode) {
            $value = trim($barcode['value'] ?? '');
            if ($value === '') {
                continue;
            }
            CatalogBarcode::firstOrCreate(
                ['tenant_id' => $tenantId, 'value' => $value],
                [
                    'product_id'   => $product->uuid,
                    'variant_id'   => null,
                    'barcode_type' => $barcode['type'] ?? 'ean13',
                    'is_primary'   => (bool) ($barcode['is_primary'] ?? false),
                ],
            );
        }
    }

    /** @param string[] $tagNames */
    private function syncTags(Product $product, array $tagNames): void
    {
        $tagIds = collect($tagNames)->map(function (string $name): string {
            $slug = Str::slug($name);

            // tenant_id explícito para evitar dependência implícita do TenantScope
            // (crítico em jobs/workers onde o scope pode estar desativado)
            $tag = Tag::firstOrCreate(
                ['tenant_id' => TenantContext::getIdOrFail(), 'slug' => $slug],
                ['name' => $name, 'type' => 'product'],
            );

            return $tag->uuid;
        });

        $product->tags()->sync($tagIds->all());
    }
}
