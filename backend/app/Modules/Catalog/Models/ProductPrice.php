<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductPrice extends BaseModel
{
    protected $table = 'product_prices';

    protected $fillable = [
        'tenant_id',
        'price_list_id',
        'product_id',
        'variant_id',
        'price_cents',
        'min_price_cents',
        'cost_price_cents',
        'packaging_price_cents',
        'packaging_qty',
        'valid_from',
        'valid_to',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'price_cents'          => 'integer',
            'min_price_cents'      => 'integer',
            'cost_price_cents'     => 'integer',
            'packaging_price_cents'=> 'integer',
            'packaging_qty'        => 'decimal:4',
            'valid_from'           => 'date',
            'valid_to'             => 'date',
        ]);
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_id', 'uuid');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'uuid');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'variant_id', 'uuid');
    }

    public function isCurrentlyValid(): bool
    {
        $today = now()->toDateString();

        $fromOk = $this->valid_from === null || $this->valid_from->toDateString() <= $today;
        $toOk   = $this->valid_to   === null || $this->valid_to->toDateString()   >= $today;

        return $fromOk && $toOk;
    }
}
