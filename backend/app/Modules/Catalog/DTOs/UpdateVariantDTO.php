<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class UpdateVariantDTO extends BaseDTO
{
    public function __construct(
        public ?string $sku              = null,
        public ?int    $priceCents       = null,
        public ?string $name             = null,
        public ?string $barcode          = null,
        public ?string $gtin             = null,
        public ?int    $costCents        = null,
        public ?int    $compareAtCents   = null,
        public ?int    $weightG          = null,
        public ?array  $dimensions       = null,
        public ?bool   $isActive         = null,
        public ?bool   $isDefault        = null,
        public ?int    $sortOrder        = null,
        /** @var string[]|null */
        public ?array  $attributeIds     = null,
        public ?string $ncm              = null,
        public ?string $cest             = null,
        public ?string $cfopDefault      = null,
        public ?int    $originCode       = null,
        public ?string $taxProfileId     = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            sku:            $request->has('sku')
                ? strtoupper(trim($request->string('sku')->toString()))
                : null,
            priceCents:     $request->has('price_cents')     ? $request->integer('price_cents')           : null,
            name:           $request->has('name')            ? ($request->string('name')->value() ?: null) : null,
            barcode:        $request->has('barcode')         ? ($request->string('barcode')->value() ?: null) : null,
            gtin:           $request->has('gtin')            ? ($request->string('gtin')->value() ?: null) : null,
            costCents:      $request->has('cost_cents')      ? $request->integer('cost_cents')             : null,
            compareAtCents: $request->has('compare_at_cents')? $request->integer('compare_at_cents')       : null,
            weightG:        $request->has('weight_g')        ? $request->integer('weight_g')               : null,
            dimensions:     $request->has('dimensions')      ? $request->array('dimensions')               : null,
            isActive:       $request->has('is_active')       ? $request->boolean('is_active')              : null,
            isDefault:      $request->has('is_default')      ? $request->boolean('is_default')             : null,
            sortOrder:      $request->has('sort_order')      ? $request->integer('sort_order')             : null,
            attributeIds:   $request->has('attribute_ids')   ? $request->array('attribute_ids')            : null,
            ncm:            $request->has('ncm')             ? ($request->string('ncm')->value() ?: null)  : null,
            cest:           $request->has('cest')            ? ($request->string('cest')->value() ?: null) : null,
            cfopDefault:    $request->has('cfop_default')    ? ($request->string('cfop_default')->value() ?: null) : null,
            originCode:     $request->has('origin_code')     ? $request->integer('origin_code')            : null,
            taxProfileId:   $request->has('tax_profile_id')  ? ($request->string('tax_profile_id')->value() ?: null) : null,
        );
    }
}
