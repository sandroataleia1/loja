<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Listeners;

use App\Modules\Analytics\Enums\AggregateTypeEnum;
use App\Modules\Analytics\DTOs\AnalyticsEventDTO;
use App\Modules\Analytics\Models\ProductMetrics;
use App\Modules\Analytics\Services\AnalyticsEventRecorder;
use App\Modules\Analytics\Services\MetricsCalculator;
use App\Modules\Sales\Events\SaleCompleted;
use Illuminate\Support\Str;

/**
 * Atualização incremental de ProductMetrics ao concluir uma venda.
 * Itera sobre os itens da venda e incrementa métricas por produto.
 * SaleItem.product_variant_id → Variant.product_id para identificar o produto.
 */
final class UpdateProductMetricsOnSaleCompleted
{
    public function __construct(
        private readonly AnalyticsEventRecorder $recorder,
        private readonly MetricsCalculator $calculator,
    ) {}

    public function handle(SaleCompleted $event): void
    {
        $sale  = $event->sale;
        $items = $sale->items()->with('variant')->get();

        foreach ($items as $item) {
            $productId = $item->variant?->product_id;

            if (empty($productId)) {
                continue;
            }

            $quantity    = (int) $item->quantity;
            $itemRevenue = $this->calculator->centsToDecimal((int) $item->total_cents);

            $metrics = ProductMetrics::firstOrNew(
                ['tenant_id' => $sale->tenant_id, 'product_id' => $productId],
                ['uuid' => Str::uuid()->toString()],
            );

            $metrics->units_sold    += $quantity;
            $metrics->gross_revenue = (float) $metrics->gross_revenue + $itemRevenue;
            $metrics->last_sale_at  = $sale->completed_at ?? now();
            $metrics->days_without_sale = 0;
            $metrics->computed_at   = now();
            $metrics->save();

            $this->recorder->record(new AnalyticsEventDTO(
                tenantId:      $sale->tenant_id,
                eventName:     'product.sold',
                aggregateType: AggregateTypeEnum::Product,
                aggregateUuid: $productId,
                payload:       [
                    'sale_id'    => $sale->uuid,
                    'variant_id' => $item->product_variant_id,
                    'quantity'   => $quantity,
                    'revenue'    => $itemRevenue,
                ],
                metadata:      ['store_id' => $sale->store_id, 'source' => 'pdv'],
            ));
        }
    }
}
