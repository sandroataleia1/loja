<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Modules\Catalog\Enums\ProductStatusEnum;
use App\Modules\Catalog\Enums\ProductTypeEnum;
use App\Modules\Catalog\Enums\UnitOfMeasureEnum;
use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class UpdateProductDTO extends BaseDTO
{
    public function __construct(
        public ?string             $name,
        public ?ProductTypeEnum    $type,
        public ?UnitOfMeasureEnum  $unitOfMeasure,
        public ?string            $ncm,
        public ?string            $cest,
        public ?string            $cfopDefault,
        public ?int               $originCode,
        public ?ProductStatusEnum  $status,
        public ?string            $brandId,
        public ?array            $categoryUuids,
        public ?string           $collectionId,
        public ?string           $gridId,
        public ?string           $unitId,
        public ?int              $basePriceCents,
        public ?int              $costPriceCents,
        public ?string           $description,
        public ?string           $shortDescription,
        public ?bool             $isFeatured,
        public ?bool             $isDigital,
        public ?array            $seo,
        public ?array            $tags,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            name:             $request->string('name')->value() ?: null,
            type:             $request->has('type') ? ProductTypeEnum::from($request->string('type')->toString()) : null,
            unitOfMeasure:    $request->has('unit_of_measure')
                                ? ($request->filled('unit_of_measure') ? UnitOfMeasureEnum::from($request->string('unit_of_measure')->toString()) : null)
                                : null,
            ncm:              $request->has('ncm') ? ($request->string('ncm')->value() ?: null) : null,
            cest:             $request->has('cest') ? ($request->string('cest')->value() ?: null) : null,
            cfopDefault:      $request->has('cfop_default') ? ($request->string('cfop_default')->value() ?: null) : null,
            originCode:       $request->has('origin_code') ? $request->integer('origin_code') : null,
            status:           $request->has('status') ? ProductStatusEnum::from($request->string('status')->toString()) : null,
            brandId:          $request->has('brand_id') ? ($request->string('brand_id')->value() ?: null) : null,
            categoryUuids:    $request->has('category_uuids') ? $request->array('category_uuids') : null,
            collectionId:     $request->has('collection_id') ? ($request->string('collection_id')->value() ?: null) : null,
            gridId:           $request->has('grid_id') ? ($request->string('grid_id')->value() ?: null) : null,
            unitId:           $request->has('unit_id') ? ($request->string('unit_id')->value() ?: null) : null,
            basePriceCents:   $request->has('base_price_cents') ? $request->integer('base_price_cents') : null,
            costPriceCents:   $request->has('cost_price_cents') ? $request->integer('cost_price_cents') : null,
            description:      $request->has('description') ? ($request->string('description')->value() ?: null) : null,
            shortDescription: $request->has('short_description') ? ($request->string('short_description')->value() ?: null) : null,
            isFeatured:       $request->has('is_featured') ? $request->boolean('is_featured') : null,
            isDigital:        $request->has('is_digital') ? $request->boolean('is_digital') : null,
            seo:              $request->has('seo') ? $request->array('seo') : null,
            tags:             $request->has('tags') ? $request->array('tags') : null,
        );
    }
}
