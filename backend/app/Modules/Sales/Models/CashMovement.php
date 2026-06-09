<?php

declare(strict_types=1);

namespace App\Modules\Sales\Models;

use App\Core\Auth\Models\User;
use App\Modules\Inventory\Models\Store;
use App\Modules\Sales\Enums\CashMovementTypeEnum;
use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Database\Factories\CashMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimentação de caixa — imutável.
 *
 * Não usa BaseModel: sem SoftDeletes (registros nunca deletados) e sem updated_at
 * (registros nunca atualizados).
 */
final class CashMovement extends Model
{
    /** @use HasFactory<CashMovementFactory> */
    use HasFactory;
    use BelongsToTenant;
    use HasUuid;

    public const UPDATED_AT = null;

    protected $table      = 'cash_movements';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'cash_register_session_id',
        'type',
        'amount_cents',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type'         => CashMovementTypeEnum::class,
            'amount_cents' => 'integer',
            'created_at'   => 'datetime',
        ];
    }

    protected static function newFactory(): CashMovementFactory
    {
        return CashMovementFactory::new();
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashRegisterSession::class, 'cash_register_session_id', 'uuid');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'uuid');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'uuid');
    }
}
