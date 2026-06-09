<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Jobs;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Fiscal\Enums\FiscalDocumentStatusEnum;
use App\Modules\Fiscal\Enums\FiscalEventTypeEnum;
use App\Modules\Fiscal\Events\FiscalDocumentAuthorized;
use App\Modules\Fiscal\Models\FiscalDocument;
use App\Modules\Fiscal\Models\FiscalEvent;
use App\Modules\Fiscal\Models\FiscalResponse;
use App\Modules\Fiscal\Models\TenantFiscalSettings;
use App\Modules\Fiscal\Services\FiscalProviderResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Sincroniza o status de documentos em contingência com o provedor.
 *
 * Executado periodicamente para transmitir documentos emitidos em contingência
 * assim que a SEFAZ ficar disponível novamente.
 *
 * Agendado em routes/console.php: a cada 15 minutos nos horários de operação.
 */
final class FiscalStatusSyncJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries   = 1;
    public int $timeout = 300;

    public function __construct(
        public readonly string $tenantId,
    ) {
        $this->onQueue(config('fiscal.queue', 'fiscal'));
    }

    public function handle(FiscalProviderResolver $resolver): void
    {
        TenantContext::set($this->tenantId);

        try {
            $settings = TenantFiscalSettings::withoutTenantScope()
                ->where('tenant_id', $this->tenantId)
                ->where('is_active', true)
                ->first();

            if ($settings === null) {
                return;
            }

            // Busca documentos em contingência ou processando há mais de 5 min
            $documents = FiscalDocument::withoutTenantScope()
                ->where('tenant_id', $this->tenantId)
                ->whereIn('status', [
                    FiscalDocumentStatusEnum::Contingency->value,
                    FiscalDocumentStatusEnum::Processing->value,
                ])
                ->where('created_at', '<', now()->subMinutes(5))
                ->get();

            foreach ($documents as $document) {
                try {
                    $provider = $resolver->resolve($document->provider);
                    $result   = $provider->checkStatus($settings, $document);

                    FiscalResponse::create([
                        'fiscal_document_id' => $document->uuid,
                        'provider'           => $document->provider,
                        'status_code'        => $result->statusCode,
                        'message'            => $result->message,
                        'raw_response'       => $result->rawResponse,
                    ]);

                    if ($result->isAuthorized()) {
                        FiscalEvent::create([
                            'fiscal_document_id' => $document->uuid,
                            'event_type'         => FiscalEventTypeEnum::Authorize,
                            'payload'            => ['via_sync' => true],
                            'provider'           => $document->provider,
                        ]);

                        $document->update([
                            'status'     => FiscalDocumentStatusEnum::Authorized,
                            'access_key' => $result->accessKey,
                            'protocol'   => $result->protocol,
                            'number'     => $result->number,
                            'xml_path'   => $result->xmlPath,
                            'issued_at'  => now(),
                        ]);

                        FiscalDocumentAuthorized::dispatch($document->fresh());
                    }
                } catch (\Throwable) {
                    // Falha isolada não interrompe outros documentos
                }
            }
        } finally {
            TenantContext::clear();
        }
    }
}
