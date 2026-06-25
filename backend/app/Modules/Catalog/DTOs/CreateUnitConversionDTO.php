<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use Illuminate\Http\Request;

final readonly class CreateUnitConversionDTO
{
    public function __construct(
        public string $fromUnit,
        public string $toUnit,
        public float $factor,
        public ?string $productId,
        public ?string $variantId,
        public ?string $notes,
        public bool $isActive,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            fromUnit:  $request->string('from_unit')->upper()->value(),
            toUnit:    $request->string('to_unit')->upper()->value(),
            factor:    (float) $request->input('factor'),
            productId: $request->input('product_id'),
            variantId: $request->input('variant_id'),
            notes:     $request->input('notes'),
            isActive:  $request->boolean('is_active', true),
        );
    }
}
