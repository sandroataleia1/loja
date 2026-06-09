<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Purchasing\Actions\CreateSupplierAction;
use App\Modules\Purchasing\Http\Resources\SupplierResource;
use App\Modules\Purchasing\Models\Supplier;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class SupplierController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $suppliers = Supplier::where('tenant_id', $tenantId)
            ->when($request->boolean('active'), fn ($q) => $q->where('is_active', true))
            ->when($request->filled('q'), fn ($q) => $q->where(
                fn ($sub) => $sub->where('name', 'ilike', "%{$request->q}%")
                                  ->orWhere('document', 'ilike', "%{$request->q}%")
                                  ->orWhere('code', 'ilike', "%{$request->q}%")
            ))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            data: SupplierResource::collection($suppliers),
            meta: [
                'current_page' => $suppliers->currentPage(),
                'per_page'     => $suppliers->perPage(),
                'total'        => $suppliers->total(),
                'last_page'    => $suppliers->lastPage(),
            ],
        );
    }

    public function store(Request $request, CreateSupplierAction $action): JsonResponse
    {
        $validated = $request->validate([
            'person_type' => ['required', 'in:INDIVIDUAL,COMPANY'],
            'name'        => ['required', 'string', 'max:200'],
            'trade_name'  => ['nullable', 'string', 'max:200'],
            'document'    => ['nullable', 'string', 'max:20'],
            'email'       => ['nullable', 'email', 'max:254'],
            'phone'       => ['nullable', 'string', 'max:30'],
            'notes'       => ['nullable', 'string'],
            'is_active'   => ['boolean'],
        ]);

        $supplier = $action->execute($validated);

        return $this->created(new SupplierResource($supplier));
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return $this->success(new SupplierResource($supplier));
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'name'       => ['sometimes', 'string', 'max:200'],
            'trade_name' => ['nullable', 'string', 'max:200'],
            'document'   => ['nullable', 'string', 'max:20'],
            'email'      => ['nullable', 'email', 'max:254'],
            'phone'      => ['nullable', 'string', 'max:30'],
            'notes'      => ['nullable', 'string'],
            'is_active'  => ['boolean'],
        ]);

        $supplier->update($validated);

        return $this->success(new SupplierResource($supplier->refresh()));
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();
        return $this->noContent();
    }
}
