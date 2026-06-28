<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Jobs\ExportCatalogPdfJob;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Variant;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CatalogExportController extends Controller
{
    use HasApiResponse;

    /**
     * POST /api/v1/catalog/catalog/export/pdf
     * Despacha geração assíncrona do catálogo PDF.
     */
    public function exportPdf(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'collection_id'  => 'nullable|uuid',
            'product_ids'    => 'nullable|array',
            'product_ids.*'  => 'uuid',
            'include_prices' => 'boolean',
        ]);

        $tenantId = TenantContext::getId();
        $jobId    = \Illuminate\Support\Str::uuid()->toString();

        ExportCatalogPdfJob::dispatch(
            tenantId:      $tenantId,
            jobId:         $jobId,
            collectionId:  $validated['collection_id'] ?? null,
            productIds:    $validated['product_ids'] ?? [],
            includePrices: $validated['include_prices'] ?? true,
        );

        return $this->success(['job_id' => $jobId, 'status' => 'queued'], status: 202);
    }

    /**
     * GET /api/v1/catalog/catalog/export/{jobId}/status
     * Verifica o status do job de exportação.
     */
    public function exportStatus(string $jobId): JsonResponse
    {
        $status = Cache::get("catalog_export_{$jobId}");

        if ($status === null) {
            return $this->error('Job não encontrado ou expirado.', status: 404);
        }

        return $this->success($status);
    }

    /**
     * GET /api/v1/catalog/catalog/products/export/csv
     * Exporta produtos + variantes como CSV (streamed).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $tenantId = TenantContext::getId();

        $products = Product::where('tenant_id', $tenantId)
            ->with(['brand', 'variants', 'categories'])
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="catalog-export-' . now()->format('Ymd-His') . '.csv"',
        ];

        $callback = function () use ($products): void {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 para compatibilidade com Excel
            fwrite($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, [
                'product_uuid', 'product_name', 'slug', 'type', 'status', 'brand',
                'base_price_cents', 'cost_price_cents', 'is_on_sale',
                'variant_uuid', 'variant_sku', 'variant_name', 'variant_price_cents',
            ]);

            foreach ($products as $product) {
                if ($product->variants->isEmpty()) {
                    fputcsv($handle, [
                        $product->uuid, $product->name, $product->slug,
                        $product->type?->value, $product->status?->value,
                        $product->brand?->name,
                        $product->base_price_cents, $product->cost_price_cents,
                        $product->is_on_sale ? '1' : '0',
                        '', '', '', '',
                    ]);
                } else {
                    foreach ($product->variants as $variant) {
                        fputcsv($handle, [
                            $product->uuid, $product->name, $product->slug,
                            $product->type?->value, $product->status?->value,
                            $product->brand?->name,
                            $product->base_price_cents, $product->cost_price_cents,
                            $product->is_on_sale ? '1' : '0',
                            $variant->uuid, $variant->sku, $variant->name, $variant->price_cents,
                        ]);
                    }
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
