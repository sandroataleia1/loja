<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Resources\ProductPriceHistoryResource;
use App\Modules\Catalog\Models\Product;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class ProductPriceHistoryController extends Controller
{
    use HasApiResponse;

    public function index(Request $request, Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        $history = $product->priceHistory()
            ->when($request->string('variant_id')->value(), fn ($q, $v) => $q->where('variant_id', $v))
            ->paginate($request->integer('per_page', 50));

        return $this->success(
            data: ProductPriceHistoryResource::collection($history),
            meta: [
                'current_page' => $history->currentPage(),
                'per_page'     => $history->perPage(),
                'total'        => $history->total(),
                'last_page'    => $history->lastPage(),
            ],
        );
    }
}
