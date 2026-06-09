<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\CreateAttributeDTO;
use App\Modules\Catalog\Models\Attribute;
use App\Modules\Catalog\Models\AttributeGroup;
use App\Shared\Exceptions\ConflictException;
use App\Shared\Exceptions\NotFoundException;

final readonly class CreateAttributeAction
{
    public function execute(CreateAttributeDTO $dto): Attribute
    {
        $group = AttributeGroup::where('uuid', $dto->attributeGroupId)->first();

        if ($group === null) {
            throw new NotFoundException("Grupo de atributo '{$dto->attributeGroupId}' não encontrado.");
        }

        if (Attribute::where('attribute_group_id', $dto->attributeGroupId)->where('value', $dto->value)->exists()) {
            throw new ConflictException("Atributo '{$dto->value}' já existe neste grupo.");
        }

        return Attribute::create([
            'attribute_group_id' => $dto->attributeGroupId,
            'value'              => $dto->value,
            'label'              => $dto->label,
            'color_hex'          => $dto->colorHex,
            'sort_order'         => $dto->sortOrder,
        ]);
    }
}
