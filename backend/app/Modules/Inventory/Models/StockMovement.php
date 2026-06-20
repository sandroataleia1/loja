<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Core\Auth\Models\User;
use App\Modules\Catalog\Models\Variant;
use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimentação de estoque — imutável por design.
 * Sem SoftDeletes, sem updated_at: o registro é escrito uma vez e nunca alterado.
 * Reversão cria um novo movimento com tipo e quantidade opostos.
 */
final class StockMovement extends Model
{
    use BelongsToTenant;
    use HasUuid;

    public const UPDATED_AT = null;

    protected $table      = 'inventory_movements';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'variant_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reserved_before',
        'reserved_after',
        'reference_type',
        'reference_id',
        'created_by',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type'            => MovementTypeEnum::class,
            'quantity'        => 'decimal:3',
            'quantity_before' => 'decimal:3',
            'quantity_after'  => 'decimal:3',
            'reserved_before' => 'decimal:3',
            'reserved_after'  => 'decimal:3',
            'metadata'        => 'array',
            'created_at'      => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'uuid');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'variant_id', 'uuid');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'uuid');
    }
}
