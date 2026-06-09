<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class CreateVariantDTO extends BaseDTO
{
    public function __construct(
        public string  $productId,
        public string  $sku,
        public int     $priceCents,
        public ?string $name,
        public ?string $barcode,
        public ?string $gtin,
        public ?int    $costCents,
        public ?int    $compareAtCents,
        public ?int    $weightG,
        public ?array  $dimensions,
        public bool    $isActive,
        public bool    $isDefault,
        public int     $sortOrder,
        /** @var string[] */
        public array   $attributeIds,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            productId:      $request->string('product_id')->toString(),
            sku:            strtoupper(trim($request->string('sku')->toString())),
            priceCents:     $request->integer('price_cents'),
            name:           $request->string('name')->value() ?: null,
            barcode:        $request->string('barcode')->value() ?: null,
            gtin:           $request->string('gtin')->value() ?: null,
            costCents:      $request->has('cost_cents') ? $request->integer('cost_cents') : null,
            compareAtCents: $request->has('compare_at_cents') ? $request->integer('compare_at_cents') : null,
            weightG:        $request->has('weight_g') ? $request->integer('weight_g') : null,
            dimensions:     $request->array('dimensions') ?: null,
            isActive:       $request->boolean('is_active', true),
            isDefault:      $request->boolean('is_default', false),
            sortOrder:      $request->integer('sort_order', 0),
            attributeIds:   $request->array('attribute_ids'),
        );
    }
}
