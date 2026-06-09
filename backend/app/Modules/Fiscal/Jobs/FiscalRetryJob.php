<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Jobs;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Fiscal\Models\FiscalDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Reenvia um documento fiscal rejeitado ou com erro.
 *
 * Diferente do FiscalIssueJob (emissão inicial), o FiscalRetryJob é disparado
 * manualmente pelo operador via POST /fiscal/documents/{id}/retry, após
 * corrigir o motivo da rejeição (dados da nota, certificado, etc.).
 */
final class FiscalRetryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int   $tries   = 1; // retry manual — operador decide quando retentar
    public int   $timeout = 120;

    public function __construct(
        public readonly string $documentUuid,
        public readonly string $tenantId,
    ) {
        $this->onQueue(config('fiscal.queue', 'fiscal'));
    }

    public function handle(): void
    {
        TenantContext::set($this->tenantId);

        try {
            $document = FiscalDocument::withoutTenantScope()
                ->where('uuid', $this->documentUuid)
                ->where('tenant_id', $this->tenantId)
                ->first();

            if ($document === null || ! $document->status->canRetry()) {
                return;
            }

            // Reseta para Pending antes de reprocessar
            $document->update(['status' => \App\Modules\Fiscal\Enums\FiscalDocumentStatusEnum::Pending]);

            FiscalIssueJob::dispatch($this->documentUuid, $this->tenantId);
        } finally {
            TenantContext::clear();
        }
    }
}
