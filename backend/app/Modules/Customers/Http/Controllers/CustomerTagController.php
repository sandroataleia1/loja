<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Controllers;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Customers\Http\Resources\CustomerTagResource;
use App\Modules\Customers\Models\CustomerTag;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class CustomerTagController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public function index(): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $tags = CustomerTag::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        return $this->success(CustomerTagResource::collection($tags));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', \App\Modules\Customers\Models\Customer::class);

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $tenantId = TenantContext::getIdOrFail();

        $existing = CustomerTag::where('tenant_id', $tenantId)
            ->where('name', $validated['name'])
            ->exists();

        if ($existing) {
            return $this->error('Tag com este nome já existe.', [], 409);
        }

        $tag = CustomerTag::create([
            'name'  => $validated['name'],
            'color' => $validated['color'] ?? null,
        ]);

        return $this->created(new CustomerTagResource($tag));
    }

    public function update(Request $request, CustomerTag $customerTag): JsonResponse
    {
        $this->authorize('update', \App\Modules\Customers\Models\Customer::class);

        $validated = $request->validate([
            'name'  => ['sometimes', 'required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $customerTag->update($validated);

        return $this->success(new CustomerTagResource($customerTag->refresh()));
    }

    public function destroy(CustomerTag $customerTag): JsonResponse
    {
        $this->authorize('delete', \App\Modules\Customers\Models\Customer::class);

        $customerTag->delete();

        return $this->noContent();
    }
}
