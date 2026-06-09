<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Actions;

use App\Modules\Fiscal\Enums\FiscalDocumentStatusEnum;
use App\Modules\Fiscal\Enums\FiscalEventTypeEnum;
use App\Modules\Fiscal\Events\FiscalDocumentCancelled;
use App\Modules\Fiscal\Models\FiscalDocument;
use App\Modules\Fiscal\Models\FiscalEvent;
use App\Modules\Fiscal\Models\FiscalResponse;
use App\Modules\Fiscal\Models\TenantFiscalSettings;
use App\Modules\Fiscal\Services\FiscalProviderResolver;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;

final readonly class CancelFiscalDocumentAction
{
    public function __construct(
        private FiscalProviderResolver $providerResolver,
    ) {}

    /**
     * Cancela um documento fiscal já autorizado via provedor.
     *
     * Cancelamento fiscal é INDEPENDENTE do cancelamento da venda.
     * Uma venda pode ser cancelada sem cancelar o documento fiscal
     * (e vice-versa, dependendo do prazo SEFAZ de 24h/30 dias).
     */
    public function execute(FiscalDocument $document, ?string $reason = null): FiscalDocument
    {
        if (! $document->status->canCancel()) {
            throw new BusinessException(
                "Documento fiscal não pode ser cancelado no status '{$document->status->label()}'. "
                . 'Apenas documentos autorizados podem ser cancelados.'
            );
        }

        return DB::transaction(function () use ($document, $reason): FiscalDocument {
            $justification = $reason ?? 'Cancelamento solicitado pelo operador.';

            $settings = TenantFiscalSettings::withoutTenantScope()
                ->where('tenant_id', $document->tenant_id)
                ->where('is_active', true)
                ->first();

            // Grava evento de cancelamento antes de chamar o provedor
            FiscalEvent::create([
                'fiscal_document_id' => $document->uuid,
                'event_type'         => FiscalEventTypeEnum::Cancel,
                'payload'            => ['reason' => $justification],
                'provider'           => $document->provider,
            ]);

            if ($settings !== null) {
                try {
                    $provider = $this->providerResolver->resolve($document->provider);
                    $result   = $provider->cancel($settings, $document, $justification);

                    FiscalResponse::create([
                        'fiscal_document_id' => $document->uuid,
                        'provider'           => $document->provider,
                        'status_code'        => $result->statusCode,
                        'message'            => $result->message,
                        'raw_response'       => $result->rawResponse,
                    ]);
                } catch (\Throwable $e) {
                    // Log mas não bloqueia — cancelamento administrativo prossegue
                    FiscalResponse::create([
                        'fiscal_document_id' => $document->uuid,
                        'provider'           => $document->provider,
                        'status_code'        => 0,
                        'message'            => 'Erro ao comunicar cancelamento ao provedor: ' . $e->getMessage(),
                        'raw_response'       => ['error' => $e->getMessage()],
                    ]);
                }
            }

            $document->update([
                'status'       => FiscalDocumentStatusEnum::Cancelled,
                'cancelled_at' => now(),
            ]);

            FiscalDocumentCancelled::dispatch($document->fresh(), $reason);

            return $document->fresh();
        });
    }
}
