<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Jobs;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Fiscal\Actions\ProcessFiscalDocumentAction;
use App\Modules\Fiscal\Models\FiscalDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Emite um documento fiscal via provedor configurado.
 *
 * Substitui ProcessFiscalDocumentJob como ponto de entrada principal.
 * Retries são gerenciados pelo próprio job (backoff exponencial).
 */
final class FiscalIssueJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int   $tries   = 3;
    public int   $timeout = 120;
    public array $backoff  = [30, 60, 120];

    public function __construct(
        public readonly string $documentUuid,
        public readonly string $tenantId,
    ) {
        $this->onQueue(config('fiscal.queue', 'fiscal'));
    }

    public function handle(ProcessFiscalDocumentAction $action): void
    {
        TenantContext::set($this->tenantId);

        try {
            $document = FiscalDocument::withoutTenantScope()
                ->where('uuid', $this->documentUuid)
                ->where('tenant_id', $this->tenantId)
                ->first();

            if ($document === null) {
                return;
            }

            $action->execute($document);
        } finally {
            TenantContext::clear();
        }
    }

    public function failed(\Throwable $e): void
    {
        // Telemetria: Sentry, Bugsnag, etc.
    }
}
