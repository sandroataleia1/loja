<?php

declare(strict_types=1);

namespace App\Modules\Sales\Models;

use App\Core\Auth\Models\User;
use App\Modules\Finance\Models\PaymentCondition;
use App\Modules\Finance\Models\PaymentMethod;
use App\Modules\Inventory\Models\Store;
use App\Modules\Fiscal\Enums\FiscalModeEnum;
use App\Modules\Sales\Enums\FiscalStatusEnum;
use App\Modules\Sales\Enums\SalesChannelEnum;
use App\Modules\Sales\Enums\SaleStatusEnum;
use App\Shared\Models\BaseModel;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Sale extends BaseModel
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    protected static function newFactory(): SaleFactory
    {
        return SaleFactory::new();
    }

    protected $table = 'sales';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'session_id',
        'customer_id',
        'payment_method_id',
        'payment_condition_id',
        'seller_id',
        'status',
        'subtotal_cents',
        'discount_total_cents',
        'tax_total_cents',
        'total_cents',
        'sync_uuid',
        'fiscal_status',
        'fiscal_key',
        'fiscal_mode',
        'sales_channel',
        'fiscal_data',
        'reference_sale_id',
        'notes',
        'metadata',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'status'               => SaleStatusEnum::class,
            'fiscal_status'        => FiscalStatusEnum::class,
            'fiscal_mode'          => FiscalModeEnum::class,
            'sales_channel'        => SalesChannelEnum::class,
            'subtotal_cents'       => 'integer',
            'discount_total_cents' => 'integer',
            'tax_total_cents'      => 'integer',
            'total_cents'          => 'integer',
            'fiscal_data'          => 'array',
            'metadata'             => 'array',
            'completed_at'         => 'datetime',
            'cancelled_at'         => 'datetime',
        ]);
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public function paidAmountCents(): int
    {
        return (int) $this->payments()->where('status', 'paid')->sum('amount_cents');
    }

    public function remainingAmountCents(): int
    {
        return max(0, $this->total_cents - $this->paidAmountCents());
    }

    public function isFullyPaid(): bool
    {
        return $this->paidAmountCents() >= $this->total_cents;
    }

    public function recalculateTotals(): void
    {
        $this->subtotal_cents       = $this->items()->sum('total_cents');
        $this->total_cents          = max(0, $this->subtotal_cents - $this->discount_total_cents);
        $this->save();
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'uuid');
    }

    public function paymentCondition(): BelongsTo
    {
        return $this->belongsTo(PaymentCondition::class, 'payment_condition_id', 'uuid');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'uuid');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashRegisterSession::class, 'session_id', 'uuid');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id', 'uuid');
    }

    public function referenceSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'reference_sale_id', 'uuid');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'sale_id', 'uuid');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'sale_id', 'uuid');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(SaleCommission::class, 'sale_id', 'uuid');
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(SaleDiscount::class, 'sale_id', 'uuid');
    }
}
