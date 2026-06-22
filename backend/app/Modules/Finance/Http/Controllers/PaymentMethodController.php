<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\PaymentMethod;
use App\Shared\Traits\HasApiResponse;
use App\Core\Tenancy\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentMethodController extends Controller
{
    use HasApiResponse;

    public function index(): JsonResponse
    {
        $tenantId = TenantContext::getId();

        $items = PaymentMethod::where(function ($q) use ($tenantId): void {
            $q->whereNull('tenant_id');
            if ($tenantId !== null) {
                $q->orWhere('tenant_id', $tenantId);
            }
        })
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get(['uuid', 'name', 'type', 'is_system', 'sort_order']);

        return $this->success($items->values());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:30'],
        ]);

        $tenantId = TenantContext::getIdOrFail();

        $item = PaymentMethod::create([
            'tenant_id'  => $tenantId,
            'name'       => $validated['name'],
            'type'       => $validated['type'] ?? null,
            'is_active'  => true,
            'is_system'  => false,
            'sort_order' => (int) (PaymentMethod::whereNull('tenant_id')->max('sort_order') ?? 0) + 100,
        ]);

        return $this->created([
            'uuid'       => $item->uuid,
            'name'       => $item->name,
            'type'       => $item->type,
            'is_system'  => false,
            'sort_order' => $item->sort_order,
        ]);
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $item = PaymentMethod::where('uuid', $uuid)
            ->where('tenant_id', TenantContext::getIdOrFail())
            ->firstOrFail();

        $item->update(['name' => $validated['name']]);

        return $this->success([
            'uuid'       => $item->uuid,
            'name'       => $item->name,
            'type'       => $item->type,
            'is_system'  => false,
            'sort_order' => $item->sort_order,
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        PaymentMethod::where('uuid', $uuid)
            ->where('tenant_id', TenantContext::getIdOrFail())
            ->firstOrFail()
            ->delete();

        return $this->noContent();
    }
}
