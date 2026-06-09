<?php

declare(strict_types=1);

namespace App\Modules\Sync\Http\Controllers;

use App\Modules\Sync\Actions\AckPullAction;
use App\Modules\Sync\Actions\SyncBatchAction;
use App\Modules\Sync\Actions\SyncPullAction;
use App\Modules\Sync\DTOs\SyncBatchDTO;
use App\Modules\Sync\DTOs\SyncPullDTO;
use App\Modules\Sync\Http\Requests\SyncBatchRequest;
use App\Modules\Sync\Http\Requests\SyncPullAckRequest;
use App\Modules\Sync\Http\Requests\SyncPullRequest;
use App\Modules\Sync\Http\Resources\SyncBatchResultResource;
use App\Modules\Sync\Http\Resources\SyncLogResource;
use App\Modules\Sync\Http\Resources\SyncPullResultResource;
use App\Modules\Sync\Models\SyncDevice;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

final class SyncController extends Controller
{
    use HasApiResponse;

    /** POST /sync/push — PDV envia lote de operações. */
    public function push(SyncBatchRequest $request, SyncBatchAction $action): JsonResponse
    {
        $this->authorize('push', SyncDevice::class);

        $result = $action->execute(SyncBatchDTO::fromRequest($request), $request);

        return $this->success(new SyncBatchResultResource($result));
    }

    /** POST /sync/pull — PDV solicita dados atualizados do backend. */
    public function pull(SyncPullRequest $request, SyncPullAction $action): JsonResponse
    {
        $this->authorize('pull', SyncDevice::class);

        $result = $action->execute(SyncPullDTO::fromRequest($request), $request);

        return $this->success(new SyncPullResultResource($result));
    }

    /**
     * POST /sync/pull/ack — PDV confirma que processou o pull.
     *
     * Registra o ack no SyncLog para auditoria.
     * O checkpoint já foi avançado no pull — o ack apenas confirma recebimento.
     */
    public function ack(SyncPullAckRequest $request, AckPullAction $action): JsonResponse
    {
        $this->authorize('pull', SyncDevice::class);

        $log = $action->execute(
            deviceUuid: $request->string('device_uuid')->toString(),
            batchId:    $request->string('batch_id')->toString(),
            pulledAt:   $request->string('pulled_at')->toString(),
        );

        return $this->success(new SyncLogResource($log));
    }
}
