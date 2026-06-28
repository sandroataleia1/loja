<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Observers;

use App\Modules\Catalog\Models\ProductPrice;
use App\Modules\Catalog\Models\ProductPriceHistory;

/**
 * Registra histórico imutável quando um ProductPrice é criado ou atualizado.
 *
 * ATENÇÃO: observers Eloquent NÃO disparam em updateOrCreate() via queries em massa.
 * O upsertPrices() no PriceListController registra o histórico manualmente.
 * Este observer cobre atualizações individuais (ex: edição futura via endpoint próprio).
 */
final class ProductPriceObserver
{
    public function created(ProductPrice $price): void
    {
        ProductPriceHistory::create([
            'tenant_id'       => $price->tenant_id,
            'product_id'      => $price->product_id,
            'variant_id'      => $price->variant_id,
            'old_price_cents' => null,
            'new_price_cents' => $price->price_cents,
            'changed_by'      => auth()->id(),
            'changed_at'      => now(),
            'reason'          => 'Cadastro inicial',
        ]);
    }

    public function updated(ProductPrice $price): void
    {
        if (! $price->wasChanged('price_cents')) {
            return;
        }

        ProductPriceHistory::create([
            'tenant_id'       => $price->tenant_id,
            'product_id'      => $price->product_id,
            'variant_id'      => $price->variant_id,
            'old_price_cents' => $price->getOriginal('price_cents'),
            'new_price_cents' => $price->price_cents,
            'changed_by'      => auth()->id(),
            'changed_at'      => now(),
            'reason'          => request()->input('reason'),
        ]);
    }
}
