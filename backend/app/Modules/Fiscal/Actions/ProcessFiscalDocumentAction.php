<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Actions;

use App\Modules\Fiscal\Data\FiscalIssueData;
use App\Modules\Fiscal\Data\FiscalItemData;
use App\Modules\Fiscal\Data\FiscalProviderResult;
use App\Modules\Fiscal\Enums\FiscalDocumentStatusEnum;
use App\Modules\Fiscal\Enums\FiscalEnvironmentEnum;
use App\Modules\Fiscal\Enums\FiscalEventTypeEnum;
use App\Modules\Fiscal\Events\FiscalDocumentAuthorized;
use App\Modules\Fiscal\Events\FiscalDocumentRejected;
use App\Modules\Fiscal\Models\FiscalDocument;
use App\Modules\Fiscal\Models\FiscalDocumentItem;
use App\Modules\Fiscal\Models\FiscalEvent;
use App\Modules\Fiscal\Models\FiscalResponse;
use App\Modules\Fiscal\Models\TenantFiscalSettings;
use App\Modules\Fiscal\Services\FiscalProviderResolver;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;

/**
 * Processa a emissão do documento fiscal via adapter do provedor.
 *
 * Chamado exclusivamente pelo FiscalIssueJob.
 *
 * Responsabilidades:
 * 1. Resolver o adapter correto via FiscalProviderResolver.
 * 2. Construir FiscalIssueData a partir do documento.
 * 3. Chamar o provedor.
 * 4. Gravar FiscalEvent + FiscalResponse independente do resultado.
 * 5. Atualizar o status do documento.
 * 6. Disparar evento de domínio.
 */
final readonly class ProcessFiscalDocumentAction
{
    public function __construct(
        private FiscalProviderResolver $providerResolver,
    ) {}

    public function execute(FiscalDocument $document): void
    {
        if (! $document->canProcess()) {
            return;
        }

        DB::transaction(function () use ($document): void {
            // Marca como PROCESSING para evitar duplicação em retries concorrentes
            $document->update(['status' => FiscalDocumentStatusEnum::Processing]);

            $settings = TenantFiscalSettings::withoutTenantScope()
                ->where('tenant_id', $document->tenant_id)
                ->where('is_active', true)
                ->first();

            if ($settings === null) {
                $document->update([
                    'status'        => FiscalDocumentStatusEnum::Error,
                    'error_message' => 'Configuração fiscal não encontrada ou inativa.',
                ]);
                return;
            }

            $provider  = $this->providerResolver->resolve($document->provider);
            $issueData = $this->buildIssueData($document, $settings);

            // Grava evento de emissão antes de chamar o provedor
            $this->recordEvent($document, FiscalEventTypeEnum::Issue, ['issue_data' => $issueData->metadata]);

            try {
                $result = $provider->issue($settings, $issueData);
            } catch (\Throwable $e) {
                // Erro técnico (rede, timeout, configuração)
                $this->recordResponse($document, null, 0, $e->getMessage(), ['error' => $e->getMessage()]);
                $this->recordEvent($document, FiscalEventTypeEnum::Reject, ['error' => $e->getMessage()]);

                $document->update([
                    'status'        => FiscalDocumentStatusEnum::Error,
                    'error_message' => $e->getMessage(),
                ]);

                throw $e; // propaga para o job gerenciar retries
            }

            // Grava a resposta do provedor sempre (autorizado, rejeitado ou contingência)
            $this->recordResponse(
                $document,
                $result->statusCode,
                $result->statusCode ?? 0,
                $result->message,
                $result->rawResponse,
            );

            $this->applyResult($document, $result);
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buildIssueData(FiscalDocument $document, TenantFiscalSettings $settings): FiscalIssueData
    {
        $items = $document->items->map(fn (FiscalDocumentItem $item) => new FiscalItemData(
            variantUuid:    $item->product_variant_id,
            description:    $item->tax_snapshot['description'] ?? 'Produto',
            quantity:       $item->quantity,
            unitPriceCents: $item->unit_price_cents,
            totalCents:     $item->total_cents,
            taxData:        $item->tax_snapshot,
        ))->all();

        return new FiscalIssueData(
            documentUuid: $document->uuid,
            type:         $document->type,
            environment:  $settings->environment ?? FiscalEnvironmentEnum::Homologation,
            series:       $document->series ?? $settings->series ?? 1,
            number:       $document->number ?? $settings->next_number ?? 1,
            saleUuid:     $document->sale_id,
            storeUuid:    $document->store_id,
            items:        $items,
            metadata:     ['provider' => $document->provider->value],
        );
    }

    private function applyResult(FiscalDocument $document, FiscalProviderResult $result): void
    {
        $update = ['status' => $result->status];

        if ($result->isAuthorized()) {
            $update += [
                'access_key' => $result->accessKey,
                'protocol'   => $result->protocol,
                'xml_path'   => $result->xmlPath,
                'pdf_path'   => $result->pdfPath,
                'number'     => $result->number,
                'issued_at'  => now(),
            ];
            $this->recordEvent($document, FiscalEventTypeEnum::Authorize, ['access_key' => $result->accessKey]);

            $document->update($update);
            FiscalDocumentAuthorized::dispatch($document->fresh());

        } elseif ($result->isRejected()) {
            $update['error_message'] = $result->message;
            $this->recordEvent($document, FiscalEventTypeEnum::Reject, ['message' => $result->message, 'code' => $result->statusCode]);

            $document->update($update);
            FiscalDocumentRejected::dispatch($document->fresh(), $result->message ?? '');

        } elseif ($result->isContingency()) {
            $update['error_message'] = $result->message;
            $this->recordEvent($document, FiscalEventTypeEnum::Contingency, ['message' => $result->message]);

            $document->update($update);

        } else {
            $document->update($update);
        }
    }

    private function recordEvent(FiscalDocument $document, FiscalEventTypeEnum $type, array $payload = []): void
    {
        FiscalEvent::create([
            'fiscal_document_id' => $document->uuid,
            'event_type'         => $type,
            'payload'            => $payload,
            'response'           => null,
            'provider'           => $document->provider,
        ]);
    }

    private function recordResponse(
        FiscalDocument $document,
        ?int           $statusCode,
        int            $httpCode,
        ?string        $message,
        array          $rawResponse,
    ): void {
        FiscalResponse::create([
            'fiscal_document_id' => $document->uuid,
            'provider'           => $document->provider,
            'status_code'        => $statusCode,
            'message'            => $message,
            'raw_response'       => $rawResponse,
        ]);
    }
}
