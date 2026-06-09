<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Core\Auth\Models\User;
use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Histórico imutável de preços por produto/variante.
 *
 * Registros NÃO são alterados ou excluídos — são auditoria.
 * IA promocional futura consumirá série temporal de preços
 * correlacionada com eventos de venda.
 */
final class ProductPriceHistory extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $table = 'catalog_price_history';
    protected $primaryKey = 'uuid';

    // Histórico é imutável — sem SoftDeletes, sem updated_at manual
    public $timestamps = true;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'old_price_cents',
        'new_price_cents',
        'changed_by',
        'changed_at',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'old_price_cents' => 'integer',
            'new_price_cents' => 'integer',
            'changed_at'      => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'uuid');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'variant_id', 'uuid');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by', 'uuid');
    }

    public function variationCents(): int
    {
        return $this->new_price_cents - $this->old_price_cents;
    }

    public function variationPercent(): float
    {
        if ($this->old_price_cents === 0) {
            return 0.0;
        }

        return round(($this->variationCents() / $this->old_price_cents) * 100, 2);
    }
}
