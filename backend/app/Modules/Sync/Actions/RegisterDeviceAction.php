<?php

declare(strict_types=1);

namespace App\Modules\Sync\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Inventory\Models\Store;
use App\Modules\Sync\DTOs\RegisterDeviceDTO;
use App\Modules\Sync\Events\DeviceRegistered;
use App\Modules\Sync\Models\SyncDevice;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final readonly class RegisterDeviceAction
{
    /**
     * Registra ou reativa um dispositivo PDV para uma loja.
     *
     * Idempotente: re-registro com mesmo device_uuid retorna o dispositivo existente
     * e atualiza metadados (name, platform, app_version).
     */
    public function execute(RegisterDeviceDTO $dto): SyncDevice
    {
        $tenantId = TenantContext::getIdOrFail();

        $store = Store::where('uuid', $dto->storeId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($store === null) {
            throw new UnprocessableEntityHttpException('Loja não encontrada para este tenant.');
        }

        $isNew = false;

        $device = SyncDevice::withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('store_id', $dto->storeId)
            ->where('device_uuid', $dto->deviceUuid)
            ->first();

        if ($device === null) {
            $device = SyncDevice::create([
                'tenant_id'   => $tenantId,
                'store_id'    => $dto->storeId,
                'device_uuid' => $dto->deviceUuid,
                'name'        => $dto->name,
                'platform'    => $dto->platform,
                'app_version' => $dto->appVersion,
                'is_active'   => true,
                'last_seen_at' => now(),
                'metadata'    => $dto->metadata,
                'created_by'  => Auth::id(),
            ]);

            $isNew = true;
        } else {
            if ($device->trashed()) {
                $device->restore();
            }

            $device->update([
                'name'        => $dto->name,
                'platform'    => $dto->platform,
                'app_version' => $dto->appVersion,
                'is_active'   => true,
                'last_seen_at' => now(),
                'metadata'    => $dto->metadata ?? $device->metadata,
            ]);
        }

        if ($isNew) {
            DeviceRegistered::dispatch($device);
        }

        return $device->fresh();
    }
}
