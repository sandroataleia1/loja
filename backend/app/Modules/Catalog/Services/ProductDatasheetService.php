<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductShareLink;
use Barryvdh\DomPDF\Facade\Pdf;

final class ProductDatasheetService
{
    public function generatePdf(Product $product): string
    {
        $product->loadMissing([
            'brand',
            'categories',
            'variants',
            'images',
            'barcodes',
            'unit',
        ]);

        $pdf = Pdf::loadView('catalog.product-datasheet', compact('product'));

        return $pdf->output();
    }

    public function generateShareLink(Product $product): ProductShareLink
    {
        return ProductShareLink::create([
            'tenant_id'  => $product->tenant_id,
            'product_id' => $product->uuid,
            'token'      => bin2hex(random_bytes(32)),
            'expires_at' => now()->addHours(24),
        ]);
    }
}
