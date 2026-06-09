<?php

declare(strict_types=1);

namespace App\Modules\Sync\Http\Controllers;

use App\Modules\Sync\Actions\RegisterDeviceAction;
use App\Modules\Sync\DTOs\RegisterDeviceDTO;
use App\Modules\Sync\Http\Requests\RegisterDeviceRequest;
use App\Modules\Sync\Http\Resources\SyncDeviceResource;
use App\Modules\Sync\Models\SyncDevice;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class SyncDeviceController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAnyDevice', SyncDevice::class);

        $devices = SyncDevice::query()
            ->when($request->string('store_id')->value(), fn ($q, $v) => $q->where('store_id', $v))
            ->when($request->boolean('active_only', false), fn ($q) => $q->where('is_active', true))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->success(SyncDeviceResource::collection($devices));
    }

    public function show(SyncDevice $device): JsonResponse
    {
        $this->authorize('viewDevice', $device);

        return $this->success(new SyncDeviceResource($device));
    }

    public function register(RegisterDeviceRequest $request, RegisterDeviceAction $action): JsonResponse
    {
        $this->authorize('registerDevice', SyncDevice::class);

        $device = $action->execute(RegisterDeviceDTO::fromRequest($request));

        return $this->created(new SyncDeviceResource($device));
    }

    public function deactivate(SyncDevice $device): JsonResponse
    {
        $this->authorize('deactivateDevice', SyncDevice::class);

        $device->update(['is_active' => false]);

        return $this->success(new SyncDeviceResource($device->fresh()));
    }
}
