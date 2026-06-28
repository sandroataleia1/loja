<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Jobs;

use App\Modules\Catalog\Services\CatalogPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class ExportCatalogPdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    public function __construct(
        private readonly string  $tenantId,
        private readonly string  $jobId,
        private readonly ?string $collectionId  = null,
        private readonly array   $productIds    = [],
        private readonly bool    $includePrices = true,
    ) {
        $this->onQueue('catalog');
    }

    public function handle(CatalogPdfService $service): void
    {
        try {
            Cache::put("catalog_export_{$this->jobId}", ['status' => 'processing'], now()->addHour());

            $pdf      = $service->generate($this->tenantId, $this->collectionId, $this->productIds, $this->includePrices);
            $filePath = "catalog-exports/{$this->tenantId}/{$this->jobId}.pdf";

            Storage::disk('local')->put($filePath, $pdf);

            Cache::put("catalog_export_{$this->jobId}", [
                'status'       => 'completed',
                'download_url' => route('catalog.export.download', ['jobId' => $this->jobId]),
                'file_path'    => $filePath,
                'generated_at' => now()->toIso8601String(),
            ], now()->addDay());

            Log::info("CatalogPdf export completed: {$this->jobId}", [
                'tenant_id' => $this->tenantId,
            ]);
        } catch (\Throwable $e) {
            Cache::put("catalog_export_{$this->jobId}", [
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ], now()->addHour());

            Log::error("CatalogPdf export failed: {$this->jobId}", [
                'tenant_id' => $this->tenantId,
                'error'     => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
