<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Actions;

use App\Modules\Omnichannel\DTOs\PlaceOrderDTO;
use App\Modules\Omnichannel\Enums\OrderStatusEnum;
use App\Modules\Omnichannel\Events\OrderPlaced;
use App\Modules\Omnichannel\Models\Order;

final readonly class PlaceOrderAction
{
    public function execute(PlaceOrderDTO $dto): Order
    {
        $order = Order::create([
            'tenant_id'    => $dto->tenantId,
            'channel_id'   => $dto->channelId,
            'customer_id'  => $dto->customerId,
            'store_id'     => $dto->storeId,
            'order_number' => $this->generateUniqueOrderNumber(),
            'status'       => OrderStatusEnum::Pending,
            'total_amount' => $dto->totalAmount,
            'metadata'     => $dto->metadata,
            'placed_at'    => $dto->placedAt ?? now(),
        ]);

        OrderPlaced::dispatch($order);

        return $order;
    }

    private function generateUniqueOrderNumber(): string
    {
        do {
            $number = Order::generateOrderNumber();
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
