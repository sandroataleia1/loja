<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Listeners;

use App\Modules\Analytics\Enums\AggregateTypeEnum;
use App\Modules\Analytics\DTOs\AnalyticsEventDTO;
use App\Modules\Analytics\Models\CustomerMetrics;
use App\Modules\Analytics\Services\AnalyticsEventRecorder;
use App\Modules\Analytics\Services\MetricsCalculator;
use App\Modules\Sales\Events\SaleCompleted;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Atualização incremental de CustomerMetrics ao concluir uma venda.
 * Evita recalcular do zero — apenas incrementa os contadores.
 * Para recalcular do zero, use ConsolidateCustomerMetricsJob.
 */
final class UpdateCustomerMetricsOnSaleCompleted
{
    public function __construct(
        private readonly AnalyticsEventRecorder $recorder,
        private readonly MetricsCalculator $calculator,
    ) {}

    public function handle(SaleCompleted $event): void
    {
        $sale = $event->sale;

        if (empty($sale->customer_id)) {
            return;
        }

        $totalCents = (int) $sale->total_cents;
        $revenue    = $this->calculator->centsToDecimal($totalCents);
        $completedAt = $sale->completed_at ?? now();

        // Upsert incremental — não trava linha para leitura/escrita simultânea
        $metrics = CustomerMetrics::firstOrNew(
            ['tenant_id' => $sale->tenant_id, 'customer_id' => $sale->customer_id],
            ['uuid' => Str::uuid()->toString()],
        );

        $newTotalOrders = $metrics->total_orders + 1;
        $newTotalSpent  = (float) $metrics->total_spent + $revenue;

        $metrics->total_orders             = $newTotalOrders;
        $metrics->total_spent              = $newTotalSpent;
        $metrics->average_ticket           = $this->calculator->averageTicket($newTotalOrders, $newTotalSpent);
        $metrics->last_purchase_at         = $completedAt;
        $metrics->days_since_last_purchase = 0;
        $metrics->computed_at              = now();
        $metrics->save();

        // Record analytics event
        $this->recorder->record(new AnalyticsEventDTO(
            tenantId:      $sale->tenant_id,
            eventName:     'sale.completed',
            aggregateType: AggregateTypeEnum::Customer,
            aggregateUuid: $sale->customer_id,
            payload:       [
                'sale_id'       => $sale->uuid,
                'total_revenue' => $revenue,
                'store_id'      => $sale->store_id,
            ],
            metadata:      [
                'channel_id' => null,
                'source'     => 'pdv',
            ],
            occurredAt: Carbon::instance($completedAt)->toDateTimeImmutable(),
        ));
    }
}
