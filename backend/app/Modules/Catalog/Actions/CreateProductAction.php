<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\DTOs\CreateProductDTO;
use App\Modules\Catalog\Events\ProductCreated;
use App\Modules\Catalog\Models\Product;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;
use App\Shared\Exceptions\ConflictException;

final readonly class CreateProductAction
{
    public function __construct(
        private GenerateInternalCodeAction $generateCode,
    ) {}

    public function execute(CreateProductDTO $dto): Product
    {
        if (Product::where('slug', $dto->slug)->exists()) {
            throw new ConflictException("Produto com slug '{$dto->slug}' já existe nesta empresa.");
        }

        $code = $this->generateCode->execute(
            tenantId: TenantContext::getIdOrFail(),
            entity:   SequenceEntityEnum::Product,
        );

        $product = Product::create([
            'code'                  => $code,
            'brand_id'              => $dto->brandId,
            'collection_id'         => $dto->collectionId,
            'grid_id'               => $dto->gridId,
            'name'                  => $dto->name,
            'slug'                  => $dto->slug,
            'description'           => $dto->description,
            'short_description'     => $dto->shortDescription,
            'marketing_description' => $dto->marketingDescription,
            'internal_notes'        => $dto->internalNotes,
            'unit_id'               => $dto->unitId,
            'base_price_cents'      => $dto->basePriceCents,
            'cost_price_cents'      => $dto->costPriceCents,
            'type'                  => $dto->type,
            // unit_of_measure kept for legacy compatibility — only set when unit_id is absent
            'unit_of_measure'       => $dto->unitId === null ? $dto->unitOfMeasure : null,
            'ncm'                   => $dto->ncm,
            'cest'                  => $dto->cest,
            'cfop_default'          => $dto->cfopDefault,
            'origin_code'           => $dto->originCode ?? 0,
            'status'                => $dto->status,
            'season'                => $dto->season,
            'launch_date'           => $dto->launchDate,
            'is_featured'           => $dto->isFeatured,
            'is_digital'            => $dto->isDigital,
            'is_publishable'        => $dto->isPublishable,
            'seo'                   => $dto->seo,
        ]);

        if (! empty($dto->categoryUuids)) {
            $product->categories()->attach($dto->categoryUuids);
        }

        ProductCreated::dispatch($product);

        return $product;
    }
}
