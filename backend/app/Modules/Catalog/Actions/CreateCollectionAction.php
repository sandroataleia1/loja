<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\CreateCollectionDTO;
use App\Modules\Catalog\Models\ProductCollection;
use App\Shared\Exceptions\ConflictException;

final readonly class CreateCollectionAction
{
    public function execute(CreateCollectionDTO $dto): ProductCollection
    {
        if (ProductCollection::where('slug', $dto->slug)->exists()) {
            throw new ConflictException("Coleção com slug '{$dto->slug}' já existe neste tenant.");
        }

        return ProductCollection::create([
            'name'        => $dto->name,
            'slug'        => $dto->slug,
            'description' => $dto->description,
            'cover_url'   => $dto->coverUrl,
            'starts_at'   => $dto->startsAt,
            'ends_at'     => $dto->endsAt,
            'is_active'   => $dto->isActive,
        ]);
    }
}
