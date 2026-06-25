<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CatalogKitItem extends BaseModel
{
    protected $table = 'catalog_kit_items';

    protected $fillable = [
        'tenant_id',
        'kit_product_id',
        'component_product_id',
        'component_variant_id',
        'unit_id',
        'quantity',
        'sort_order',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'quantity'   => 'decimal:4',
            'sort_order' => 'integer',
        ]);
    }

    protected static function booted(): void
    {
        static::creating(function (self $item): void {
            if ($item->kit_product_id === $item->component_product_id) {
                throw new \DomainException('Um kit não pode ter a si mesmo como componente.');
            }
        });
    }

    public function kit(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'kit_product_id', 'uuid');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_product_id', 'uuid');
    }

    public function componentVariant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'component_variant_id', 'uuid');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'uuid');
    }
}
