<?php

declare(strict_types=1);

namespace App\Modules\Sales\Models;

use App\Modules\Inventory\Models\Store;
use App\Modules\Sales\Enums\CashRegisterSessionStatusEnum;
use App\Shared\Models\BaseModel;
use Database\Factories\CashRegisterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class CashRegister extends BaseModel
{
    /** @use HasFactory<CashRegisterFactory> */
    use HasFactory;

    protected $table = 'cash_registers';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'code',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_active' => 'boolean',
        ]);
    }

    protected static function newFactory(): CashRegisterFactory
    {
        return CashRegisterFactory::new();
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public function hasOpenSession(): bool
    {
        return $this->sessions()
            ->where('status', CashRegisterSessionStatusEnum::Open)
            ->exists();
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'uuid');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CashRegisterSession::class, 'cash_register_id', 'uuid');
    }

    public function openSession(): HasOne
    {
        return $this->hasOne(CashRegisterSession::class, 'cash_register_id', 'uuid')
            ->where('status', CashRegisterSessionStatusEnum::Open);
    }
}
