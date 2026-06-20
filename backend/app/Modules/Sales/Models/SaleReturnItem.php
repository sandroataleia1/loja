<?php

declare(strict_types=1);

namespace App\Modules\Sales\Models;

use App\Modules\Catalog\Models\Variant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SaleReturnItem extends Model
{
    protected $table = 'sale_return_items';

    protected $fillable = [
        'sale_return_id',
        'sale_item_id',
        'variant_id',
        'quantity_returned',
        'unit_price_cents',
        'refund_amount_cents',
        'condition',
        'condition_notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_returned'  => 'decimal:3',
            'unit_price_cents'   => 'integer',
            'refund_amount_cents' => 'integer',
            'created_at'         => 'datetime',
            'updated_at'         => 'datetime',
        ];
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class, 'sale_return_id', 'uuid');
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class, 'sale_item_id', 'uuid');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'variant_id', 'uuid');
    }
}
