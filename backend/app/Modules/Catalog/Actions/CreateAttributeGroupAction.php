<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\CreateAttributeGroupDTO;
use App\Modules\Catalog\Models\AttributeGroup;
use App\Shared\Exceptions\ConflictException;

final readonly class CreateAttributeGroupAction
{
    public function execute(CreateAttributeGroupDTO $dto): AttributeGroup
    {
        if (AttributeGroup::where('slug', $dto->slug)->exists()) {
            throw new ConflictException("Grupo de atributo '{$dto->slug}' já existe nesta empresa.");
        }

        return AttributeGroup::create([
            'name'       => $dto->name,
            'slug'       => $dto->slug,
            'type'       => $dto->type,
            'sort_order' => $dto->sortOrder,
        ]);
    }
}
