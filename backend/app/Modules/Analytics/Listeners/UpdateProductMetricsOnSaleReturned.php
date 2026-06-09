<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Listeners;

use App\Modules\Analytics\Enums\AggregateTypeEnum;
use App\Modules\Analytics\DTOs\AnalyticsEventDTO;
use App\Modules\Analytics\Models\ProductMetrics;
use App\Modules\Analytics\Services\AnalyticsEventRecorder;
use App\Modules\Analytics\Services\MetricsCalculator;
use App\Modules\Sales\Events\SaleReturned;
use Illuminate\Support\Str;

/**
 * Atualiza return_rate de ProductMetrics ao processar uma devolução.
 * Usa SaleReturnItem.variant_id → Variant.product_id para identificar o produto.
 */
final class UpdateProductMetricsOnSaleReturned
{
    public function __construct(
        private readonly AnalyticsEventRecorder $recorder,
        private readonly MetricsCalculator $calculator,
    ) {}

    public function handle(SaleReturned $event): void
    {
        $saleReturn = $event->saleReturn;
        $sale       = $event->originalSale;

        $returnItems = $saleReturn->items()->with('variant')->get();

        foreach ($returnItems as $returnItem) {
            $productId = $returnItem->variant?->product_id;

            if (empty($productId)) {
                continue;
            }

            $metrics = ProductMetrics::firstOrNew(
                ['tenant_id' => $sale->tenant_id, 'product_id' => $productId],
                ['uuid' => Str::uuid()->toString()],
            );

            // Recalculate return_rate: approximate increment
            // Exact recalculation done by ConsolidateProductMetricsJob
            $unitsSold     = max($metrics->units_sold, 1);
            $currentReturns = (int) round((float) $metrics->return_rate * $unitsSold);
            $newReturns    = $currentReturns + (int) $returnItem->quantity_returned;

            $metrics->return_rate = $this->calculator->returnRate($unitsSold, $newReturns);
            $metrics->computed_at = now();
            $metrics->save();

            $refunded = $this->calculator->centsToDecimal((int) $returnItem->refund_amount_cents);

            $this->recorder->record(new AnalyticsEventDTO(
                tenantId:      $sale->tenant_id,
                eventName:     'product.returned',
                aggregateType: AggregateTypeEnum::Product,
                aggregateUuid: $productId,
                payload:       [
                    'sale_return_id'   => $saleReturn->uuid,
                    'sale_id'          => $sale->uuid,
                    'variant_id'       => $returnItem->variant_id,
                    'quantity_returned' => $returnItem->quantity_returned,
                    'refunded'         => $refunded,
                ],
                metadata: ['store_id' => $sale->store_id],
            ));
        }
    }
}
