<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Http\Resources\CostCenterResource;
use App\Modules\Finance\Models\CostCenter;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CostCenterController extends Controller
{
    use HasApiResponse;

    private const TYPES = ['ADMINISTRATIVE', 'COMMERCIAL', 'LOGISTICS', 'PURCHASING', 'FINANCIAL'];

    public function index(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $costCenters = CostCenter::where('tenant_id', $tenantId)
            ->when($request->boolean('active'), fn ($q) => $q->where('is_active', true))
            ->when($request->filled('type'),    fn ($q) => $q->where('type', $request->type))
            ->orderBy('code')
            ->get();

        return $this->success(CostCenterResource::collection($costCenters));
    }

    public function tree(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $roots = CostCenter::where('tenant_id', $tenantId)
            ->whereNull('parent_id')
            ->with('allChildren')
            ->orderBy('code')
            ->get();

        return $this->success(CostCenterResource::collection($roots));
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId  = TenantContext::getIdOrFail();
        $validated = $request->validate([
            'parent_id' => ['nullable', 'uuid', 'exists:cost_centers,uuid'],
            'code'      => ['required', 'string', 'max:20'],
            'name'      => ['required', 'string', 'max:100'],
            'type'      => ['required', 'string', 'in:' . implode(',', self::TYPES)],
            'is_active' => ['boolean'],
        ]);

        $costCenter = CostCenter::create(array_merge(['tenant_id' => $tenantId], $validated));

        return $this->created(new CostCenterResource($costCenter));
    }

    public function show(CostCenter $costCenter): JsonResponse
    {
        return $this->success(new CostCenterResource($costCenter->load('children')));
    }

    public function update(Request $request, CostCenter $costCenter): JsonResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'uuid', 'exists:cost_centers,uuid'],
            'code'      => ['sometimes', 'required', 'string', 'max:20'],
            'name'      => ['sometimes', 'required', 'string', 'max:100'],
            'type'      => ['sometimes', 'required', 'string', 'in:' . implode(',', self::TYPES)],
            'is_active' => ['boolean'],
        ]);

        $costCenter->update($validated);

        return $this->success(new CostCenterResource($costCenter->refresh()));
    }

    public function destroy(CostCenter $costCenter): JsonResponse
    {
        if ($costCenter->children()->exists()) {
            return $this->error('Não é possível excluir um centro de custo que possui filhos.', 422);
        }

        $costCenter->delete();

        return $this->noContent();
    }
}
