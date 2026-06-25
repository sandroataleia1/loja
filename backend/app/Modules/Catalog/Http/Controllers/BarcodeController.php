<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Models\CatalogBarcode;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

final class BarcodeController extends Controller
{
    use HasApiResponse;

    /**
     * Busca produto/variante pelo valor exato de código de barras.
     * Usado pelo PDV e módulo de separação de pedidos.
     */
    public function lookup(string $value): JsonResponse
    {
        $barcode = CatalogBarcode::where('value', $value)
            ->with([
                'product.brand',
                'product.categories',
                'product.images',
                'variant.product.brand',
                'variant.product.categories',
                'variant.product.images',
            ])
            ->firstOrFail();

        $product = $barcode->product ?? $barcode->variant?->product;

        if ($product === null) {
            return $this->notFound('Produto não encontrado para este código de barras.');
        }

        return $this->success([
            'product'        => new ProductResource($product),
            'matched_variant' => $barcode->variant?->uuid,
            'barcode_type'    => $barcode->barcode_type->value,
            'barcode_label'   => $barcode->barcode_type->label(),
        ]);
    }
}
