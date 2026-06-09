<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Modules\Catalog\Enums\AttributeTypeEnum;
use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class CreateAttributeGroupDTO extends BaseDTO
{
    public function __construct(
        public string            $name,
        public string            $slug,
        public AttributeTypeEnum $type,
        public int               $sortOrder,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            name:      $request->string('name')->toString(),
            slug:      Str::slug($request->string('name')->toString()),
            type:      AttributeTypeEnum::from($request->string('type', 'text')->toString()),
            sortOrder: $request->integer('sort_order', 0),
        );
    }
}
