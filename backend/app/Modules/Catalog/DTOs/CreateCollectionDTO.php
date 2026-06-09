<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class CreateCollectionDTO extends BaseDTO
{
    public function __construct(
        public string  $name,
        public string  $slug,
        public ?string $description,
        public ?string $coverUrl,
        public ?string $startsAt,
        public ?string $endsAt,
        public bool    $isActive,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            name:        $request->string('name')->toString(),
            slug:        Str::slug($request->string('name')->toString()),
            description: $request->string('description')->value() ?: null,
            coverUrl:    $request->string('cover_url')->value() ?: null,
            startsAt:    $request->string('starts_at')->value() ?: null,
            endsAt:      $request->string('ends_at')->value() ?: null,
            isActive:    $request->boolean('is_active', true),
        );
    }
}
