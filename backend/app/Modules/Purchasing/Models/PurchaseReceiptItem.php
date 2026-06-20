<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Models;

use App\Modules\Catalog\Models\Variant;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PurchaseReceiptItem extends Model
{
    use HasUuid;

    protected $table      = 'purchase_receipt_items';
    protected $primaryKey = 'uuid';
    public    $incrementing = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'receipt_id', 'purchase_order_item_id', 'product_variant_id',
        'quantity_received', 'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity_received' => 'decimal:3',
            'unit_cost'         => 'decimal:2',
        ];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceipt::class, 'receipt_id', 'uuid');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id', 'uuid');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'product_variant_id', 'uuid');
    }
}
