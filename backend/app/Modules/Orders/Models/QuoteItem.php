<?php

declare(strict_types=1);

namespace App\Modules\Orders\Models;

use App\Shared\Models\BaseModel;

final class QuoteItem extends BaseModel
{
    protected $table = 'quote_items';

    protected $fillable = [
        'quote_id',
        'product_variant_id',
        'name_snapshot',
        'sku_snapshot',
        'unit_of_measure',
        'quantity',
        'unit_price_cents',
        'discount_cents',
        'subtotal_cents',
        'sort_order',
        'notes',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'quantity'         => 'decimal:3',
            'unit_price_cents' => 'integer',
            'discount_cents'   => 'integer',
            'subtotal_cents'   => 'integer',
            'sort_order'       => 'integer',
        ]);
    }

    public function quote(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id', 'uuid');
    }

    public function unitPrice(): float { return $this->unit_price_cents / 100; }
    public function subtotal(): float  { return $this->subtotal_cents / 100; }
}
