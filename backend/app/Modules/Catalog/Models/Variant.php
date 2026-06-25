<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Fiscal\Enums\ProductOriginEnum;
use App\Modules\Fiscal\Models\TaxProfile;
use App\Shared\Models\BaseModel;
use Database\Factories\VariantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

final class Variant extends BaseModel
{
    /** @use HasFactory<VariantFactory> */
    use HasFactory;

    protected static function newFactory(): VariantFactory
    {
        return VariantFactory::new();
    }

    protected $table = 'catalog_variants';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'ncm_code_id',
        'unit_id',
        'sku',
        'grid_combination',
        'name',
        'barcode',
        'gtin',
        'price_cents',
        'cost_cents',
        'compare_at_cents',
        'weight_g',
        'dimensions',
        'is_active',
        'is_default',
        'sort_order',
        'metadata',
        'ncm',
        'cest',
        'cfop_default',
        'origin_code',
        'tax_profile_id',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'grid_combination' => 'array',
            'price_cents'      => 'integer',
            'cost_cents'       => 'integer',
            'compare_at_cents' => 'integer',
            'weight_g'         => 'integer',
            'dimensions'       => 'array',
            'is_active'        => 'boolean',
            'is_default'       => 'boolean',
            'sort_order'       => 'integer',
            'metadata'         => 'array',
            'origin_code'      => ProductOriginEnum::class,
        ]);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'uuid');
    }

    public function ncmCode(): BelongsTo
    {
        return $this->belongsTo(NcmCode::class, 'ncm_code_id', 'uuid');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'uuid');
    }

    public function taxProfile(): BelongsTo
    {
        return $this->belongsTo(TaxProfile::class, 'tax_profile_id', 'uuid');
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(
            Attribute::class,
            'catalog_variant_attributes',
            'variant_id',
            'attribute_id',
            'uuid',
            'uuid',
        )->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(ProductImage::class, 'imageable', 'imageable_type', 'imageable_id', 'uuid')
            ->orderBy('sort_order');
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(CatalogBarcode::class, 'variant_id', 'uuid');
    }

    public function primaryBarcode(): ?CatalogBarcode
    {
        return $this->barcodes()->where('is_primary', true)->first();
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public function priceInReais(): float
    {
        return $this->price_cents / 100;
    }

    public function hasDiscount(): bool
    {
        return $this->compare_at_cents !== null && $this->compare_at_cents > $this->price_cents;
    }

    public function discountPercent(): int
    {
        if (! $this->hasDiscount()) {
            return 0;
        }

        return (int) round((1 - $this->price_cents / $this->compare_at_cents) * 100);
    }

    public function buildNameFromAttributes(): string
    {
        return $this->attributes
            ->map(fn (Attribute $a) => $a->label)
            ->join(' / ');
    }
}
