<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Listeners;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Events\ProductReturned;
use App\Modules\Catalog\Models\Product;
use App\Modules\Sales\Events\SaleReturned;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener assíncrono: dispara ProductReturned por produto após devolução.
 *
 * Permite que o domínio de catálogo rastreie taxas de retorno,
 * score de qualidade e decisões de campanha por produto.
 */
final class UpdateProductAnalyticsOnSaleReturned implements ShouldQueue
{
    public string $queue = 'default';

    public function handle(SaleReturned $event): void
    {
        $saleReturn  = $event->saleReturn;
        $originalSale = $event->originalSale;

        TenantContext::runFor($saleReturn->tenant_id, function () use ($saleReturn, $originalSale): void {
            $saleReturn->loadMissing('items');
            $processedProducts = [];

            foreach ($saleReturn->items as $item) {
                if ($item->variant_id === null) {
                    continue;
                }

                $product = Product::where('uuid', function ($query) use ($item): void {
                    $query->select('product_id')
                        ->from('catalog_variants')
                        ->where('uuid', $item->variant_id)
                        ->limit(1);
                })->first();

                if ($product === null) {
                    continue;
                }

                if (! in_array($product->uuid, $processedProducts, true)) {
                    ProductReturned::dispatch(
                        $product,
                        (int) $item->quantity_returned,
                        $originalSale->uuid,
                    );
                    $processedProducts[] = $product->uuid;
                }
            }
        });
    }
}
