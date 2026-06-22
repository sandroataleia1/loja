<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class CreateGridDTO extends BaseDTO
{
    public function __construct(
        public string  $name,
        public ?string $description,
        /** @var string[] */
        public array   $attributeIds,
        /** Grupo primário — null indica grade multi-dimensional */
        public ?string $attributeGroupId = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            name:             $request->string('name')->toString(),
            description:      $request->string('description')->value() ?: null,
            attributeIds:     $request->array('attribute_ids'),
            attributeGroupId: $request->string('attribute_group_id')->value() ?: null,
        );
    }
}
