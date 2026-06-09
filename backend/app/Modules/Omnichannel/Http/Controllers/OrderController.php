<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Omnichannel\Actions\PlaceOrderAction;
use App\Modules\Omnichannel\DTOs\PlaceOrderDTO;
use App\Modules\Omnichannel\Enums\OrderStatusEnum;
use App\Modules\Omnichannel\Events\OrderFulfilled;
use App\Modules\Omnichannel\Events\OrderPaid;
use App\Modules\Omnichannel\Http\Requests\PlaceOrderRequest;
use App\Modules\Omnichannel\Http\Resources\OrderResource;
use App\Modules\Omnichannel\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class OrderController
{
    // GET /orders
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::where('tenant_id', TenantContext::getId())
            ->with('channel')
            ->orderByDesc('placed_at')
            ->paginate(50);

        return OrderResource::collection($orders);
    }

    // POST /orders
    public function store(PlaceOrderRequest $request, PlaceOrderAction $action): JsonResponse
    {
        $this->authorize('create', Order::class);

        $order = $action->execute(new PlaceOrderDTO(
            tenantId:    TenantContext::getId(),
            channelId:   $request->validated('channel_id'),
            totalAmount: (float) $request->validated('total_amount'),
            customerId:  $request->validated('customer_id'),
            storeId:     $request->validated('store_id'),
            metadata:    $request->validated('metadata'),
            placedAt:    $request->validated('placed_at')
                ? new \DateTimeImmutable($request->validated('placed_at'))
                : null,
        ));

        return response()->json([
            'success' => true,
            'data'    => OrderResource::make($order->load('channel')),
        ], 201);
    }

    // GET /orders/{order}
    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json([
            'success' => true,
            'data'    => OrderResource::make($order->load('channel')),
        ]);
    }

    // POST /orders/{order}/pay
    public function markPaid(Order $order): JsonResponse
    {
        $this->authorize('fulfill', $order);

        $order->transitionTo(OrderStatusEnum::Paid);

        OrderPaid::dispatch($order);

        return response()->json([
            'success' => true,
            'data'    => OrderResource::make($order),
        ]);
    }

    // POST /orders/{order}/cancel
    public function cancel(Order $order): JsonResponse
    {
        $this->authorize('cancel', $order);

        $order->transitionTo(OrderStatusEnum::Cancelled);

        return response()->json([
            'success' => true,
            'data'    => OrderResource::make($order),
        ]);
    }

    private function authorize(string $ability, mixed $model): void
    {
        app('gate')->authorize($ability, $model);
    }
}
