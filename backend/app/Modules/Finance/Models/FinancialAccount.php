<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Modules\Finance\Enums\FinancialAccountTypeEnum;
use App\Modules\Finance\Enums\FinancialEntryStatusEnum;
use App\Modules\Finance\Enums\FinancialEntryTypeEnum;
use App\Modules\Inventory\Models\Store;
use App\Shared\Models\BaseModel;
use Database\Factories\FinancialAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FinancialAccount extends BaseModel
{
    /** @use HasFactory<FinancialAccountFactory> */
    use HasFactory;

    protected $table = 'financial_accounts';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'code',
        'name',
        'type',
        'is_active',
        'current_balance_cents',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'type'                  => FinancialAccountTypeEnum::class,
            'is_active'             => 'boolean',
            'current_balance_cents' => 'integer',
        ]);
    }

    protected static function newFactory(): FinancialAccountFactory
    {
        return FinancialAccountFactory::new();
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    /**
     * Saldo computado on-demand somando lançamentos pagos.
     * Usado para auditoria/reconciliação — não para exibição diária (use current_balance_cents).
     */
    public function computedBalanceCents(): int
    {
        $income  = (int) $this->entries()
            ->where('status', FinancialEntryStatusEnum::Paid->value)
            ->where('type', FinancialEntryTypeEnum::Income->value)
            ->sum('amount_cents');

        $expense = (int) $this->entries()
            ->where('status', FinancialEntryStatusEnum::Paid->value)
            ->where('type', FinancialEntryTypeEnum::Expense->value)
            ->sum('amount_cents');

        return $income - $expense;
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'uuid');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(FinancialEntry::class, 'financial_account_id', 'uuid');
    }

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(FinancialTransfer::class, 'origin_account_id', 'uuid');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(FinancialTransfer::class, 'destination_account_id', 'uuid');
    }
}
