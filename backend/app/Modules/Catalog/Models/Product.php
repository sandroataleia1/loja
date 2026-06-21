<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Enums\ProductStatusEnum;
use App\Modules\Catalog\Enums\ProductTypeEnum;
use App\Modules\Catalog\Enums\ProductVisibilityEnum;
use App\Modules\Catalog\Enums\UnitOfMeasureEnum;
use App\Modules\Fiscal\Enums\ProductOriginEnum;
use App\Modules\Media\Models\MediaAsset;
use App\Shared\Models\BaseModel;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

final class Product extends BaseModel
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
    protected $table = 'catalog_products';

    protected $fillable = [
        'tenant_id',
        'brand_id',
        'collection_id',
        'grid_id',
        'name',
        'slug',
        'description',
        'short_description',
        'marketing_description',
        'internal_notes',
        'type',
        'unit_of_measure',
        'ncm',
        'cest',
        'cfop_default',
        'origin_code',
        'status',
        'visibility',
        'base_price',
        'cost_price',
        'season',
        'launch_date',
        'is_featured',
        'is_digital',
        'is_publishable',
        'seo',
        'metadata',
        // Analytics foundation — populated by listeners, not user input
        'sales_velocity',
        'days_without_sale',
        'stock_age',
        'last_sale_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'type'              => ProductTypeEnum::class,
            'unit_of_measure'   => UnitOfMeasureEnum::class,
            'origin_code'       => ProductOriginEnum::class,
            'status'            => ProductStatusEnum::class,
            'visibility'        => ProductVisibilityEnum::class,
            'base_price'        => 'decimal:2',
            'cost_price'        => 'decimal:2',
            'is_featured'       => 'boolean',
            'is_digital'        => 'boolean',
            'is_publishable'    => 'boolean',
            'launch_date'       => 'date',
            'last_sale_at'      => 'datetime',
            'sales_velocity'    => 'decimal:4',
            'days_without_sale' => 'integer',
            'stock_age'         => 'integer',
            'seo'               => 'array',
            'metadata'          => 'array',
        ]);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'uuid');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'catalog_product_categories',
            'product_id',
            'category_id',
            'uuid',
            'uuid',
        )->withPivot('sort_order')->orderByPivot('sort_order');
    }

    /** Coleção editorial primária (BelongsTo — um produto, uma linha). */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(ProductCollection::class, 'collection_id', 'uuid');
    }

    /**
     * Coleções comerciais (many-to-many — Black Friday, Liquidação, etc.).
     * Um produto pode participar de múltiplas campanhas simultaneamente.
     */
    public function commercialCollections(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductCollection::class,
            'catalog_collection_products',
            'product_id',
            'collection_id',
            'uuid',
            'uuid',
        )->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function grid(): BelongsTo
    {
        return $this->belongsTo(Grid::class, 'grid_id', 'uuid');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class, 'product_id', 'uuid')
            ->orderBy('sort_order');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function defaultVariant(): HasMany
    {
        return $this->variants()->where('is_default', true)->limit(1);
    }

    /** Imagens legadas (URL-based, catalog_images). */
    public function images(): MorphMany
    {
        return $this->morphMany(ProductImage::class, 'imageable', 'imageable_type', 'imageable_id', 'uuid')
            ->orderBy('sort_order');
    }

    /** Mídia desacoplada (MediaAsset domain). */
    public function media(): BelongsToMany
    {
        return $this->belongsToMany(
            MediaAsset::class,
            'product_media',
            'product_id',
            'media_asset_id',
            'uuid',
            'uuid',
        )->using(ProductMedia::class)
            ->withPivot(['position', 'is_primary'])
            ->orderByPivot('position');
    }

    public function primaryMedia(): BelongsToMany
    {
        return $this->media()->wherePivot('is_primary', true)->limit(1);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            Tag::class,
            'catalog_taggables',
            'taggable_id',
            'tag_id',
            'uuid',
            'uuid',
        )->wherePivot('taggable_type', self::class);
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class, 'product_id', 'uuid')
            ->orderByDesc('changed_at');
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public function isVariable(): bool
    {
        return $this->type === ProductTypeEnum::Variable;
    }

    public function isPublished(): bool
    {
        return $this->status === ProductStatusEnum::Active;
    }

    public function isReadyToPublish(): bool
    {
        return $this->is_publishable
            && $this->status->isPublishable()
            && filled($this->description);
    }
}
