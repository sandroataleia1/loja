<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers;

use App\Modules\Inventory\Actions\CommitStockCountAction;
use App\Modules\Inventory\Actions\CreateStockCountAction;
use App\Modules\Inventory\DTOs\CommitStockCountDTO;
use App\Modules\Inventory\DTOs\CreateStockCountDTO;
use App\Modules\Inventory\Http\Requests\CommitStockCountRequest;
use App\Modules\Inventory\Http\Requests\CreateStockCountRequest;
use App\Modules\Inventory\Http\Resources\StockCountResource;
use App\Modules\Inventory\Models\StockCount;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class StockCountController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $counts = StockCount::query()
            ->with(['store'])
            ->when($request->string('store_id')->value(), fn ($q, $v) => $q->where('store_id', $v))
            ->when($request->string('status')->value(),   fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            data: StockCountResource::collection($counts),
            meta: [
                'current_page' => $counts->currentPage(),
                'per_page'     => $counts->perPage(),
                'total'        => $counts->total(),
                'last_page'    => $counts->lastPage(),
            ],
        );
    }

    public function store(CreateStockCountRequest $request, CreateStockCountAction $action): JsonResponse
    {
        $count = $action->execute(CreateStockCountDTO::fromRequest($request));

        return $this->created(new StockCountResource($count));
    }

    public function show(StockCount $stockCount): JsonResponse
    {
        $stockCount->load(['store', 'items.variant', 'creator', 'committer']);

        return $this->success(new StockCountResource($stockCount));
    }

    public function commit(CommitStockCountRequest $request, StockCount $stockCount, CommitStockCountAction $action): JsonResponse
    {
        $count = $action->execute($stockCount->load('items'), CommitStockCountDTO::fromRequest($request));

        return $this->success(new StockCountResource($count), 'Inventário confirmado com sucesso.');
    }
}
