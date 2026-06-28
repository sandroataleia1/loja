<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Models\ProductStorageLocation;
use App\Modules\Catalog\Models\Variant;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class VariantStorageLocationController extends Controller
{
    use HasApiResponse;

    public function index(Variant $variant): JsonResponse
    {
        $locations = ProductStorageLocation::where('variant_id', $variant->uuid)
            ->with('storageAddress')
            ->get();

        return $this->success($locations);
    }

    public function store(Request $request, Variant $variant): JsonResponse
    {
        $tenantId = TenantContext::getId();

        $validated = $request->validate([
            'storage_address_id' => 'required|uuid',
            'is_primary'         => 'boolean',
        ]);

        $location = ProductStorageLocation::create([
            ...$validated,
            'tenant_id'  => $tenantId,
            'variant_id' => $variant->uuid,
        ]);

        return $this->created($location->load('storageAddress'));
    }

    public function show(Variant $variant, ProductStorageLocation $storageLocation): JsonResponse
    {
        abort_unless($storageLocation->variant_id === $variant->uuid, 404);

        return $this->success($storageLocation->load('storageAddress'));
    }

    public function update(Request $request, Variant $variant, ProductStorageLocation $storageLocation): JsonResponse
    {
        abort_unless($storageLocation->variant_id === $variant->uuid, 404);

        $validated = $request->validate([
            'is_primary' => 'boolean',
        ]);

        $storageLocation->update($validated);

        return $this->success($storageLocation->fresh()->load('storageAddress'));
    }

    public function destroy(Variant $variant, ProductStorageLocation $storageLocation): JsonResponse
    {
        abort_unless($storageLocation->variant_id === $variant->uuid, 404);

        $storageLocation->delete();

        return $this->noContent();
    }
}
