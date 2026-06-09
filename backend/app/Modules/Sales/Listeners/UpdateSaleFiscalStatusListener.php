<?php

declare(strict_types=1);

namespace App\Modules\Sales\Listeners;

use App\Modules\Fiscal\Events\FiscalDocumentAuthorized;
use App\Modules\Fiscal\Events\FiscalDocumentCancelled;
use App\Modules\Fiscal\Events\FiscalDocumentRejected;
use App\Modules\Sales\Enums\FiscalStatusEnum;
use App\Modules\Sales\Models\Sale;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Mantém sale.fiscal_status e sale.fiscal_key sincronizados com o FiscalDocument.
 *
 * Desacoplamento: o módulo Fiscal não conhece Sale — é o módulo Sales
 * que observa eventos fiscais e atualiza seus próprios campos.
 */
final class UpdateSaleFiscalStatusListener implements ShouldQueue
{
    public string $queue = 'fiscal';

    public function handleAuthorized(FiscalDocumentAuthorized $event): void
    {
        if ($event->document->sale_id === null) {
            return;
        }

        // Listener enfileirado roda sem contexto: escopa explicitamente pelo
        // tenant do documento fiscal (forTenant).
        Sale::forTenant($event->document->tenant_id)
            ->where('uuid', $event->document->sale_id)
            ->update([
                'fiscal_status' => FiscalStatusEnum::Issued,
                'fiscal_key'    => $event->document->access_key,
            ]);
    }

    public function handleCancelled(FiscalDocumentCancelled $event): void
    {
        if ($event->document->sale_id === null) {
            return;
        }

        Sale::forTenant($event->document->tenant_id)
            ->where('uuid', $event->document->sale_id)
            ->update([
                'fiscal_status' => FiscalStatusEnum::Cancelled,
            ]);
    }

    public function handleRejected(FiscalDocumentRejected $event): void
    {
        if ($event->document->sale_id === null) {
            return;
        }

        Sale::forTenant($event->document->tenant_id)
            ->where('uuid', $event->document->sale_id)
            ->update([
                'fiscal_status' => FiscalStatusEnum::Error,
            ]);
    }
}
