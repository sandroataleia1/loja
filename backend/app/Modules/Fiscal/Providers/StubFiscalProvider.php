<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Providers;

use App\Modules\Fiscal\Contracts\FiscalProviderContract;
use App\Modules\Fiscal\Data\FiscalIssueData;
use App\Modules\Fiscal\Data\FiscalProviderResult;
use App\Modules\Fiscal\Models\FiscalDocument;
use App\Modules\Fiscal\Models\TenantFiscalSettings;

/**
 * Provedor fiscal de desenvolvimento/testes.
 *
 * Nunca acessa a SEFAZ nem nenhum provedor externo.
 * Simula uma autorização imediata com dados fictícios.
 * Comportamento controlável via ENV para simular rejeição e contingência.
 *
 * ENV: FISCAL_STUB_MODE = 'authorized' | 'rejected' | 'contingency'
 */
final class StubFiscalProvider implements FiscalProviderContract
{
    public function issue(TenantFiscalSettings $settings, FiscalIssueData $data): FiscalProviderResult
    {
        $mode = config('fiscal.stub_mode', 'authorized');

        return match ($mode) {
            'rejected'    => $this->simulateRejection(),
            'contingency' => $this->simulateContingency(),
            default       => $this->simulateAuthorization($data),
        };
    }

    public function cancel(TenantFiscalSettings $settings, FiscalDocument $document, string $reason): FiscalProviderResult
    {
        $cancelProtocol = 'CANCEL-' . now()->format('YmdHis');

        return FiscalProviderResult::cancelled(
            protocol:    $cancelProtocol,
            rawResponse: [
                'provider'      => 'stub',
                'cancel_reason' => $reason,
                'cancelled_at'  => now()->toISOString(),
            ],
        );
    }

    public function checkStatus(TenantFiscalSettings $settings, FiscalDocument $document): FiscalProviderResult
    {
        // Stub: sempre retorna autorizado para documentos em contingência
        if ($document->access_key !== null) {
            return FiscalProviderResult::authorized(
                accessKey:   $document->access_key,
                protocol:    $document->protocol ?? 'STUB-SYNC-' . now()->format('YmdHis'),
                number:      $document->number ?? 1,
                rawResponse: ['provider' => 'stub', 'status_check' => true],
            );
        }

        return FiscalProviderResult::contingency('Documento ainda não transmitido.');
    }

    // ── Helpers de simulação ──────────────────────────────────────────────────

    private function simulateAuthorization(FiscalIssueData $data): FiscalProviderResult
    {
        $number    = $data->number;
        $cnpj      = '00000000000000'; // stub CNPJ
        $model     = $data->type->modelo();
        $env       = $data->environment->tpAmb();
        $accessKey = $this->generateFakeAccessKey($cnpj, $model, $number, $env);
        $protocol  = 'STUB-' . now()->format('YmdHis');

        return FiscalProviderResult::authorized(
            accessKey:   $accessKey,
            protocol:    $protocol,
            number:      $number,
            xmlPath:     "fiscal/stub/{$data->documentUuid}.xml",
            pdfPath:     "fiscal/stub/{$data->documentUuid}.pdf",
            rawResponse: [
                'provider'       => 'stub',
                'document_uuid'  => $data->documentUuid,
                'environment'    => $data->environment->value,
                'series'         => $data->series,
                'number'         => $number,
                'authorized_at'  => now()->toISOString(),
            ],
        );
    }

    private function simulateRejection(): FiscalProviderResult
    {
        return FiscalProviderResult::rejected(
            message:     '[STUB] Rejeição 539: CNPJ do emitente inválido.',
            statusCode:  539,
            rawResponse: ['provider' => 'stub', 'rejection_code' => 539],
        );
    }

    private function simulateContingency(): FiscalProviderResult
    {
        return FiscalProviderResult::contingency(
            '[STUB] SEFAZ indisponível — emitindo em contingência.',
            ['provider' => 'stub'],
        );
    }

    private function generateFakeAccessKey(string $cnpj, int $model, int $number, int $env): string
    {
        // Formato: cUF(2) + AAMM(4) + CNPJ(14) + mod(2) + serie(3) + nNF(9) + tpEmis(1) + cNF(8) + cDV(1)
        $uf     = '35'; // SP
        $aamm   = now()->format('ym');
        $mod    = str_pad((string) $model, 2, '0', STR_PAD_LEFT);
        $serie  = '001';
        $nNF    = str_pad((string) $number, 9, '0', STR_PAD_LEFT);
        $tpEmis = '1';
        $cNF    = str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);

        $key43 = $uf . $aamm . $cnpj . $mod . $serie . $nNF . $tpEmis . $cNF;
        $dv    = $this->calculateKeyDv($key43);

        return $key43 . $dv;
    }

    private function calculateKeyDv(string $key43): int
    {
        $sequence = [2, 3, 4, 5, 6, 7, 8, 9];
        $sum      = 0;

        foreach (array_reverse(str_split($key43)) as $index => $digit) {
            $sum += (int) $digit * $sequence[$index % 8];
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
