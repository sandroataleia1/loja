<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Fiscal\DTOs\RequestFiscalDocumentDTO;
use App\Modules\Fiscal\Enums\FiscalModeEnum;
use App\Modules\Fiscal\Enums\FiscalProviderEnum;
use App\Modules\Fiscal\Events\FiscalEmissionFailed;
use App\Modules\Fiscal\Events\FiscalEmissionQueued;
use App\Modules\Fiscal\Events\FiscalEmissionRequested;
use App\Modules\Fiscal\Events\FiscalEmissionSkipped;
use App\Modules\Fiscal\Models\FiscalDocument;
use App\Modules\Fiscal\Models\TenantFiscalSettings;
use App\Modules\Sales\Models\Sale;

/**
 * Orquestrador de emissão fiscal — decide se/como emitir com base na
 * política do tenant e no fiscal_mode congelado na venda.
 *
 * Regras:
 * - fiscal_mode = NONE → skip imediato (nunca usa configuração do tenant)
 * - auto_issue_nfce = false → skip quando $isAutoTriggered = true
 * - Configuração inativa → skip
 * - Delega criação/enfileiramento para RequestFiscalDocumentAction
 */
final readonly class GenerateFiscalDocumentAction
{
    public function __construct(
        private RequestFiscalDocumentAction $requestAction,
    ) {}

    /**
     * @param  bool $isAutoTriggered  true = disparado por SaleCompleted; false = manual (PDV/API)
     */
    public function execute(Sale $sale, bool $isAutoTriggered = true): ?FiscalDocument
    {
        $mode = $sale->fiscal_mode ?? FiscalModeEnum::None;

        // Venda marcada como sem emissão fiscal — respeita independente de config
        if (! $mode->requiresFiscalDocument()) {
            FiscalEmissionSkipped::dispatch($sale, 'fiscal_mode=none');
            return null;
        }

        $tenantId = TenantContext::getIdOrFail();
        $settings = TenantFiscalSettings::where('tenant_id', $tenantId)->first();

        if ($settings === null || ! $settings->is_active) {
            FiscalEmissionSkipped::dispatch($sale, 'fiscal_settings_inactive');
            return null;
        }

        // Emissão automática (SaleCompleted) pode ser desativada pelo tenant
        if ($isAutoTriggered && ! $settings->auto_issue_nfce) {
            FiscalEmissionSkipped::dispatch($sale, 'auto_issue_disabled');
            return null;
        }

        FiscalEmissionRequested::dispatch($sale, $mode, $isAutoTriggered);

        try {
            $document = $this->requestAction->execute(new RequestFiscalDocumentDTO(
                storeId:         $sale->store_id,
                saleId:          $sale->uuid,
                type:            $mode->documentType(),
                provider:        FiscalProviderEnum::Stub,
                contingencyMode: false,
            ));

            FiscalEmissionQueued::dispatch($sale, $document);

            return $document;
        } catch (\Throwable $e) {
            FiscalEmissionFailed::dispatch($sale, $e);
            return null;
        }
    }
}
