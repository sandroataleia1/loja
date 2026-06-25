<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customers\Jobs\ImportCustomersJob;
use App\Shared\Models\ImportLog;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Core\Tenancy\Services\TenantContext;

final class CustomerImportController extends Controller
{
    use HasApiResponse;

    private const TEMPLATE_HEADERS = [
        'name', 'trade_name', 'person_type', 'document',
        'email', 'phone', 'whatsapp',
        'zip_code', 'street', 'number', 'neighborhood', 'city', 'state',
    ];

    public function store(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $request->validate([
            'file' => ['required', 'file', 'max:5120', 'mimes:csv,txt'],
        ]);

        $file     = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $path     = $file->storeAs("imports/{$tenantId}", uniqid('customers_', true) . '.csv');

        $importLog = ImportLog::create([
            'tenant_id'   => $tenantId,
            'imported_by' => $request->user()->uuid,
            'import_type' => 'customers',
            'file_name'   => $fileName,
            'status'      => 'processing',
            'started_at'  => now(),
        ]);

        ImportCustomersJob::dispatch($tenantId, $path, $importLog->uuid);

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
        }, 'clientes_template.csv', ['Content-Type' => 'text/csv']);
    }
}
