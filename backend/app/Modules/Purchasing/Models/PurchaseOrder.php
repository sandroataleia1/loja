<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Models;

use App\Modules\Purchasing\Enums\PurchaseOrderStatusEnum;
use App\Shared\Models\BaseModel;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PurchaseOrder extends BaseModel
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory;

    protected static function newFactory(): PurchaseOrderFactory
    {
        return PurchaseOrderFactory::new();
    }

    protected $table = 'purchase_orders';

    protected $fillable = [
        'tenant_id', 'store_id', 'supplier_id', 'code', 'status',
        'order_date', 'expected_delivery_date', 'subtotal', 'discount', 'total',
        'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'status'                 => PurchaseOrderStatusEnum::class,
            'order_date'             => 'date',
            'expected_delivery_date' => 'date',
            'subtotal'               => 'decimal:2',
            'discount'               => 'decimal:2',
            'total'                  => 'decimal:2',
        ]);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'uuid');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id', 'uuid');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseReceipt::class, 'purchase_order_id', 'uuid');
    }

    /** Total de unidades já recebidas em todos os recebimentos. */
    public function totalReceivedForVariant(string $variantId): int
    {
        return $this->items()
            ->where('product_variant_id', $variantId)
            ->value('received_quantity') ?? 0;
    }
}
