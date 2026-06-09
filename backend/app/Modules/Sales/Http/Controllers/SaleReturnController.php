<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Controllers;

use App\Modules\Sales\Actions\ProcessReturnAction;
use App\Modules\Sales\DTOs\ProcessReturnDTO;
use App\Modules\Sales\Http\Requests\ProcessReturnRequest;
use App\Modules\Sales\Http\Resources\SaleReturnResource;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleReturn;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class SaleReturnController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $returns = SaleReturn::query()
            ->with(['items', 'originalSale'])
            ->when($request->string('store_id')->value(), fn ($q, $v) => $q->where('store_id', $v))
            ->when($request->string('status')->value(),   fn ($q, $v) => $q->where('status', $v))
            ->when($request->string('from')->value(),     fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->string('to')->value(),       fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->success(SaleReturnResource::collection($returns));
    }

    public function show(SaleReturn $saleReturn): JsonResponse
    {
        return $this->success(new SaleReturnResource($saleReturn->load(['items', 'originalSale'])));
    }

    public function bySale(Sale $sale): JsonResponse
    {
        $returns = SaleReturn::where('original_sale_id', $sale->uuid)
            ->with('items')
            ->get();

        return $this->success(SaleReturnResource::collection($returns));
    }

    public function store(ProcessReturnRequest $request, ProcessReturnAction $action): JsonResponse
    {
        $saleReturn = $action->execute(ProcessReturnDTO::fromRequest($request));

        return $this->created(new SaleReturnResource($saleReturn));
    }
}
