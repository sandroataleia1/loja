<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductLot;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductLotController extends Controller
{
    use HasApiResponse;

    public function index(Product $product): JsonResponse
    {
        $lots = ProductLot::where('product_id', $product->uuid)
            ->where('tenant_id', $product->tenant_id)
            ->withTrashed(false)
            ->orderBy('received_at', 'desc')
            ->get();

        return $this->success($lots);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'lot_number'       => 'required|string|max:60',
            'manufacture_date' => 'nullable|date',
            'expiry_date'      => 'nullable|date|after_or_equal:manufacture_date',
            'received_at'      => 'required|date',
            'initial_qty'      => 'required|numeric|min:0',
            'variant_id'       => 'nullable|uuid',
            'purchase_order_id'=> 'nullable|uuid',
            'supplier_id'      => 'nullable|uuid',
            'notes'            => 'nullable|string',
        ]);

        $lot = ProductLot::create([
            ...$validated,
            'tenant_id'   => $product->tenant_id,
            'product_id'  => $product->uuid,
            'current_qty' => $validated['initial_qty'],
        ]);

        return $this->created($lot);
    }

    public function show(Product $product, ProductLot $lot): JsonResponse
    {
        abort_unless($lot->product_id === $product->uuid, 404);

        return $this->success($lot->load(['product', 'variant']));
    }

    public function update(Request $request, Product $product, ProductLot $lot): JsonResponse
    {
        abort_unless($lot->product_id === $product->uuid, 404);

        $validated = $request->validate([
            'lot_number'       => 'sometimes|string|max:60',
            'manufacture_date' => 'nullable|date',
            'expiry_date'      => 'nullable|date',
            'received_at'      => 'sometimes|date',
            'initial_qty'      => 'sometimes|numeric|min:0',
            'current_qty'      => 'sometimes|numeric|min:0',
            'notes'            => 'nullable|string',
        ]);

        $lot->update($validated);

        return $this->success($lot->fresh());
    }

    public function destroy(Product $product, ProductLot $lot): JsonResponse
    {
        abort_unless($lot->product_id === $product->uuid, 404);

        $lot->delete();

        return $this->noContent();
    }
}
