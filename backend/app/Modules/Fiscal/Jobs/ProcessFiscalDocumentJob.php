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
 * Job de compatibilidade retroativa — delega para FiscalIssueJob.
 *
 * Mantido para não quebrar jobs já enfileirados antes do Sprint 11.
 * Novos dispatches devem usar FiscalIssueJob diretamente.
 *
 * @deprecated Use FiscalIssueJob para novos dispatches.
 */
final class ProcessFiscalDocumentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int   $tries   = 3;
    public int   $timeout = 120;
    public array $backoff  = [30, 60, 120];

    public function __construct(
        private readonly string $documentUuid,
        private readonly string $tenantId,
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

            if ($document !== null) {
                $action->execute($document);
            }
        } finally {
            TenantContext::clear();
        }
    }

    public function failed(\Throwable $e): void {}
}
