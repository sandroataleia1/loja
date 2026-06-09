<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Attribute extends BaseModel
{
    protected $table = 'catalog_attributes';

    protected $fillable = [
        'tenant_id',
        'attribute_group_id',
        'value',
        'label',
        'color_hex',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'sort_order' => 'integer',
            'metadata'   => 'array',
        ]);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AttributeGroup::class, 'attribute_group_id', 'uuid');
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(
            Variant::class,
            'catalog_variant_attributes',
            'attribute_id',
            'variant_id',
            'uuid',
            'uuid',
        )->withPivot('sort_order');
    }

    public function grids(): BelongsToMany
    {
        return $this->belongsToMany(
            Grid::class,
            'catalog_grid_items',
            'attribute_id',
            'grid_id',
            'uuid',
            'uuid',
        )->withPivot('sort_order');
    }
}
