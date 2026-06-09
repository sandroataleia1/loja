<?php

declare(strict_types=1);

namespace App\Modules\Conditional\Models;

use App\Modules\Catalog\Models\Variant;
use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ConditionalItem extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use HasUuid;

    protected $table      = 'conditional_items';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'conditional_id',
        'variant_id',
        'quantity',
        'unit_price_cents',
        'returned_quantity',
        'sold_quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity'          => 'integer',
            'unit_price_cents'  => 'integer',
            'returned_quantity' => 'integer',
            'sold_quantity'     => 'integer',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function conditional(): BelongsTo
    {
        return $this->belongsTo(Conditional::class, 'conditional_id', 'uuid');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'variant_id', 'uuid');
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public function pendingQuantity(): int
    {
        return $this->quantity - $this->returned_quantity - $this->sold_quantity;
    }

    public function totalCents(): int
    {
        return $this->quantity * $this->unit_price_cents;
    }
}
