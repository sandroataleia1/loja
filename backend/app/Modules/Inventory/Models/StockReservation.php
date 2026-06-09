<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Catalog\Models\Variant;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reserva explícita de estoque.
 *
 * Substitui o contador opaco reserved_quantity em InventoryBalance
 * por registros rastreáveis de quem reservou o quê, até quando.
 *
 * Status transitions:
 * active → released  (cancelamento de reserva)
 * active → consumed  (venda completada, estoque baixado)
 * active → expired   (job de expiração)
 */
final class StockReservation extends BaseModel
{
    protected $table = 'stock_reservations';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'variant_id',
        'quantity',
        'status',
        'reference_type',
        'reference_id',
        'expires_at',
        'released_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'quantity'    => 'integer',
            'expires_at'  => 'datetime',
            'released_at' => 'datetime',
        ]);
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public function isActive(): bool   { return $this->status === 'active'; }
    public function isExpired(): bool  { return $this->expires_at !== null && $this->expires_at->isPast(); }

    public function release(): void
    {
        $this->update(['status' => 'released', 'released_at' => now()]);
        $this->decrementBalance();
    }

    public function consume(): void
    {
        $this->update(['status' => 'consumed', 'released_at' => now()]);
        $this->decrementBalance();
    }

    private function decrementBalance(): void
    {
        InventoryBalance::where('store_id', $this->store_id)
            ->where('variant_id', $this->variant_id)
            ->decrement('reserved_quantity', $this->quantity);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'variant_id', 'uuid');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'uuid');
    }
}
