<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Jobs\ImportPricesJob;
use App\Modules\Catalog\Models\PriceList;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;

final class PriceImportController extends Controller
{
    use HasApiResponse;

    /**
     * POST /api/v1/catalog/price-lists/{priceList}/import
     *
     * Enfileira importação de preços via CSV. Retorna 202 imediatamente.
     */
    public function import(Request $request, PriceList $priceList): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $file     = $request->file('file');
        $tenantId = TenantContext::getIdOrFail();
        $userId   = $request->user()->uuid;
        $path     = $file->store("imports/prices/{$tenantId}", 'local');

        ImportPricesJob::dispatch($path, $priceList->uuid, $tenantId, $userId);

        return $this->accepted('Importação de preços iniciada. Os preços serão atualizados em breve.');
    }

    /**
     * GET /api/v1/catalog/price-lists/import/template
     *
     * Retorna CSV de exemplo para importação de preços.
     */
    public function template(): Response
    {
        $headers = 'variant_sku,price_cents,min_price_cents,packaging_price_cents,packaging_qty,valid_from,valid_to,reason';
        $example1 = 'PROD001-SC,4500,3500,,,,,';
        $example2 = 'PROD002-UN,280,200,2500,10,2026-07-01,2026-12-31,Promoção Julho';
        $example3 = 'PROD003-PC,1200,,,,,,"Ajuste custo fornecedor"';

        $csv = implode("\n", [$headers, $example1, $example2, $example3]) . "\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="price_import_template.csv"',
        ]);
    }
}
