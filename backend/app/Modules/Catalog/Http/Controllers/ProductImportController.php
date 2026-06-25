<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Jobs\ImportProductsJob;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;

final class ProductImportController extends Controller
{
    use HasApiResponse;

    private const ALLOWED_MIMES = [
        'text/csv',
        'application/csv',
        'text/plain',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
    ];

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        $file     = $request->file('file');
        $tenantId = TenantContext::getIdOrFail();
        $userId   = $request->user()->uuid;
        $path     = $file->store("imports/catalog/{$tenantId}", 'local');

        ImportProductsJob::dispatch($path, $tenantId, $userId);

        return $this->accepted('Importação iniciada. Você será notificado ao concluir.');
    }

    public function template(): Response
    {
        $headers = 'code,name,category_code,brand_code,unit_code,ncm_code,price_cents,cost_cents,status,type,variant_sku,variant_barcode,variant_attributes,weight_g,barcode_type,barcode_value';
        $example1 = '#PROD001,"Cimento CP-II 50kg",MATERIAIS,VOTORANTIM,SC,3208.10.00,4500,3200,active,simple,PROD001-SC,7891234567890,,50000,ean13,7891234567890';
        $example2 = '#PROD002,"Tijolo Cerâmico 9 Furos",ALVENARIA,CERÂMICA ALFA,UN,6901.00.00,280,180,active,simple,PROD002-UN,7899999999999,,2500,ean13,7899999999999';

        $csv = implode("\n", [$headers, $example1, $example2]) . "\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="catalog_import_template.csv"',
        ]);
    }
}
