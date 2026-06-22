<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\PaymentCondition;
use App\Modules\Finance\Services\InstallmentCalculatorService;
use App\Shared\Traits\HasApiResponse;
use App\Core\Tenancy\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentConditionController extends Controller
{
    use HasApiResponse;

    private const COLUMNS = [
        'uuid', 'name', 'type',
        'discount_type', 'discount_value',
        'interest_type', 'interest_value',
        'fine_percent', 'fine_after_days', 'grace_days',
        'installment_count', 'first_due_days', 'interval_days',
        'has_entry', 'entry_percent', 'is_variable',
        'is_active', 'is_system', 'sort_order',
    ];

    private const VALIDATION_RULES = [
        'name'              => ['required', 'string', 'max:100'],
        'type'              => ['string', 'in:a_vista,parcelado,entrada_parcelas,variavel'],
        'discount_type'     => ['string', 'in:none,percent,fixed'],
        'discount_value'    => ['numeric', 'min:0'],
        'interest_type'     => ['string', 'in:none,percent_month,percent_total,fixed_per_installment,fixed_total'],
        'interest_value'    => ['numeric', 'min:0'],
        'fine_percent'      => ['numeric', 'min:0', 'max:100'],
        'fine_after_days'   => ['integer', 'min:0'],
        'grace_days'        => ['integer', 'min:0'],
        'installment_count' => ['integer', 'min:0', 'max:360'],
        'first_due_days'    => ['integer', 'min:0'],
        'interval_days'     => ['integer', 'min:1', 'max:365'],
        'has_entry'         => ['boolean'],
        'entry_percent'     => ['numeric', 'min:0', 'max:100'],
        'is_variable'       => ['boolean'],
        'is_active'         => ['boolean'],
    ];

    public function index(): JsonResponse
    {
        $tenantId = TenantContext::getId();

        $items = PaymentCondition::where(function ($q) use ($tenantId): void {
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
        $validated = $request->validate(self::VALIDATION_RULES);

        $tenantId = TenantContext::getIdOrFail();

        $item = PaymentCondition::create(array_merge([
            'tenant_id'  => $tenantId,
            'is_active'  => true,
            'is_system'  => false,
            'sort_order' => (int) (PaymentCondition::whereNull('tenant_id')->max('sort_order') ?? 0) + 100,
        ], $validated));

        return $this->created($item->fresh()?->only(self::COLUMNS));
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate(self::VALIDATION_RULES);

        $item = PaymentCondition::where('uuid', $uuid)
            ->where('tenant_id', TenantContext::getIdOrFail())
            ->firstOrFail();

        $item->update($validated);

        return $this->success($item->fresh()?->only(self::COLUMNS));
    }

    public function destroy(string $uuid): JsonResponse
    {
        PaymentCondition::where('uuid', $uuid)
            ->where('tenant_id', TenantContext::getIdOrFail())
            ->firstOrFail()
            ->delete();

        return $this->noContent();
    }

    /**
     * Calcula e retorna o cronograma de parcelas para um valor dado.
     * Usado para preview no frontend e validação antes de salvar.
     */
    public function calculate(
        string $uuid,
        Request $request,
        InstallmentCalculatorService $calculator,
    ): JsonResponse {
        $request->validate([
            'amount_cents'      => ['required', 'integer', 'min:1'],
            'sale_date'         => ['nullable', 'date'],
            'installment_count' => ['nullable', 'integer', 'min:1', 'max:360'],
        ]);

        $tenantId = TenantContext::getId();

        $condition = PaymentCondition::where('uuid', $uuid)
            ->where(function ($q) use ($tenantId): void {
                $q->whereNull('tenant_id');
                if ($tenantId !== null) {
                    $q->orWhere('tenant_id', $tenantId);
                }
            })
            ->firstOrFail();

        $saleDate = $request->filled('sale_date')
            ? Carbon::parse($request->string('sale_date')->toString())
            : Carbon::today();

        $result = $calculator->calculate(
            condition:              $condition,
            amountCents:            $request->integer('amount_cents'),
            saleDate:               $saleDate,
            customInstallmentCount: $request->filled('installment_count')
                ? $request->integer('installment_count')
                : null,
        );

        return $this->success($result);
    }
}
