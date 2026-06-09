<?php

declare(strict_types=1);

namespace App\Modules\Sales\Models;

use App\Core\Auth\Models\User;
use App\Modules\Customers\Models\Customer;
use App\Modules\Inventory\Models\Store;
use App\Modules\Sales\Enums\ReturnReasonEnum;
use App\Modules\Sales\Enums\SaleReturnStatusEnum;
use App\Shared\Models\BaseModel;
use Database\Factories\SaleReturnFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SaleReturn extends BaseModel
{
    /** @use HasFactory<SaleReturnFactory> */
    use HasFactory;

    protected static function newFactory(): SaleReturnFactory
    {
        return SaleReturnFactory::new();
    }

    protected $table = 'sale_returns';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'original_sale_id',
        'customer_id',
        'processed_by',
        'code',
        'status',
        'return_type',
        'reason',
        'notes',
        'refund_amount_cents',
        'refund_method',
        'stock_restocked',
        'financial_reversed',
        'processed_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'status'              => SaleReturnStatusEnum::class,
            'reason'              => ReturnReasonEnum::class,
            'refund_amount_cents' => 'integer',
            'stock_restocked'     => 'boolean',
            'financial_reversed'  => 'boolean',
            'processed_at'        => 'datetime',
        ]);
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public function isExchange(): bool
    {
        return $this->return_type === 'exchange';
    }

    public function totalReturnedQuantity(): int
    {
        return (int) $this->items()->sum('quantity_returned');
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function originalSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'original_sale_id', 'uuid');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'uuid');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'uuid');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by', 'uuid');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class, 'sale_return_id', 'uuid');
    }
}
