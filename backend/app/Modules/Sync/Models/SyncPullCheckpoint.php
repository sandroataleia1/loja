<?php

declare(strict_types=1);

namespace App\Modules\Sync\Models;

use App\Modules\Sync\Enums\SyncEntityTypeEnum;
use App\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Checkpoint de pull por device × entity_type.
 *
 * Rastreia o último timestamp sincronizado para que pulls incrementais
 * retornem apenas registros modificados após last_synced_at.
 *
 * PK = bigint autoincrement (não UUID — atualizado frequentemente).
 * Tem updated_at mas não created_at.
 */
final class SyncPullCheckpoint extends Model
{
    use BelongsToTenant;

    protected $table = 'sync_pull_checkpoints';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    /** Tabela tem updated_at mas não created_at. */
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'device_id',
        'entity_type',
        'last_synced_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'entity_type'    => SyncEntityTypeEnum::class,
            'last_synced_at' => 'datetime',
            'updated_at'     => 'datetime',
        ];
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public function advance(\DateTimeInterface $to): void
    {
        $this->update([
            'last_synced_at' => $to,
            'updated_at'     => now(),
        ]);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function device(): BelongsTo
    {
        return $this->belongsTo(SyncDevice::class, 'device_id', 'uuid');
    }
}
