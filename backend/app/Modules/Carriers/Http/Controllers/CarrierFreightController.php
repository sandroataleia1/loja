<?php

declare(strict_types=1);

namespace App\Modules\Carriers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Carriers\Models\Carrier;
use App\Modules\Carriers\Models\CarrierFreightRange;
use App\Modules\Carriers\Models\CarrierFreightTable;
use App\Modules\Carriers\Services\FreightCalculatorService;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CarrierFreightController extends Controller
{
    use HasApiResponse;

    public function __construct(private readonly FreightCalculatorService $calculator) {}

    public function tables(Carrier $carrier): JsonResponse
    {
        return $this->success($carrier->freightTables()->with('ranges')->get());
    }

    public function storeTable(Request $request, Carrier $carrier): JsonResponse
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:150'],
            'pricing_type' => ['required', 'string', 'in:' . implode(',', CarrierFreightTable::PRICING_TYPES)],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $table = CarrierFreightTable::create(array_merge(
            ['tenant_id' => $carrier->tenant_id, 'carrier_id' => $carrier->uuid],
            $validated,
        ));

        return $this->created($table);
    }

    public function updateTable(Request $request, Carrier $carrier, CarrierFreightTable $table): JsonResponse
    {
        $validated = $request->validate([
            'name'         => ['sometimes', 'required', 'string', 'max:150'],
            'pricing_type' => ['sometimes', 'required', 'string', 'in:' . implode(',', CarrierFreightTable::PRICING_TYPES)],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $table->update($validated);

        return $this->success($table->refresh());
    }

    public function destroyTable(Carrier $carrier, CarrierFreightTable $table): JsonResponse
    {
        $table->delete();

        return $this->noContent();
    }

    public function storeRange(Request $request, Carrier $carrier, CarrierFreightTable $table): JsonResponse
    {
        $validated = $request->validate([
            'min_weight_g'    => ['nullable', 'numeric', 'min:0'],
            'max_weight_g'    => ['nullable', 'numeric', 'min:0'],
            'min_value_cents' => ['nullable', 'integer', 'min:0'],
            'max_value_cents' => ['nullable', 'integer', 'min:0'],
            'min_cep'         => ['nullable', 'string', 'max:8'],
            'max_cep'         => ['nullable', 'string', 'max:8'],
            'price_cents'     => ['required', 'integer', 'min:0'],
            'estimated_days'  => ['nullable', 'integer', 'min:0'],
        ]);

        $range = CarrierFreightRange::create(array_merge(
            ['tenant_id' => $carrier->tenant_id, 'freight_table_id' => $table->uuid],
            $validated,
        ));

        return $this->created($range);
    }

    public function destroyRange(Carrier $carrier, CarrierFreightTable $table, CarrierFreightRange $range): JsonResponse
    {
        $range->delete();

        return $this->noContent();
    }

    public function calculate(Request $request, Carrier $carrier): JsonResponse
    {
        $validated = $request->validate([
            'dest_cep'         => ['required', 'string'],
            'weight_g'         => ['required', 'numeric', 'min:0'],
            'value_cents'      => ['required', 'integer', 'min:0'],
            'freight_table_id' => ['nullable', 'uuid'],
        ]);

        $result = $this->calculator->calculate(
            $carrier->uuid,
            $validated['dest_cep'],
            (float) $validated['weight_g'],
            (int) $validated['value_cents'],
            $validated['freight_table_id'] ?? null,
        );

        if (! $result) {
            return $this->error('Nenhuma faixa de frete encontrada para os parâmetros informados.', 422);
        }

        return $this->success($result->toArray());
    }
}
