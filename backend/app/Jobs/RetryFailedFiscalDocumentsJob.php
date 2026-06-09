<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Fiscal\Actions\ProcessFiscalDocumentAction;
use App\Modules\Fiscal\Enums\FiscalDocumentStatusEnum;
use App\Modules\Fiscal\Models\FiscalDocument;
use App\Modules\Fiscal\Models\TenantFiscalSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job agendado para reprocessar documentos fiscais com falha.
 *
 * Executa a cada 15 minutos via scheduler.
 * Respeita fiscal_retry_max configurado por tenant.
 * Após atingir o limite: documento fica em 'failed' definitivo + alerta.
 */
final class RetryFailedFiscalDocumentsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue('fiscal');
    }

    public function handle(ProcessFiscalDocumentAction $processDocument): void
    {
        FiscalDocument::where('status', FiscalDocumentStatusEnum::Failed)
            ->with('sale')
            ->orderBy('created_at')
            ->chunk(50, function ($documents) use ($processDocument): void {
                foreach ($documents as $document) {
                    $this->retryDocument($document, $processDocument);
                }
            });
    }

    private function retryDocument(FiscalDocument $document, ProcessFiscalDocumentAction $action): void
    {
        $tenantId = $document->tenant_id;

        $settings = TenantFiscalSettings::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if ($settings === null) {
            Log::info('fiscal.retry.skipped', [
                'document_uuid' => $document->uuid,
                'reason'        => 'fiscal_settings_inactive',
            ]);
            return;
        }

        // Verificar certificado expirado antes de tentar
        if ($settings->certificate_expires_at?->isPast()) {
            Log::error('fiscal.retry.cert_expired', [
                'document_uuid'       => $document->uuid,
                'tenant_id'           => $tenantId,
                'certificate_expires' => $settings->certificate_expires_at,
            ]);
            return;
        }

        $retryCount = $document->retry_count ?? 0;
        $maxRetries = $settings->fiscal_retry_max ?? 5;

        if ($retryCount >= $maxRetries) {
            Log::error('fiscal.retry.max_reached', [
                'document_uuid' => $document->uuid,
                'retry_count'   => $retryCount,
                'max_retries'   => $maxRetries,
            ]);
            // Aqui poderia disparar: FiscalDocumentRetryExhausted event → notificação
            return;
        }

        try {
            TenantContext::set($tenantId);

            $action->execute($document);

            Log::info('fiscal.retry.success', [
                'document_uuid' => $document->uuid,
                'retry_count'   => $retryCount + 1,
            ]);
        } catch (\Throwable $e) {
            Log::warning('fiscal.retry.failed', [
                'document_uuid' => $document->uuid,
                'retry_count'   => $retryCount + 1,
                'error'         => $e->getMessage(),
            ]);
        } finally {
            TenantContext::clear();
        }
    }
}
