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

    private const COLUMNS = [
        'uuid', 'name', 'type', 'is_system', 'sort_order',
        'accepts_change', 'allow_installments', 'max_installments',
        'min_installment_value_cents', 'requires_authorization', 'integrates_financial',
        'is_active',
    ];

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
        ->get(self::COLUMNS);

        return $this->success($items->values());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                       => ['required', 'string', 'max:100'],
            'type'                       => ['nullable', 'string', 'max:30'],
            'accepts_change'             => ['boolean'],
            'allow_installments'         => ['boolean'],
            'max_installments'           => ['integer', 'min:1', 'max:72'],
            'min_installment_value_cents' => ['integer', 'min:0'],
            'requires_authorization'     => ['boolean'],
            'integrates_financial'       => ['boolean'],
        ]);

        $tenantId = TenantContext::getIdOrFail();

        $item = PaymentMethod::create([
            'tenant_id'                  => $tenantId,
            'name'                       => $validated['name'],
            'type'                       => $validated['type'] ?? null,
            'accepts_change'             => $validated['accepts_change'] ?? false,
            'allow_installments'         => $validated['allow_installments'] ?? false,
            'max_installments'           => $validated['max_installments'] ?? 1,
            'min_installment_value_cents' => $validated['min_installment_value_cents'] ?? 0,
            'requires_authorization'     => $validated['requires_authorization'] ?? false,
            'integrates_financial'       => $validated['integrates_financial'] ?? true,
            'is_active'                  => true,
            'is_system'                  => false,
            'sort_order'                 => (int) (PaymentMethod::whereNull('tenant_id')->max('sort_order') ?? 0) + 100,
        ]);

        return $this->created($item->only(self::COLUMNS));
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'name'                       => ['required', 'string', 'max:100'],
            'accepts_change'             => ['boolean'],
            'allow_installments'         => ['boolean'],
            'max_installments'           => ['integer', 'min:1', 'max:72'],
            'min_installment_value_cents' => ['integer', 'min:0'],
            'requires_authorization'     => ['boolean'],
            'integrates_financial'       => ['boolean'],
            'is_active'                  => ['boolean'],
        ]);

        $item = PaymentMethod::where('uuid', $uuid)
            ->where('tenant_id', TenantContext::getIdOrFail())
            ->firstOrFail();

        $item->update($validated);

        return $this->success($item->fresh()?->only(self::COLUMNS));
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
