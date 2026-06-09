<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Controllers;

use App\Modules\Sales\Actions\AddPaymentAction;
use App\Modules\Sales\Actions\ApplyDiscountAction;
use App\Modules\Sales\Actions\CancelSaleAction;
use App\Modules\Sales\Actions\CompleteSaleAction;
use App\Modules\Sales\Actions\CreateCommissionAction;
use App\Modules\Sales\Actions\CreateSaleAction;
use App\Modules\Sales\DTOs\AddPaymentDTO;
use App\Modules\Sales\DTOs\ApplyDiscountDTO;
use App\Modules\Sales\DTOs\CancelSaleDTO;
use App\Modules\Sales\DTOs\CreateCommissionDTO;
use App\Modules\Sales\DTOs\CreateSaleDTO;
use App\Modules\Sales\Http\Requests\AddPaymentRequest;
use App\Modules\Sales\Http\Requests\ApplyDiscountRequest;
use App\Modules\Sales\Http\Requests\CancelSaleRequest;
use App\Modules\Sales\Http\Requests\CreateCommissionRequest;
use App\Modules\Sales\Http\Requests\CreateSaleRequest;
use App\Modules\Sales\Http\Resources\PaymentTransactionResource;
use App\Modules\Sales\Http\Resources\SaleCommissionResource;
use App\Modules\Sales\Http\Resources\SaleResource;
use App\Modules\Sales\Models\Sale;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class SaleController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $sales = Sale::query()
            ->with(['store', 'seller'])
            ->when($request->string('store_id')->value(),   fn ($q, $v) => $q->where('store_id', $v))
            ->when($request->string('status')->value(),     fn ($q, $v) => $q->where('status', $v))
            ->when($request->string('session_id')->value(), fn ($q, $v) => $q->where('session_id', $v))
            ->when($request->string('customer_id')->value(), fn ($q, $v) => $q->where('customer_id', $v))
            ->when($request->string('seller_id')->value(),  fn ($q, $v) => $q->where('seller_id', $v))
            ->when($request->string('from')->value(),       fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->string('to')->value(),         fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            data: SaleResource::collection($sales),
            meta: [
                'current_page' => $sales->currentPage(),
                'per_page'     => $sales->perPage(),
                'total'        => $sales->total(),
                'last_page'    => $sales->lastPage(),
            ],
        );
    }

    public function store(CreateSaleRequest $request, CreateSaleAction $action): JsonResponse
    {
        $sale = $action->execute(CreateSaleDTO::fromRequest($request));

        return $this->created(new SaleResource($sale));
    }

    public function show(Sale $sale): JsonResponse
    {
        $sale->load(['store', 'seller', 'session', 'items', 'payments', 'discounts', 'commissions']);

        return $this->success(new SaleResource($sale));
    }

    public function complete(Sale $sale, CompleteSaleAction $action): JsonResponse
    {
        $completed = $action->execute($sale);

        return $this->success(new SaleResource($completed), 'Venda concluída com sucesso.');
    }

    public function cancel(CancelSaleRequest $request, Sale $sale, CancelSaleAction $action): JsonResponse
    {
        $cancelled = $action->execute($sale, CancelSaleDTO::fromRequest($request));

        return $this->success(new SaleResource($cancelled), 'Venda cancelada.');
    }

    public function addPayment(AddPaymentRequest $request, Sale $sale, AddPaymentAction $action): JsonResponse
    {
        $payment = $action->execute($sale, AddPaymentDTO::fromRequest($request));

        return $this->created(
            new PaymentTransactionResource($payment),
            'Pagamento registrado.',
        );
    }

    public function applyDiscount(ApplyDiscountRequest $request, Sale $sale, ApplyDiscountAction $action): JsonResponse
    {
        $updated = $action->execute($sale, ApplyDiscountDTO::fromRequest($request));

        return $this->success(new SaleResource($updated), 'Desconto aplicado.');
    }

    public function addCommission(CreateCommissionRequest $request, Sale $sale, CreateCommissionAction $action): JsonResponse
    {
        $commission = $action->execute($sale, CreateCommissionDTO::fromRequest($request));

        return $this->created(new SaleCommissionResource($commission), 'Comissão registrada.');
    }
}
