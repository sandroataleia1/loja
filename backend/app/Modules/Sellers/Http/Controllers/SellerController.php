<?php

declare(strict_types=1);

namespace App\Modules\Sellers\Http\Controllers;

use App\Core\Auth\Models\User;
use App\Core\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\Sellers\Http\Resources\SellerResource;
use App\Modules\Sellers\Models\SellerProfile;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SellerController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $sellers = SellerProfile::where('tenant_id', $tenantId)
            ->with('user')
            ->when($request->boolean('active'), fn ($q) => $q->where('is_active', true))
            ->when($request->filled('q'), fn ($q) => $q->whereHas(
                'user',
                fn ($u) => $u->where('name', 'ilike', "%{$request->q}%")
                              ->orWhere('email', 'ilike', "%{$request->q}%")
            ))
            ->orderBy('created_at')
            ->paginate($request->integer('per_page', 50));

        return $this->success(
            data: SellerResource::collection($sellers),
            meta: [
                'current_page' => $sellers->currentPage(),
                'per_page'     => $sellers->perPage(),
                'total'        => $sellers->total(),
                'last_page'    => $sellers->lastPage(),
            ],
        );
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId  = TenantContext::getIdOrFail();
        $validated = $request->validate([
            'user_id'         => ['required', 'uuid', 'exists:users,uuid'],
            'code'            => ['nullable', 'string', 'max:20'],
            'nickname'        => ['nullable', 'string', 'max:50'],
            'seller_type'     => ['nullable', 'string', 'in:internal,external'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'monthly_target'  => ['nullable', 'numeric', 'min:0'],
            'supervisor_id'   => ['nullable', 'uuid', 'exists:seller_profiles,uuid'],
            'region'          => ['nullable', 'string', 'max:100'],
            'notes'           => ['nullable', 'string'],
        ]);

        // Verifica se usuário pertence ao tenant
        $userBelongs = User::where('uuid', $validated['user_id'])
            ->whereHas('memberships', fn ($q) => $q->where('tenant_id', $tenantId))
            ->exists();

        if (! $userBelongs) {
            return $this->error('Usuário não pertence a este tenant.', [], 422);
        }

        $seller = SellerProfile::create(array_merge(['tenant_id' => $tenantId], $validated));

        return $this->created(new SellerResource($seller->load('user')));
    }

    public function show(SellerProfile $seller): JsonResponse
    {
        return $this->success(new SellerResource($seller->load('user')));
    }

    public function update(Request $request, SellerProfile $seller): JsonResponse
    {
        $validated = $request->validate([
            'code'            => ['nullable', 'string', 'max:20'],
            'nickname'        => ['nullable', 'string', 'max:50'],
            'seller_type'     => ['nullable', 'string', 'in:internal,external'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'monthly_target'  => ['nullable', 'numeric', 'min:0'],
            'supervisor_id'   => ['nullable', 'uuid', 'exists:seller_profiles,uuid'],
            'region'          => ['nullable', 'string', 'max:100'],
            'is_active'       => ['boolean'],
            'notes'           => ['nullable', 'string'],
        ]);

        $seller->update($validated);

        return $this->success(new SellerResource($seller->refresh()->load('user')));
    }

    public function destroy(SellerProfile $seller): JsonResponse
    {
        $seller->delete();
        return $this->noContent();
    }
}
