<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Core\Auth\Models\User;
use App\Modules\Inventory\Enums\StockCountStatusEnum;
use App\Shared\Models\BaseModel;
use Database\Factories\StockCountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class StockCount extends BaseModel
{
    /** @use HasFactory<StockCountFactory> */
    use HasFactory;

    protected static function newFactory(): StockCountFactory
    {
        return StockCountFactory::new();
    }

    protected $table = 'stock_counts';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'status',
        'notes',
        'created_by',
        'committed_by',
        'started_at',
        'committed_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'status'       => StockCountStatusEnum::class,
            'started_at'   => 'datetime',
            'committed_at' => 'datetime',
        ]);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'uuid');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockCountItem::class, 'count_id', 'uuid');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'uuid');
    }

    public function committer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'committed_by', 'uuid');
    }
}
