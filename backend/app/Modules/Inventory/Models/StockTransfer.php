<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Core\Auth\Models\User;
use App\Modules\Inventory\Enums\TransferStatusEnum;
use App\Shared\Models\BaseModel;
use Database\Factories\StockTransferFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class StockTransfer extends BaseModel
{
    /** @use HasFactory<StockTransferFactory> */
    use HasFactory;

    protected static function newFactory(): StockTransferFactory
    {
        return StockTransferFactory::new();
    }

    protected $table = 'stock_transfers';

    protected $fillable = [
        'tenant_id',
        'origin_store_id',
        'destination_store_id',
        'status',
        'notes',
        'created_by',
        'dispatched_by',
        'received_by',
        'dispatched_at',
        'received_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'status'       => TransferStatusEnum::class,
            'dispatched_at' => 'datetime',
            'received_at'   => 'datetime',
            'cancelled_at'  => 'datetime',
        ]);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function originStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'origin_store_id', 'uuid');
    }

    public function destinationStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'destination_store_id', 'uuid');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class, 'transfer_id', 'uuid');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'uuid');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by', 'uuid');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by', 'uuid');
    }
}
