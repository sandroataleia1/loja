<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Catalog\Models\Variant;
use App\Shared\Models\BaseModel;
use Database\Factories\InventoryBalanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class InventoryBalance extends BaseModel
{
    /** @use HasFactory<InventoryBalanceFactory> */
    use HasFactory;

    protected static function newFactory(): InventoryBalanceFactory
    {
        return InventoryBalanceFactory::new();
    }

    protected $table = 'inventory_balances';

    /**
     * quantity e reserved_quantity são excluídos do fillable mass-assignment.
     * Toda mutação de saldo deve passar por AdjustStockAction ou ReserveStockAction,
     * que criam o InventoryMovement correspondente antes de alterar o saldo.
     * Nunca use InventoryBalance::update(['quantity' => x]) diretamente.
     */
    protected $fillable = [
        'tenant_id',
        'store_id',
        'variant_id',
    ];

    protected $guarded = [
        'quantity',
        'reserved_quantity',
    ];

    /**
     * Defaults de modelo: como quantity/reserved_quantity são $guarded, eles não
     * são preenchidos por create([...]). Sem estes defaults, um balance recém-criado
     * teria quantity NULL em memória — quebrando movimentos que leem quantity_before
     * logo após criar o saldo (ex.: recebimento de transferência em loja sem saldo).
     */
    protected $attributes = [
        'quantity'          => 0,
        'reserved_quantity' => 0,
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'quantity'          => 'integer',
            'reserved_quantity' => 'integer',
        ]);
    }

    // ── Computed ──────────────────────────────────────────────────────────────

    public function getAvailableQuantityAttribute(): int
    {
        return $this->quantity - $this->reserved_quantity;
    }

    // ── Controlled mutators (use only via Actions) ────────────────────────────

    public function applyQuantityChange(int $delta, int $reservedDelta = 0): void
    {
        $this->getConnection()->table($this->table)
            ->where($this->getKeyName(), $this->getKey())
            ->update([
                'quantity'          => $this->getConnection()->raw("quantity + {$delta}"),
                'reserved_quantity' => $this->getConnection()->raw("reserved_quantity + {$reservedDelta}"),
                'updated_at'        => now(),
            ]);

        $this->quantity          += $delta;
        $this->reserved_quantity += $reservedDelta;
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

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'variant_id', 'variant_id')
            ->where('store_id', $this->store_id);
    }
}
