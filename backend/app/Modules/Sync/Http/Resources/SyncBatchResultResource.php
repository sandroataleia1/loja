<?php

declare(strict_types=1);

namespace App\Modules\Sync\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resultado de um push batch.
 *
 * $this->resource = ['log' => SyncLog, 'results' => array<string, array>]
 */
final class SyncBatchResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $log = $this->resource['log'];

        return [
            'batch_id'       => $log->batch_id,
            'direction'      => $log->direction,
            'operation_count' => $log->operation_count,
            'synced_count'   => $log->synced_count,
            'failed_count'   => $log->failed_count,
            'conflict_count' => $log->conflict_count,
            'duration_ms'    => $log->duration_ms,
            'results'        => $this->resource['results'],
        ];
    }
}
