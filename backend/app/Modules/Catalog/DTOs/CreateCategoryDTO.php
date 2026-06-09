<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class CreateCategoryDTO extends BaseDTO
{
    public function __construct(
        public string  $name,
        public string  $slug,
        public ?string $parentId,
        public ?string $description,
        public ?string $imageUrl,
        public int     $sortOrder,
        public bool    $isActive,
        public ?string $code = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            name:        $request->string('name')->toString(),
            slug:        Str::slug($request->string('name')->toString()),
            parentId:    $request->string('parent_id')->value() ?: null,
            description: $request->string('description')->value() ?: null,
            imageUrl:    $request->string('image_url')->value() ?: null,
            sortOrder:   $request->integer('sort_order', 0),
            isActive:    $request->boolean('is_active', true),
        );
    }
}
