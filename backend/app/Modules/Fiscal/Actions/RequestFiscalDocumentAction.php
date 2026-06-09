<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Fiscal\DTOs\RequestFiscalDocumentDTO;
use App\Modules\Fiscal\Enums\FiscalDocumentStatusEnum;
use App\Modules\Fiscal\Enums\FiscalEventTypeEnum;
use App\Modules\Fiscal\Events\FiscalDocumentRequested;
use App\Modules\Fiscal\Jobs\FiscalIssueJob;
use App\Modules\Fiscal\Models\FiscalDocument;
use App\Modules\Fiscal\Models\FiscalEvent;
use App\Shared\Exceptions\BusinessException;

final readonly class RequestFiscalDocumentAction
{
    /**
     * Cria um FiscalDocument PENDING e despacha o FiscalIssueJob.
     *
     * Idempotente por sale_id + type: se já existe documento não-terminal
     * e não pode ser retentado, retorna erro sem criar duplicata.
     */
    public function execute(RequestFiscalDocumentDTO $dto): FiscalDocument
    {
        $tenantId = TenantContext::getIdOrFail();

        // Evita duplicata de documento para a mesma venda+tipo ainda em andamento
        if ($dto->saleId !== null) {
            $existing = FiscalDocument::where('sale_id', $dto->saleId)
                ->where('type', $dto->type->value)
                ->whereNotIn('status', [
                    FiscalDocumentStatusEnum::Cancelled->value,
                    FiscalDocumentStatusEnum::Error->value,
                    FiscalDocumentStatusEnum::Rejected->value,
                ])
                ->first();

            if ($existing !== null && ! $existing->status->canRetry()) {
                throw new BusinessException(
                    "Já existe documento fiscal {$dto->type->label()} para esta venda no status '{$existing->status->label()}'."
                );
            }
        }

        $document = FiscalDocument::create([
            'store_id'         => $dto->storeId,
            'sale_id'          => $dto->saleId,
            'type'             => $dto->type,
            'status'           => FiscalDocumentStatusEnum::Pending,
            'provider'         => $dto->provider,
            'contingency_mode' => $dto->contingencyMode,
        ]);

        // Registra o evento de solicitação
        FiscalEvent::create([
            'fiscal_document_id' => $document->uuid,
            'event_type'         => FiscalEventTypeEnum::Issue,
            'payload'            => [
                'requested_by'      => 'manual',
                'contingency_mode'  => $dto->contingencyMode,
            ],
            'provider' => $dto->provider,
        ]);

        FiscalDocumentRequested::dispatch($document);

        FiscalIssueJob::dispatch($document->uuid, $tenantId);

        return $document;
    }
}
