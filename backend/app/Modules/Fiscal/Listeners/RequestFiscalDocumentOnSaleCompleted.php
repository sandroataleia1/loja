<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Listeners;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Fiscal\Actions\GenerateFiscalDocumentAction;
use App\Modules\Sales\Events\SaleCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener assíncrono: delega a decisão fiscal ao GenerateFiscalDocumentAction.
 *
 * Implementa ShouldQueue para garantir que falhas na emissão fiscal
 * NUNCA afetem o fluxo da venda. A venda já está concluída quando este
 * listener executa.
 *
 * O tenant context é restaurado porque workers de fila não passam por
 * middleware HTTP.
 */
final class RequestFiscalDocumentOnSaleCompleted implements ShouldQueue
{
    public string $queue = 'fiscal';

    public function __construct(
        private readonly GenerateFiscalDocumentAction $action,
    ) {}

    public function handle(SaleCompleted $event): void
    {
        $sale = $event->sale;

        TenantContext::runFor($sale->tenant_id, function () use ($sale): void {
            $this->action->execute($sale, isAutoTriggered: true);
        });
    }
}
