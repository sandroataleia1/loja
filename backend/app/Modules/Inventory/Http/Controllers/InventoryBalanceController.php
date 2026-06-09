<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers;

use App\Modules\Inventory\Actions\AdjustStockAction;
use App\Modules\Inventory\DTOs\AdjustStockDTO;
use App\Modules\Inventory\Http\Requests\AdjustStockRequest;
use App\Modules\Inventory\Http\Resources\InventoryBalanceResource;
use App\Modules\Inventory\Http\Resources\StockMovementResource;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\StockMovement;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class InventoryBalanceController extends Controller
{
    use HasApiResponse;

    /** Lista saldos filtráveis por loja e/ou variante. */
    public function index(Request $request): JsonResponse
    {
        $balances = InventoryBalance::query()
            ->with(['store', 'variant'])
            ->when($request->string('store_id')->value(),   fn ($q, $v) => $q->where('store_id', $v))
            ->when($request->string('variant_id')->value(), fn ($q, $v) => $q->where('variant_id', $v))
            ->when($request->boolean('low_stock'), fn ($q) => $q->whereRaw('(quantity - reserved_quantity) <= 5'))
            ->paginate($request->integer('per_page', 50));

        return $this->success(
            data: InventoryBalanceResource::collection($balances),
            meta: [
                'current_page' => $balances->currentPage(),
                'per_page'     => $balances->perPage(),
                'total'        => $balances->total(),
                'last_page'    => $balances->lastPage(),
            ],
        );
    }

    /** Ajuste manual de estoque (entrada, saída, perda, etc.). */
    public function adjust(AdjustStockRequest $request, AdjustStockAction $action): JsonResponse
    {
        $balance = $action->execute(AdjustStockDTO::fromRequest($request));

        return $this->success(
            new InventoryBalanceResource($balance->load(['store', 'variant'])),
            'Estoque ajustado com sucesso.',
        );
    }

    /** Histórico de movimentações filtráveis. */
    public function movements(Request $request): JsonResponse
    {
        $movements = StockMovement::query()
            ->when($request->string('store_id')->value(),   fn ($q, $v) => $q->where('store_id', $v))
            ->when($request->string('variant_id')->value(), fn ($q, $v) => $q->where('variant_id', $v))
            ->when($request->string('type')->value(),       fn ($q, $v) => $q->where('type', $v))
            ->when($request->string('from')->value(),       fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->string('to')->value(),         fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest('created_at')
            ->paginate($request->integer('per_page', 50));

        return $this->success(
            data: StockMovementResource::collection($movements),
            meta: [
                'current_page' => $movements->currentPage(),
                'per_page'     => $movements->perPage(),
                'total'        => $movements->total(),
                'last_page'    => $movements->lastPage(),
            ],
        );
    }
}
