<?php

declare(strict_types=1);

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Enums\OrderStatusEnum;
use App\Modules\Orders\Models\Order;
use App\Shared\Exceptions\ConflictException;

final readonly class UpdateOrderStatusAction
{
    public function execute(Order $order, OrderStatusEnum $newStatus, ?string $reason = null): Order
    {
        if (! $order->status->canTransitionTo($newStatus)) {
            throw new ConflictException(
                "Transição inválida: {$order->status->label()} → {$newStatus->label()}."
            );
        }

        $timestamps = match ($newStatus) {
            OrderStatusEnum::Confirmed  => ['confirmed_at'  => now()],
            OrderStatusEnum::Delivered  => ['delivered_at'  => now()],
            OrderStatusEnum::Completed  => ['completed_at'  => now()],
            OrderStatusEnum::Cancelled  => ['cancelled_at'  => now(), 'cancellation_reason' => $reason],
            default                     => [],
        };

        $order->update(array_merge(['status' => $newStatus], $timestamps));

        return $order->refresh();
    }
}
