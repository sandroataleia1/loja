<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\CreateGridDTO;
use App\Modules\Catalog\Models\Attribute;
use App\Modules\Catalog\Models\AttributeGroup;
use App\Modules\Catalog\Models\Grid;
use App\Shared\Exceptions\BusinessException;
use App\Shared\Exceptions\NotFoundException;
use Illuminate\Support\Facades\DB;

final readonly class CreateGridAction
{
    public function execute(CreateGridDTO $dto): Grid
    {
        $group = AttributeGroup::where('uuid', $dto->attributeGroupId)->first();

        if ($group === null) {
            throw new NotFoundException("Grupo de atributo '{$dto->attributeGroupId}' não encontrado.");
        }

        $attributes = Attribute::whereIn('uuid', $dto->attributeIds)
            ->where('attribute_group_id', $dto->attributeGroupId)
            ->get();

        if ($attributes->count() !== count($dto->attributeIds)) {
            throw new BusinessException('Um ou mais atributos não pertencem ao grupo informado.');
        }

        return DB::transaction(function () use ($dto, $attributes): Grid {
            $grid = Grid::create([
                'attribute_group_id' => $dto->attributeGroupId,
                'name'               => $dto->name,
                'description'        => $dto->description,
            ]);

            $pivot = $attributes->map(fn (Attribute $a, int $i) => [
                'grid_id'      => $grid->uuid,
                'attribute_id' => $a->uuid,
                'sort_order'   => $i,
            ])->all();

            DB::table('catalog_grid_items')->insert($pivot);

            return $grid->load('attributes');
        });
    }
}
