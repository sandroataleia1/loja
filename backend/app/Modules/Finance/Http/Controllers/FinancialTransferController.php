<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Modules\Finance\Actions\CreateFinancialTransferAction;
use App\Modules\Finance\DTOs\CreateFinancialTransferDTO;
use App\Modules\Finance\Http\Requests\CreateFinancialTransferRequest;
use App\Modules\Finance\Http\Resources\FinancialTransferResource;
use App\Modules\Finance\Models\FinancialTransfer;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class FinancialTransferController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $transfers = FinancialTransfer::query()
            ->with(['originAccount', 'destinationAccount'])
            ->when($request->string('account_id')->value(), fn ($q, $v) =>
                $q->where(fn ($sub) => $sub->where('origin_account_id', $v)->orWhere('destination_account_id', $v)))
            ->when($request->string('from')->value(), fn ($q, $v) => $q->where('transferred_at', '>=', $v))
            ->when($request->string('to')->value(), fn ($q, $v) => $q->where('transferred_at', '<=', $v))
            ->orderByDesc('transferred_at')
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            data: FinancialTransferResource::collection($transfers),
            meta: [
                'current_page' => $transfers->currentPage(),
                'per_page'     => $transfers->perPage(),
                'total'        => $transfers->total(),
                'last_page'    => $transfers->lastPage(),
            ],
        );
    }

    public function store(CreateFinancialTransferRequest $request, CreateFinancialTransferAction $action): JsonResponse
    {
        $this->authorize('create', FinancialTransfer::class);

        $transfer = $action->execute(CreateFinancialTransferDTO::fromRequest($request));

        return $this->created(new FinancialTransferResource($transfer));
    }

    public function show(FinancialTransfer $financialTransfer): JsonResponse
    {
        $financialTransfer->load(['originAccount', 'destinationAccount']);

        return $this->success(new FinancialTransferResource($financialTransfer));
    }
}
