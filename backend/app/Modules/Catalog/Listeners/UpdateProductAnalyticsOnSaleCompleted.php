<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Listeners;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Events\ProductSold;
use App\Modules\Catalog\Models\Product;
use App\Modules\Sales\Events\SaleCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener assíncrono: atualiza analytics de produtos após SaleCompleted.
 *
 * Responsabilidades:
 * - Atualizar last_sale_at em cada produto vendido
 * - Disparar ProductSold por produto/variante
 *
 * NÃO bloqueia o fluxo da venda (ShouldQueue).
 * TenantContext é restaurado explicitamente pois workers não passam por middleware HTTP.
 *
 * sales_velocity e days_without_sale são calculados por um job agendado separado
 * (não em tempo real para evitar N+1 na conclusão de vendas com muitos itens).
 */
final class UpdateProductAnalyticsOnSaleCompleted implements ShouldQueue
{
    public string $queue = 'default';

    public function handle(SaleCompleted $event): void
    {
        $sale = $event->sale;

        TenantContext::runFor($sale->tenant_id, function () use ($sale): void {
            $sale->loadMissing('items');
            $processedProducts = [];

            foreach ($sale->items as $item) {
                if ($item->product_variant_id === null) {
                    continue;
                }

                $product = Product::where('uuid', function ($query) use ($item): void {
                    $query->select('product_id')
                        ->from('catalog_variants')
                        ->where('uuid', $item->product_variant_id)
                        ->limit(1);
                })->first();

                if ($product === null) {
                    continue;
                }

                $product->update(['last_sale_at' => $sale->completed_at ?? now()]);

                // Dispara uma vez por produto único na venda
                if (! in_array($product->uuid, $processedProducts, true)) {
                    ProductSold::dispatch(
                        $product,
                        (int) $item->quantity,
                        $sale->uuid,
                        $sale->sales_channel?->value ?? 'pdv',
                    );
                    $processedProducts[] = $product->uuid;
                }
            }
        });
    }
}
