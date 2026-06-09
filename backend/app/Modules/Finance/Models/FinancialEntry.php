<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Modules\Finance\Enums\FinancialEntryStatusEnum;
use App\Modules\Finance\Enums\FinancialEntryTypeEnum;
use App\Modules\Finance\Enums\FinancialInstallmentStatusEnum;
use App\Modules\Finance\Enums\ReconciliationStatusEnum;
use App\Modules\Inventory\Models\Store;
use App\Shared\Traits\Auditable;
use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Database\Factories\FinancialEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lançamento financeiro — nunca deletado.
 *
 * Não usa SoftDeletes: cancelamentos ficam com status = CANCELLED.
 * Usa BaseModel sem SoftDeletes via composição manual dos traits necessários.
 */
final class FinancialEntry extends Model
{
    /** @use HasFactory<FinancialEntryFactory> */
    use HasFactory;
    use Auditable;
    use BelongsToTenant;
    use HasUuid;

    protected $table = 'financial_entries';

    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'type',
        'category_id',
        'financial_account_id',
        'amount_cents',
        'due_date',
        'paid_at',
        'status',
        'description',
        'reference_type',
        'reference_id',
        'reconciliation_status',
        'external_reference',
    ];

    protected function casts(): array
    {
        return [
            'type'                  => FinancialEntryTypeEnum::class,
            'status'                => FinancialEntryStatusEnum::class,
            'reconciliation_status' => ReconciliationStatusEnum::class,
            'amount_cents'          => 'integer',
            'due_date'              => 'date',
            'paid_at'               => 'datetime',
            'created_at'            => 'datetime',
            'updated_at'            => 'datetime',
        ];
    }

    protected static function newFactory(): FinancialEntryFactory
    {
        return FinancialEntryFactory::new();
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public function hasInstallments(): bool
    {
        return $this->installments()->exists();
    }

    public function paidAmountCents(): int
    {
        return (int) $this->installments()
            ->where('status', FinancialInstallmentStatusEnum::Paid->value)
            ->sum('amount_cents');
    }

    public function isFullyPaid(): bool
    {
        if (! $this->hasInstallments()) {
            return $this->status === FinancialEntryStatusEnum::Paid;
        }

        return $this->installments()
            ->where('status', '!=', FinancialInstallmentStatusEnum::Paid->value)
            ->where('status', '!=', FinancialInstallmentStatusEnum::Cancelled->value)
            ->doesntExist();
    }

    public function isOverdue(): bool
    {
        return $this->status === FinancialEntryStatusEnum::Overdue
            || ($this->status === FinancialEntryStatusEnum::Pending && $this->due_date < today());
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id', 'uuid');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class, 'category_id', 'uuid');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'uuid');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(FinancialInstallment::class, 'financial_entry_id', 'uuid')
            ->orderBy('installment_number');
    }
}
