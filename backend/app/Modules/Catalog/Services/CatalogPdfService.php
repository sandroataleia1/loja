<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

final class CatalogPdfService
{
    /**
     * Gera o PDF do catálogo de produtos.
     *
     * @param  string[]  $productIds
     */
    public function generate(
        string  $tenantId,
        ?string $collectionId = null,
        array   $productIds   = [],
        bool    $includePrices = true,
    ): string {
        $query = Product::query()->where('tenant_id', $tenantId);

        if ($collectionId !== null) {
            $query->whereHas(
                'commercialCollections',
                fn ($q) => $q->where('uuid', $collectionId),
            );
        }

        if ($productIds !== []) {
            $query->whereIn('uuid', $productIds);
        }

        /** @var Collection<int, Product> $products */
        $products = $query->with(['brand', 'images', 'variants'])->get();

        $pdf = Pdf::loadView('catalog.catalog-pdf', compact('products', 'includePrices'));

        return $pdf->output();
    }
}
