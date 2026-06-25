<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Actions\CreateUnitConversionAction;
use App\Modules\Catalog\DTOs\CreateUnitConversionDTO;
use App\Modules\Catalog\Http\Requests\StoreUnitConversionRequest;
use App\Modules\Catalog\Http\Resources\UnitConversionResource;
use App\Modules\Catalog\Models\UnitConversion;
use App\Modules\Catalog\Services\UnitConversionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class UnitConversionController
{
    public function __construct(
        private readonly UnitConversionService $service,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = UnitConversion::query()->orderBy('from_unit')->orderBy('to_unit');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->filled('variant_id')) {
            $query->where('variant_id', $request->input('variant_id'));
        }

        if ($request->boolean('global')) {
            $query->whereNull('product_id')->whereNull('variant_id');
        }

        return UnitConversionResource::collection($query->get());
    }

    public function store(StoreUnitConversionRequest $request, CreateUnitConversionAction $action): UnitConversionResource
    {
        $conversion = $action->execute(CreateUnitConversionDTO::fromRequest($request));

        return new UnitConversionResource($conversion);
    }

    public function update(Request $request, UnitConversion $unitConversion): UnitConversionResource
    {
        $data = $request->validate([
            'factor'    => ['sometimes', 'numeric', 'min:0.000001'],
            'notes'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $unitConversion->update($data);

        return new UnitConversionResource($unitConversion->fresh());
    }

    public function destroy(UnitConversion $unitConversion): JsonResponse
    {
        $unitConversion->delete();

        return response()->json(['message' => 'Conversão removida.']);
    }

    /** Endpoint para conversão pontual de quantidade (calculadora). */
    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quantity'   => ['required', 'numeric', 'min:0'],
            'from_unit'  => ['required', 'string'],
            'to_unit'    => ['required', 'string'],
            'product_id' => ['nullable', 'uuid'],
            'variant_id' => ['nullable', 'uuid'],
        ]);

        $result = $this->service->convert(
            quantity:  (float) $validated['quantity'],
            fromUnit:  strtoupper($validated['from_unit']),
            toUnit:    strtoupper($validated['to_unit']),
            productId: $validated['product_id'] ?? null,
            variantId: $validated['variant_id'] ?? null,
        );

        return response()->json([
            'quantity'   => $validated['quantity'],
            'from_unit'  => strtoupper($validated['from_unit']),
            'to_unit'    => strtoupper($validated['to_unit']),
            'result'     => $result,
            'converted'  => $result !== null,
        ]);
    }
}
