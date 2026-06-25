<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\Purchasing\Jobs\ImportSuppliersJob;
use App\Shared\Models\ImportLog;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SupplierImportController extends Controller
{
    use HasApiResponse;

    private const TEMPLATE_HEADERS = [
        'name', 'trade_name', 'person_type', 'document',
        'email', 'phone', 'ie',
    ];

    public function store(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $request->validate([
            'file' => ['required', 'file', 'max:5120', 'mimes:csv,txt'],
        ]);

        $file     = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $path     = $file->storeAs("imports/{$tenantId}", uniqid('suppliers_', true) . '.csv');

        $importLog = ImportLog::create([
            'tenant_id'   => $tenantId,
            'imported_by' => $request->user()->uuid,
            'import_type' => 'suppliers',
            'file_name'   => $fileName,
            'status'      => 'processing',
            'started_at'  => now(),
        ]);

        ImportSuppliersJob::dispatch($tenantId, $path, $importLog->uuid);

        return response()->json([
            'success' => true,
            'message' => 'Importação iniciada.',
            'data'    => ['import_log_id' => $importLog->uuid],
        ], 202);
    }

    public function status(string $importLogId): JsonResponse
    {
        $tenantId  = TenantContext::getIdOrFail();
        $importLog = ImportLog::where('uuid', $importLogId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        return $this->success([
            'uuid'          => $importLog->uuid,
            'status'        => $importLog->status,
            'total_rows'    => $importLog->total_rows,
            'success_count' => $importLog->success_count,
            'error_count'   => $importLog->error_count,
            'errors'        => $importLog->errors,
            'started_at'    => $importLog->started_at?->toISOString(),
            'finished_at'   => $importLog->finished_at?->toISOString(),
        ]);
    }

    public function template(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::TEMPLATE_HEADERS);
            fclose($out);
        }, 'fornecedores_template.csv', ['Content-Type' => 'text/csv']);
    }
}
