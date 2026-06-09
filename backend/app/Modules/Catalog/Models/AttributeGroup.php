<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Enums\AttributeTypeEnum;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AttributeGroup extends BaseModel
{
    protected $table = 'catalog_attribute_groups';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'type',
        'sort_order',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'type'       => AttributeTypeEnum::class,
            'sort_order' => 'integer',
        ]);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class, 'attribute_group_id', 'uuid')
            ->orderBy('sort_order');
    }

    public function grids(): HasMany
    {
        return $this->hasMany(Grid::class, 'attribute_group_id', 'uuid');
    }
}
