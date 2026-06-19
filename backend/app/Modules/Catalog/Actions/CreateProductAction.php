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
            'type'                  => $dto->type,
            'gender'                => $dto->gender,
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
