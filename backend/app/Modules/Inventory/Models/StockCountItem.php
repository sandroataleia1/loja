<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Catalog\Models\Variant;
use App\Shared\Traits\HasUuid;
use Database\Factories\StockCountItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StockCountItem extends Model
{
    /** @use HasFactory<StockCountItemFactory> */
    use HasFactory;
    use HasUuid;

    protected static function newFactory(): StockCountItemFactory
    {
        return StockCountItemFactory::new();
    }

    protected $table      = 'stock_count_items';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'count_id',
        'variant_id',
        'system_quantity',
        'counted_quantity',
    ];

    protected function casts(): array
    {
        return [
            'system_quantity'  => 'decimal:3',
            'counted_quantity' => 'decimal:3',
            'difference'       => 'decimal:3',
            'created_at'       => 'datetime',
            'updated_at'       => 'datetime',
        ];
    }

    public function count(): BelongsTo
    {
        return $this->belongsTo(StockCount::class, 'count_id', 'uuid');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'variant_id', 'uuid');
    }
}
