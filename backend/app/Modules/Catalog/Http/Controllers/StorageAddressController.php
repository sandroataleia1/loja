<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Models\StorageAddress;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StorageAddressController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getId();

        $addresses = StorageAddress::where('tenant_id', $tenantId)
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->when($request->input('warehouse_id'), fn ($q, $v) => $q->where('warehouse_id', $v))
            ->orderBy('aisle')->orderBy('rack')->orderBy('shelf')->orderBy('position')
            ->get();

        return $this->success($addresses);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getId();

        $validated = $request->validate([
            'warehouse_id' => 'nullable|uuid',
            'aisle'        => 'nullable|string|max:10',
            'rack'         => 'nullable|string|max:10',
            'shelf'        => 'nullable|string|max:10',
            'position'     => 'nullable|string|max:10',
            'capacity'     => 'nullable|numeric|min:0',
            'is_active'    => 'boolean',
        ]);

        // Verificar duplicata manualmente (PostgreSQL trata NULL ≠ NULL em unique)
        $existsQuery = StorageAddress::where('tenant_id', $tenantId);

        foreach (['warehouse_id', 'aisle', 'rack', 'shelf', 'position'] as $col) {
            $value = $validated[$col] ?? null;
            if ($value === null) {
                $existsQuery->whereNull($col);
            } else {
                $existsQuery->where($col, $value);
            }
        }

        if ($existsQuery->exists()) {
            return $this->error('Endereço já existe para este tenant/depósito.', status: 422);
        }

        try {
            $address = StorageAddress::create([
                ...$validated,
                'tenant_id' => $tenantId,
            ]);
        } catch (QueryException $e) {
            // PostgreSQL unique violation: SQLSTATE 23505
            if (in_array($e->getCode(), ['23505', 23505], strict: false)
                || str_contains(strtolower($e->getMessage()), 'unique')
            ) {
                return $this->error('Endereço já existe para este tenant/depósito.', status: 422);
            }
            throw $e;
        }

        return $this->created($address);
    }

    public function show(StorageAddress $storageAddress): JsonResponse
    {
        return $this->success($storageAddress->append('full_address'));
    }

    public function findByAddress(StorageAddress $address): JsonResponse
    {
        return $this->success($address->append('full_address')->load('storageLocations'));
    }

    public function update(Request $request, StorageAddress $storageAddress): JsonResponse
    {
        $validated = $request->validate([
            'aisle'     => 'nullable|string|max:10',
            'rack'      => 'nullable|string|max:10',
            'shelf'     => 'nullable|string|max:10',
            'position'  => 'nullable|string|max:10',
            'capacity'  => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $storageAddress->update($validated);

        return $this->success($storageAddress->fresh()->append('full_address'));
    }

    public function destroy(StorageAddress $storageAddress): JsonResponse
    {
        $storageAddress->delete();

        return $this->noContent();
    }
}
