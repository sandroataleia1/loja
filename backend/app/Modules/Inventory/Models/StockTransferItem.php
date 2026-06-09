<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Catalog\Models\Variant;
use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Database\Factories\StockTransferItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StockTransferItem extends Model
{
    /** @use HasFactory<StockTransferItemFactory> */
    use BelongsToTenant;
    use HasFactory;
    use HasUuid;

    protected static function newFactory(): StockTransferItemFactory
    {
        return StockTransferItemFactory::new();
    }

    protected $table      = 'stock_transfer_items';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'transfer_id',
        'variant_id',
        'quantity_requested',
        'quantity_sent',
        'quantity_received',
    ];

    protected function casts(): array
    {
        return [
            'quantity_requested' => 'integer',
            'quantity_sent'      => 'integer',
            'quantity_received'  => 'integer',
            'created_at'         => 'datetime',
            'updated_at'         => 'datetime',
        ];
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'transfer_id', 'uuid');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'variant_id', 'uuid');
    }
}
