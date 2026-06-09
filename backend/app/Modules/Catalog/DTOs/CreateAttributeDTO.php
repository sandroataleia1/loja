<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class CreateAttributeDTO extends BaseDTO
{
    public function __construct(
        public string  $attributeGroupId,
        public string  $value,
        public string  $label,
        public ?string $colorHex,
        public int     $sortOrder,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            attributeGroupId: $request->string('attribute_group_id')->toString(),
            value:            $request->string('value')->toString(),
            label:            $request->string('label')->toString(),
            colorHex:         $request->string('color_hex')->value() ?: null,
            sortOrder:        $request->integer('sort_order', 0),
        );
    }
}
