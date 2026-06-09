<?php

declare(strict_types=1);

namespace App\Modules\Sync\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resultado de um pull incremental.
 *
 * $this->resource = ['log' => SyncLog, 'pulled_at' => string, 'data' => array, 'checkpoints' => array]
 *
 * Estrutura de data por entity_type:
 * {
 *   "product": {
 *     "entities":   [...],        // entidades ativas/modificadas
 *     "tombstones": [{ uuid, deleted_at }, ...] // entidades deletadas — PDV deve remover do SQLite
 *   }
 * }
 *
 * O PDV deve confirmar recebimento via POST /sync/pull/ack { batch_id, pulled_at }
 */
final class SyncPullResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $log = $this->resource['log'];

        return [
            'batch_id'    => $log->batch_id,
            'pulled_at'   => $this->resource['pulled_at'],
            'synced_count' => $log->synced_count,
            'duration_ms' => $log->duration_ms,
            'data'        => $this->resource['data'],
            'checkpoints' => $this->resource['checkpoints'],
        ];
    }
}
