<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Listeners;

use App\Modules\Analytics\Enums\AggregateTypeEnum;
use App\Modules\Analytics\DTOs\AnalyticsEventDTO;
use App\Modules\Analytics\Models\ChannelMetrics;
use App\Modules\Analytics\Services\AnalyticsEventRecorder;
use App\Modules\Analytics\Services\MetricsCalculator;
use App\Modules\Omnichannel\Events\OrderPlaced;
use Illuminate\Support\Str;

/**
 * Incrementa ChannelMetrics quando um pedido omnichannel é criado.
 * Apenas orders_count — revenue é confirmada em OrderPaid.
 */
final class UpdateChannelMetricsOnOrderPlaced
{
    public function __construct(
        private readonly AnalyticsEventRecorder $recorder,
        private readonly MetricsCalculator $calculator,
    ) {}

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;

        $metrics = ChannelMetrics::firstOrNew(
            ['tenant_id' => $order->tenant_id, 'channel_id' => $order->channel_id],
            ['uuid' => Str::uuid()->toString()],
        );

        $metrics->orders_count += 1;
        $metrics->computed_at   = now();
        $metrics->save();

        $this->recorder->record(new AnalyticsEventDTO(
            tenantId:      $order->tenant_id,
            eventName:     'order.placed',
            aggregateType: AggregateTypeEnum::Order,
            aggregateUuid: $order->uuid,
            payload:       [
                'order_id'   => $order->uuid,
                'channel_id' => $order->channel_id,
                'amount'     => $order->total_amount,
                'customer_id' => $order->customer_id,
            ],
            metadata:      ['channel_id' => $order->channel_id],
        ));
    }
}
