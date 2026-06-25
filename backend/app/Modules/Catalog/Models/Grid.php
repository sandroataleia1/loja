<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Shared\Models\BaseModel;
use Database\Factories\GridFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Grid extends BaseModel
{
    /** @use HasFactory<GridFactory> */
    use HasFactory;

    protected $table = 'catalog_grids';

    protected static function newFactory(): GridFactory
    {
        return GridFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'attribute_group_id',
        'name',
        'description',
    ];

    public function attributeGroup(): BelongsTo
    {
        return $this->belongsTo(AttributeGroup::class, 'attribute_group_id', 'uuid');
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(
            Attribute::class,
            'catalog_grid_items',
            'grid_id',
            'attribute_id',
            'uuid',
            'uuid',
        )->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'grid_id', 'uuid');
    }
}
