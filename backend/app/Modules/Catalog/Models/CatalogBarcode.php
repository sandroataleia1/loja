<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Enums\BarcodeTypeEnum;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CatalogBarcode extends BaseModel
{
    protected $table = 'catalog_barcodes';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'barcode_type',
        'value',
        'is_primary',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'barcode_type' => BarcodeTypeEnum::class,
            'is_primary'   => 'boolean',
        ]);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'uuid');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'variant_id', 'uuid');
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    public function scopeByType(Builder $query, BarcodeTypeEnum $type): Builder
    {
        return $query->where('barcode_type', $type->value);
    }
}
