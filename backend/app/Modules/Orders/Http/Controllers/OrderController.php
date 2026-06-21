<?php

declare(strict_types=1);

namespace App\Modules\Orders\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\Orders\Actions\CreateOrderAction;
use App\Modules\Orders\Actions\UpdateOrderStatusAction;
use App\Modules\Orders\DTOs\CreateOrderDTO;
use App\Modules\Orders\Enums\OrderStatusEnum;
use App\Modules\Orders\Http\Requests\StoreOrderRequest;
use App\Modules\Orders\Http\Requests\UpdateOrderStatusRequest;
use App\Modules\Orders\Http\Resources\OrderResource;
use App\Modules\Orders\Models\Order;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('tenant_id', TenantContext::getIdOrFail())
            ->withCount('items')
            ->with(['customer'])
            ->when($request->filled('status'),      fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('store_id'),    fn ($q) => $q->where('store_id', $request->store_id))
            ->when($request->filled('q'), fn ($q) => $q->where('number', 'ilike', "%{$request->q}%"))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            data: OrderResource::collection($orders),
            meta: ['total' => $orders->total(), 'per_page' => $orders->perPage(), 'current_page' => $orders->currentPage()],
        );
    }

    public function store(StoreOrderRequest $request, CreateOrderAction $action): JsonResponse
    {
        $order = $action->execute(CreateOrderDTO::fromRequest($request));
        return $this->created(new OrderResource($order->load('items', 'customer')));
    }

    public function show(string $uuid): JsonResponse
    {
        $order = Order::where('tenant_id', TenantContext::getIdOrFail())
            ->where('uuid', $uuid)
            ->with(['items', 'customer', 'quote'])
            ->firstOrFail();

        return $this->success(new OrderResource($order));
    }

    /** Busca por número — usado pelo PDV para carregar pedido no carrinho. */
    public function showByNumber(string $number): JsonResponse
    {
        $order = Order::where('tenant_id', TenantContext::getIdOrFail())
            ->where('number', strtoupper($number))
            ->whereIn('status', [OrderStatusEnum::Pending->value, OrderStatusEnum::Confirmed->value])
            ->with(['items', 'customer'])
            ->firstOrFail();

        return $this->success(new OrderResource($order));
    }

    public function updateStatus(
        string $uuid,
        UpdateOrderStatusRequest $request,
        UpdateOrderStatusAction $action
    ): JsonResponse {
        $order = Order::where('tenant_id', TenantContext::getIdOrFail())
            ->where('uuid', $uuid)->firstOrFail();

        $updated = $action->execute(
            $order,
            OrderStatusEnum::from($request->string('status')->toString()),
            $request->string('cancellation_reason')->value() ?: null,
        );

        return $this->success(new OrderResource($updated->load('items', 'customer')));
    }

    public function destroy(string $uuid): JsonResponse
    {
        $order = Order::where('tenant_id', TenantContext::getIdOrFail())
            ->where('uuid', $uuid)->firstOrFail();

        if (! in_array($order->status, [OrderStatusEnum::Pending, OrderStatusEnum::Cancelled])) {
            return $this->error('Apenas pedidos pendentes ou cancelados podem ser excluídos.', 409);
        }

        $order->delete();
        return $this->noContent();
    }
}
