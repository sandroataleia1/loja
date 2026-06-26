<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Modules\Catalog\Enums\ProductStatusEnum;
use App\Modules\Catalog\Enums\ProductTypeEnum;
use App\Modules\Catalog\Enums\UnitOfMeasureEnum;
use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class CreateProductDTO extends BaseDTO
{
    public function __construct(
        public string             $name,
        public string             $slug,
        public ProductTypeEnum    $type,
        public ?UnitOfMeasureEnum $unitOfMeasure,
        public ?string           $ncm,
        public ?string           $cest,
        public ?string           $cfopDefault,
        public ?int              $originCode,
        public ProductStatusEnum  $status,
        public ?string           $brandId,
        public ?array            $categoryUuids,
        public ?string           $collectionId,
        public ?string           $gridId,
        public ?string           $unitId,
        public ?int              $basePriceCents,
        public ?int              $costPriceCents,
        public ?string           $description,
        public ?string           $shortDescription,
        public ?string           $marketingDescription,
        public ?string           $internalNotes,
        public bool              $isFeatured,
        public bool              $isDigital,
        public bool              $isPublishable,
        public ?string           $season,
        public ?string           $launchDate,
        public ?array            $seo,
        public ?string           $location,
        public ?array            $barcodes,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            name:                 $request->string('name')->toString(),
            slug:                 Str::slug($request->string('name')->toString()),
            type:                 ProductTypeEnum::from($request->string('type', 'simple')->toString()),
            unitOfMeasure:        $request->filled('unit_of_measure')
                                    ? UnitOfMeasureEnum::from($request->string('unit_of_measure')->toString())
                                    : null,
            ncm:                  $request->string('ncm')->value() ?: null,
            cest:                 $request->string('cest')->value() ?: null,
            cfopDefault:          $request->string('cfop_default')->value() ?: null,
            originCode:           $request->has('origin_code') ? $request->integer('origin_code') : null,
            status:               ProductStatusEnum::from($request->string('status', 'draft')->toString()),
            brandId:              $request->string('brand_id')->value() ?: null,
            categoryUuids:        $request->has('category_uuids') ? $request->array('category_uuids') : null,
            collectionId:         $request->string('collection_id')->value() ?: null,
            gridId:               $request->string('grid_id')->value() ?: null,
            unitId:               $request->string('unit_id')->value() ?: null,
            basePriceCents:       $request->has('base_price_cents')
                                    ? $request->integer('base_price_cents')
                                    : ($request->has('base_price') ? (int) round($request->float('base_price') * 100) : null),
            costPriceCents:       $request->has('cost_price_cents')
                                    ? $request->integer('cost_price_cents')
                                    : ($request->has('cost_price') ? (int) round($request->float('cost_price') * 100) : null),
            description:          $request->string('description')->value() ?: null,
            shortDescription:     $request->string('short_description')->value() ?: null,
            marketingDescription: $request->string('marketing_description')->value() ?: null,
            internalNotes:        $request->string('internal_notes')->value() ?: null,
            isFeatured:           $request->boolean('is_featured', false),
            isDigital:            $request->boolean('is_digital', false),
            isPublishable:        $request->boolean('is_publishable', false),
            season:               $request->string('season')->value() ?: null,
            launchDate:           $request->string('launch_date')->value() ?: null,
            seo:                  $request->array('seo') ?: null,
            location:             $request->string('location')->value() ?: null,
            barcodes:             $request->has('barcodes') ? $request->array('barcodes') : null,
        );
    }
}
