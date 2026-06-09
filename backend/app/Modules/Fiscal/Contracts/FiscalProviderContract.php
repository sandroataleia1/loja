<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Contracts;

use App\Modules\Fiscal\Data\FiscalIssueData;
use App\Modules\Fiscal\Data\FiscalProviderResult;
use App\Modules\Fiscal\Models\FiscalDocument;
use App\Modules\Fiscal\Models\TenantFiscalSettings;

/**
 * Contrato do adapter de provedor fiscal.
 *
 * Cada provedor (Stub, Focus NFe, Nuvem Fiscal, etc.) implementa este contrato.
 * O sistema nunca depende de uma implementação concreta — apenas deste contrato.
 *
 * Fluxo esperado de cada método:
 *   - Retornar FiscalProviderResult com status, access_key, protocol e raw_response.
 *   - Nunca lançar exceção para rejeições SEFAZ (retornar result com isRejected=true).
 *   - Lançar exceção apenas para erros técnicos (rede, timeout, configuração inválida).
 */
interface FiscalProviderContract
{
    /**
     * Emite o documento fiscal no provedor.
     *
     * @param  TenantFiscalSettings $settings  Configuração fiscal do tenant
     * @param  FiscalIssueData      $data      Dados do documento a emitir
     */
    public function issue(TenantFiscalSettings $settings, FiscalIssueData $data): FiscalProviderResult;

    /**
     * Cancela um documento autorizado.
     *
     * @param  TenantFiscalSettings $settings  Configuração fiscal do tenant
     * @param  FiscalDocument       $document  Documento a cancelar (status Authorized)
     * @param  string               $reason    Justificativa (mín. 15 chars SEFAZ)
     */
    public function cancel(TenantFiscalSettings $settings, FiscalDocument $document, string $reason): FiscalProviderResult;

    /**
     * Consulta o status atual de um documento no provedor.
     * Usado pelo FiscalStatusSyncJob para documentos em Contingency/Processing.
     */
    public function checkStatus(TenantFiscalSettings $settings, FiscalDocument $document): FiscalProviderResult;
}
