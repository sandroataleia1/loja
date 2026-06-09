<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Shared\Models\BaseModel;
use Database\Factories\ProductCollectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

// Named ProductCollection to avoid conflict with Illuminate\Support\Collection
final class ProductCollection extends BaseModel
{
    /** @use HasFactory<ProductCollectionFactory> */
    use HasFactory;

    protected static function newFactory(): ProductCollectionFactory
    {
        return ProductCollectionFactory::new();
    }
    protected $table = 'catalog_collections';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'cover_url',
        'starts_at',
        'ends_at',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'starts_at' => 'datetime',
            'ends_at'   => 'datetime',
            'is_active' => 'boolean',
            'metadata'  => 'array',
        ]);
    }

    /** Produtos com esta como coleção editorial primária (BelongsTo no produto). */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'collection_id', 'uuid');
    }

    /** Produtos nesta coleção comercial (many-to-many — campanhas, sazonais). */
    public function commercialProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'catalog_collection_products',
            'collection_id',
            'product_id',
            'uuid',
            'uuid',
        )->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(ProductImage::class, 'imageable', 'imageable_type', 'imageable_id', 'uuid');
    }

    public function isActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at !== null && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at !== null && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }
}
