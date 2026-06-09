<?php

declare(strict_types=1);

namespace App\Modules\Sync\Models;

use App\Modules\Sync\Enums\SyncEntityTypeEnum;
use App\Modules\Sync\Enums\SyncOperationStatusEnum;
use App\Modules\Sync\Enums\SyncOperationTypeEnum;
use App\Shared\Traits\BelongsToTenant;
use Database\Factories\SyncOperationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Operação de sincronização — append-only, idempotente.
 *
 * operation_uuid é gerado pelo PDV (não pelo servidor).
 * Nunca sofre UPDATE destrutivo — apenas status transitions.
 * Não usa HasUuid pois o PK vem do cliente.
 */
final class SyncOperation extends Model
{
    /** @use HasFactory<SyncOperationFactory> */
    use HasFactory;
    use BelongsToTenant;

    protected static function newFactory(): SyncOperationFactory
    {
        return SyncOperationFactory::new();
    }

    protected $table = 'sync_operations';

    protected $primaryKey = 'operation_uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    /** Tabela tem created_at, received_at, processed_at — sem updated_at. */
    public $timestamps = false;

    protected $fillable = [
        'operation_uuid',
        'tenant_id',
        'store_id',
        'device_id',
        'entity_type',
        'entity_uuid',
        'operation_type',
        'batch_id',
        'payload',
        'status',
        'idempotency_key',
        'retry_count',
        'last_error',
        'created_at',
        'received_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'entity_type'    => SyncEntityTypeEnum::class,
            'operation_type' => SyncOperationTypeEnum::class,
            'status'         => SyncOperationStatusEnum::class,
            'payload'        => 'array',
            'retry_count'    => 'integer',
            'created_at'     => 'datetime',
            'received_at'    => 'datetime',
            'processed_at'   => 'datetime',
        ];
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    public function isPdvOwned(): bool
    {
        return $this->entity_type->isPdvOwned();
    }

    public function transitionTo(SyncOperationStatusEnum $status, ?string $error = null): void
    {
        $updates = ['status' => $status];

        if ($status === SyncOperationStatusEnum::Synced || $status === SyncOperationStatusEnum::Failed || $status === SyncOperationStatusEnum::Conflict) {
            $updates['processed_at'] = now();
        }

        if ($error !== null) {
            $updates['last_error']  = $error;
            $updates['retry_count'] = $this->retry_count + 1;
        }

        $this->update($updates);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function device(): BelongsTo
    {
        return $this->belongsTo(SyncDevice::class, 'device_id', 'uuid');
    }
}
