<?php

declare(strict_types=1);

namespace App\Modules\Sales\Models;

use App\Modules\Catalog\Models\Variant;
use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Database\Factories\SaleItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SaleItem extends Model
{
    /** @use HasFactory<SaleItemFactory> */
    use BelongsToTenant;
    use HasFactory;
    use HasUuid;

    protected static function newFactory(): SaleItemFactory
    {
        return SaleItemFactory::new();
    }

    protected $table      = 'sale_items';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'sale_id',
        'product_variant_id',
        'sku_snapshot',
        'name_snapshot',
        'attributes_snapshot',
        'quantity',
        'unit_price_cents',
        'cost_price_cents',
        'discount_amount_cents',
        'subtotal_cents',
        'total_cents',
    ];

    protected function casts(): array
    {
        return [
            'attributes_snapshot'   => 'array',
            'quantity'              => 'integer',
            'unit_price_cents'      => 'integer',
            'cost_price_cents'      => 'integer',
            'discount_amount_cents' => 'integer',
            'subtotal_cents'        => 'integer',
            'total_cents'           => 'integer',
            'created_at'            => 'datetime',
            'updated_at'            => 'datetime',
        ];
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public static function buildFromVariantInput(array $input): array
    {
        $subtotal = $input['quantity'] * $input['unit_price_cents'];
        $discount = $input['discount_amount_cents'] ?? 0;

        return [
            'product_variant_id'    => $input['variant_id'] ?? null,
            'sku_snapshot'          => $input['sku_snapshot'],
            'name_snapshot'         => $input['name_snapshot'],
            'attributes_snapshot'   => $input['attributes_snapshot'] ?? [],
            'quantity'              => $input['quantity'],
            'unit_price_cents'      => $input['unit_price_cents'],
            'cost_price_cents'      => $input['cost_price_cents'] ?? null,
            'discount_amount_cents' => $discount,
            'subtotal_cents'        => $subtotal,
            'total_cents'           => $subtotal - $discount,
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'uuid');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'product_variant_id', 'uuid');
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(SaleDiscount::class, 'sale_item_id', 'uuid');
    }
}
