<?php

declare(strict_types=1);

namespace App\Modules\Sync\Models;

use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Database\Factories\SyncLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log de batch — auditoria imutável de sincronizações.
 *
 * Não usa BaseModel: sem SoftDeletes, sem updated_at.
 * Criado ao iniciar o batch, atualizado (completed_at) ao finalizar.
 */
final class SyncLog extends Model
{
    /** @use HasFactory<SyncLogFactory> */
    use HasFactory;
    use BelongsToTenant;
    use HasUuid;

    protected static function newFactory(): SyncLogFactory
    {
        return SyncLogFactory::new();
    }

    protected $table = 'sync_logs';

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    /** Apenas created_at e completed_at — sem updated_at. */
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'device_id',
        'batch_id',
        'direction',
        'operation_count',
        'synced_count',
        'failed_count',
        'conflict_count',
        'duration_ms',
        'request_summary',
        'response_summary',
        'ip_address',
        'created_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'operation_count' => 'integer',
            'synced_count'    => 'integer',
            'failed_count'    => 'integer',
            'conflict_count'  => 'integer',
            'duration_ms'     => 'integer',
            'request_summary' => 'array',
            'response_summary' => 'array',
            'created_at'      => 'datetime',
            'completed_at'    => 'datetime',
        ];
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isPush(): bool
    {
        return $this->direction === 'push';
    }

    public function isPull(): bool
    {
        return $this->direction === 'pull';
    }

    public function successRate(): float
    {
        if ($this->operation_count === 0) {
            return 0.0;
        }

        return round($this->synced_count / $this->operation_count * 100, 2);
    }

    public function markCompleted(int $durationMs): void
    {
        $this->update([
            'completed_at' => now(),
            'duration_ms'  => $durationMs,
        ]);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function device(): BelongsTo
    {
        return $this->belongsTo(SyncDevice::class, 'device_id', 'uuid');
    }
}
