<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Core\Auth\Models\User;
use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Database\Factories\FinancialTransferFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Transferência entre contas financeiras — imutável.
 *
 * Não usa SoftDeletes nem BaseModel. Transferências nunca são deletadas.
 * Para reverter, cria-se nova transferência no sentido oposto.
 */
final class FinancialTransfer extends Model
{
    /** @use HasFactory<FinancialTransferFactory> */
    use HasFactory;
    use BelongsToTenant;
    use HasUuid;

    protected $table = 'financial_transfers';

    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'origin_account_id',
        'destination_account_id',
        'amount_cents',
        'transferred_at',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents'    => 'integer',
            'transferred_at'  => 'datetime',
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
        ];
    }

    protected static function newFactory(): FinancialTransferFactory
    {
        return FinancialTransferFactory::new();
    }

    public function originAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'origin_account_id', 'uuid');
    }

    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'destination_account_id', 'uuid');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'uuid');
    }
}
