<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Shared\Models\BaseModel;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

final class Category extends BaseModel
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
    protected $table = 'catalog_categories';

    protected $fillable = [
        'tenant_id',
        'code',
        'parent_id',
        'name',
        'slug',
        'description',
        'image_url',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
            'metadata'   => 'array',
        ]);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id', 'uuid');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id', 'uuid');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'catalog_product_categories',
            'category_id',
            'product_id',
            'uuid',
            'uuid',
        )->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(ProductImage::class, 'imageable', 'imageable_type', 'imageable_id', 'uuid');
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }
}
