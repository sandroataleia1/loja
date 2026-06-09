<?php

declare(strict_types=1);

namespace App\Modules\Sales\Models;

use App\Core\Auth\Models\User;
use App\Modules\Inventory\Models\Store;
use App\Modules\Sales\Enums\CashMovementTypeEnum;
use App\Modules\Sales\Enums\CashRegisterSessionStatusEnum;
use App\Modules\Sales\Enums\PaymentMethodEnum;
use App\Modules\Sales\Enums\PaymentStatusEnum;
use App\Shared\Models\BaseModel;
use Database\Factories\CashRegisterSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CashRegisterSession extends BaseModel
{
    /** @use HasFactory<CashRegisterSessionFactory> */
    use HasFactory;

    protected static function newFactory(): CashRegisterSessionFactory
    {
        return CashRegisterSessionFactory::new();
    }

    protected $table = 'cash_register_sessions';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'cash_register_id',
        'user_id',
        'closed_by',
        'code',
        'status',
        'opening_amount_cents',
        'closing_amount_cents',
        'expected_balance_cents',
        'difference_amount_cents',
        'difference_reason',
        'notes',
        'opened_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'status'                  => CashRegisterSessionStatusEnum::class,
            'opening_amount_cents'    => 'integer',
            'closing_amount_cents'    => 'integer',
            'expected_balance_cents'  => 'integer',
            'difference_amount_cents' => 'integer',
            'opened_at'               => 'datetime',
            'closed_at'               => 'datetime',
        ]);
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public function isOpen(): bool
    {
        return $this->status === CashRegisterSessionStatusEnum::Open;
    }

    /**
     * Saldo esperado = abertura + suprimentos - sangrias + vendas em dinheiro
     *
     * Computed on demand; stored at close time in expected_balance_cents.
     */
    public function computeExpectedBalanceCents(): int
    {
        $supplyTotal     = $this->movements()
            ->where('type', CashMovementTypeEnum::Supply->value)
            ->sum('amount_cents');

        $withdrawalTotal = $this->movements()
            ->where('type', CashMovementTypeEnum::Withdrawal->value)
            ->sum('amount_cents');

        $cashSalesTotal  = $this->cashSalesTotal();

        return $this->opening_amount_cents + (int) $supplyTotal + $cashSalesTotal - (int) $withdrawalTotal;
    }

    /** Soma de pagamentos em dinheiro (cash) confirmados nesta sessão */
    public function cashSalesTotal(): int
    {
        return (int) $this->sales()
            ->join('payment_transactions', 'sales.uuid', '=', 'payment_transactions.sale_id')
            ->where('payment_transactions.method', PaymentMethodEnum::Cash->value)
            ->where('payment_transactions.status', PaymentStatusEnum::Paid->value)
            ->sum('payment_transactions.amount_cents');
    }

    /**
     * Agregado completo para o snapshot de fechamento.
     *
     * @return array{
     *     cash_total: int,
     *     pix_total: int,
     *     credit_card_total: int,
     *     debit_card_total: int,
     *     store_credit_total: int,
     *     withdrawal_total: int,
     *     supply_total: int,
     * }
     */
    public function computeAggregates(): array
    {
        $paymentBase = fn (string $method): int => (int) $this->sales()
            ->join('payment_transactions', 'sales.uuid', '=', 'payment_transactions.sale_id')
            ->where('payment_transactions.method', $method)
            ->where('payment_transactions.status', PaymentStatusEnum::Paid->value)
            ->sum('payment_transactions.amount_cents');

        return [
            'cash_total'         => $paymentBase(PaymentMethodEnum::Cash->value),
            'pix_total'          => $paymentBase(PaymentMethodEnum::Pix->value),
            'credit_card_total'  => $paymentBase(PaymentMethodEnum::CreditCard->value),
            'debit_card_total'   => $paymentBase(PaymentMethodEnum::DebitCard->value),
            'store_credit_total' => $paymentBase(PaymentMethodEnum::StoreCredit->value),
            'withdrawal_total'   => (int) $this->movements()
                ->where('type', CashMovementTypeEnum::Withdrawal->value)
                ->sum('amount_cents'),
            'supply_total'       => (int) $this->movements()
                ->where('type', CashMovementTypeEnum::Supply->value)
                ->sum('amount_cents'),
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'uuid');
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id', 'uuid');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'uuid');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by', 'uuid');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'session_id', 'uuid');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class, 'cash_register_session_id', 'uuid');
    }
}
