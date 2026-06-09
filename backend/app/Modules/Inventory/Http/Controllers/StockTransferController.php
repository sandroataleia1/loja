<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers;

use App\Modules\Inventory\Actions\CancelTransferAction;
use App\Modules\Inventory\Actions\CreateTransferAction;
use App\Modules\Inventory\Actions\DispatchTransferAction;
use App\Modules\Inventory\Actions\ReceiveTransferAction;
use App\Modules\Inventory\DTOs\CreateTransferDTO;
use App\Modules\Inventory\DTOs\DispatchTransferDTO;
use App\Modules\Inventory\DTOs\ReceiveTransferDTO;
use App\Modules\Inventory\Http\Requests\CreateTransferRequest;
use App\Modules\Inventory\Http\Requests\DispatchTransferRequest;
use App\Modules\Inventory\Http\Requests\ReceiveTransferRequest;
use App\Modules\Inventory\Http\Resources\StockTransferResource;
use App\Modules\Inventory\Models\StockTransfer;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class StockTransferController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $transfers = StockTransfer::query()
            ->with(['originStore', 'destinationStore'])
            ->when($request->string('status')->value(),           fn ($q, $v) => $q->where('status', $v))
            ->when($request->string('origin_store_id')->value(),  fn ($q, $v) => $q->where('origin_store_id', $v))
            ->when($request->string('dest_store_id')->value(),    fn ($q, $v) => $q->where('destination_store_id', $v))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            data: StockTransferResource::collection($transfers),
            meta: [
                'current_page' => $transfers->currentPage(),
                'per_page'     => $transfers->perPage(),
                'total'        => $transfers->total(),
                'last_page'    => $transfers->lastPage(),
            ],
        );
    }

    public function store(CreateTransferRequest $request, CreateTransferAction $action): JsonResponse
    {
        $transfer = $action->execute(CreateTransferDTO::fromRequest($request));

        return $this->created(new StockTransferResource($transfer));
    }

    public function show(StockTransfer $stockTransfer): JsonResponse
    {
        $stockTransfer->load(['originStore', 'destinationStore', 'items.variant', 'creator']);

        return $this->success(new StockTransferResource($stockTransfer));
    }

    public function ship(DispatchTransferRequest $request, StockTransfer $stockTransfer, DispatchTransferAction $action): JsonResponse
    {
        $transfer = $action->execute($stockTransfer->load('items'), DispatchTransferDTO::fromRequest($request));

        return $this->success(new StockTransferResource($transfer), 'Transferência despachada com sucesso.');
    }

    public function receive(ReceiveTransferRequest $request, StockTransfer $stockTransfer, ReceiveTransferAction $action): JsonResponse
    {
        $transfer = $action->execute($stockTransfer->load('items'), ReceiveTransferDTO::fromRequest($request));

        return $this->success(new StockTransferResource($transfer), 'Transferência recebida com sucesso.');
    }

    public function cancel(StockTransfer $stockTransfer, CancelTransferAction $action): JsonResponse
    {
        $transfer = $action->execute($stockTransfer->load('items'));

        return $this->success(new StockTransferResource($transfer), 'Transferência cancelada.');
    }
}
