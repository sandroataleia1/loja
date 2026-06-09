<?php

declare(strict_types=1);

namespace App\Modules\Sync\Http\Controllers;

use App\Modules\Sync\Http\Resources\SyncLogResource;
use App\Modules\Sync\Http\Resources\SyncOperationResource;
use App\Modules\Sync\Models\SyncLog;
use App\Modules\Sync\Models\SyncOperation;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class SyncLogController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAnyLog', SyncLog::class);

        $logs = SyncLog::query()
            ->when($request->string('device_id')->value(),  fn ($q, $v) => $q->where('device_id', $v))
            ->when($request->string('direction')->value(),  fn ($q, $v) => $q->where('direction', $v))
            ->when($request->string('batch_id')->value(),   fn ($q, $v) => $q->where('batch_id', $v))
            ->when($request->string('from')->value(),       fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->string('to')->value(),         fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 20));

        return $this->success(SyncLogResource::collection($logs));
    }

    public function show(SyncLog $log): JsonResponse
    {
        $this->authorize('viewLog', $log);

        return $this->success(new SyncLogResource($log));
    }

    public function operations(Request $request, string $batchId): JsonResponse
    {
        $this->authorize('viewAnyLog', SyncLog::class);

        $operations = SyncOperation::where('batch_id', $batchId)
            ->orderBy('received_at')
            ->paginate($request->integer('per_page', 50));

        return $this->success(SyncOperationResource::collection($operations));
    }
}
