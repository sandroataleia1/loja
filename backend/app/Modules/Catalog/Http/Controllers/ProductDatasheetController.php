<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\ProductDatasheetService;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ProductDatasheetController extends Controller
{
    use HasApiResponse;

    public function __construct(private readonly ProductDatasheetService $service) {}

    public function pdf(Product $product): Response
    {
        $pdfContent = $this->service->generatePdf($product);

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="ficha-' . $product->slug . '.pdf"',
        ]);
    }

    public function share(Product $product): JsonResponse
    {
        $link = $this->service->generateShareLink($product);

        return $this->created([
            'token'      => $link->token,
            'url'        => route('v1.catalog.public.products.show', ['token' => $link->token]),
            'expires_at' => $link->expires_at,
        ]);
    }
}
