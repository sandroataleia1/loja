<?php

declare(strict_types=1);

namespace App\Modules\Conditional\Models;

use App\Core\Auth\Models\User;
use App\Modules\Conditional\Enums\ConditionalStatusEnum;
use App\Modules\Customers\Models\Customer;
use App\Modules\Inventory\Models\Store;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Conditional extends BaseModel
{
    use HasFactory;

    protected $table = 'conditionals';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'customer_id',
        'opened_by',
        'code',
        'status',
        'expires_at',
        'subtotal_cents',
        'total_cents',
        'notes',
        'total_items',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'status'         => ConditionalStatusEnum::class,
            'expires_at'     => 'datetime',
            'subtotal_cents' => 'integer',
            'total_cents'    => 'integer',
            'total_items'    => 'integer',
        ]);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function items(): HasMany
    {
        return $this->hasMany(ConditionalItem::class, 'conditional_id', 'uuid');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ConditionalStatusHistory::class, 'conditional_id', 'uuid');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'uuid');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'uuid');
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by', 'uuid');
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public function isOverdue(): bool
    {
        return $this->expires_at !== null
            && $this->expires_at->isPast()
            && ! $this->status->isSettled();
    }

    public function pendingQuantity(ConditionalItem $item): int
    {
        return $item->quantity - $item->returned_quantity - $item->sold_quantity;
    }
}
