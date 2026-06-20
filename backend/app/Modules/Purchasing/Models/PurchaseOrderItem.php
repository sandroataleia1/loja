<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Models;

use App\Modules\Catalog\Models\Variant;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PurchaseOrderItem extends Model
{
    use HasUuid;

    protected $table      = 'purchase_order_items';
    protected $primaryKey = 'uuid';
    public    $incrementing = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'purchase_order_id', 'product_variant_id',
        'quantity', 'unit_cost', 'received_quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity'          => 'decimal:3',
            'unit_cost'         => 'decimal:2',
            'total_cost'        => 'decimal:3',
            'received_quantity' => 'decimal:3',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'uuid');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'product_variant_id', 'uuid');
    }

    public function pendingQuantity(): float
    {
        return (float) $this->quantity - (float) $this->received_quantity;
    }
}
