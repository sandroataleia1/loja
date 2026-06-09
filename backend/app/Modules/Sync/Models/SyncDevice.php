<?php

declare(strict_types=1);

namespace App\Modules\Sync\Models;

use App\Core\Auth\Models\User;
use App\Modules\Inventory\Models\Store;
use App\Shared\Models\BaseModel;
use Database\Factories\SyncDeviceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Dispositivo PDV registrado.
 *
 * uuid = server-generated PK (BaseModel).
 * device_uuid = client-generated identifier, immutable after registration.
 */
final class SyncDevice extends BaseModel
{
    /** @use HasFactory<SyncDeviceFactory> */
    use HasFactory;

    protected static function newFactory(): SyncDeviceFactory
    {
        return SyncDeviceFactory::new();
    }

    protected $table = 'sync_devices';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'device_uuid',
        'name',
        'platform',
        'app_version',
        'last_seen_at',
        'is_active',
        'metadata',
        'created_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'last_seen_at' => 'datetime',
            'is_active'    => 'boolean',
            'metadata'     => 'array',
        ]);
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public function markSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }

    public function isOnline(int $thresholdMinutes = 5): bool
    {
        return $this->last_seen_at?->diffInMinutes(now()) <= $thresholdMinutes;
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'uuid');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'uuid');
    }

    public function operations(): HasMany
    {
        return $this->hasMany(SyncOperation::class, 'device_id', 'uuid');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SyncLog::class, 'device_id', 'uuid');
    }

    public function pullCheckpoints(): HasMany
    {
        return $this->hasMany(SyncPullCheckpoint::class, 'device_id', 'uuid');
    }
}
