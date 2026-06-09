<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Modules\Finance\Enums\FinancialInstallmentStatusEnum;
use App\Shared\Traits\HasUuid;
use Database\Factories\FinancialInstallmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Parcela de um lançamento financeiro.
 *
 * Não usa BaseModel nem BelongsToTenant — isolada pelo lançamento pai.
 * Tenant filtering é transitivo via FinancialEntry.
 */
final class FinancialInstallment extends Model
{
    /** @use HasFactory<FinancialInstallmentFactory> */
    use HasFactory;
    use HasUuid;

    protected $table = 'financial_installments';

    protected $primaryKey = 'uuid';

    protected $fillable = [
        'financial_entry_id',
        'installment_number',
        'due_date',
        'amount_cents',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status'             => FinancialInstallmentStatusEnum::class,
            'installment_number' => 'integer',
            'amount_cents'       => 'integer',
            'due_date'           => 'date',
            'paid_at'            => 'datetime',
            'created_at'         => 'datetime',
            'updated_at'         => 'datetime',
        ];
    }

    protected static function newFactory(): FinancialInstallmentFactory
    {
        return FinancialInstallmentFactory::new();
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(FinancialEntry::class, 'financial_entry_id', 'uuid');
    }
}
