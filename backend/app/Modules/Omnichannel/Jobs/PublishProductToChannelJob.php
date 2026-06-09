<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Jobs;

use App\Core\Audit\Services\CorrelationContext;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Omnichannel\Enums\ChannelSyncStatusEnum;
use App\Modules\Omnichannel\Events\ChannelSyncFailed;
use App\Modules\Omnichannel\Events\ProductPublishedToChannel;
use App\Modules\Omnichannel\Models\ChannelProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Publication pipeline stub.
 *
 * Currently marks the record as synced (no real API call).
 * Future: resolve channel type → call ChannelAdapter (Instagram, ML, etc.)
 *
 * Carries correlation_id from the dispatching request for traceability.
 */
final class PublishProductToChannelJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;   // seconds between retries

    private readonly string $correlationId;

    public function __construct(
        private readonly string $channelProductUuid,
        private readonly string $tenantId,
        string $correlationId = '',
    ) {
        // Capture correlation context at dispatch time for traceability
        $this->correlationId = $correlationId !== ''
            ? $correlationId
            : CorrelationContext::getCorrelationId();
    }

    public function handle(): void
    {
        // Restore correlation context for this job's log entries
        CorrelationContext::setCorrelationId($this->correlationId);
        // Job enfileirado roda sem contexto de tenant: estabelece para que o
        // escopo global filtre corretamente as escritas (markSynced/markFailed).
        TenantContext::set($this->tenantId);

        try {
            $this->publish();
        } finally {
            TenantContext::clear();
        }
    }

    private function publish(): void
    {
        $channelProduct = ChannelProduct::where('uuid', $this->channelProductUuid)
            ->where('tenant_id', $this->tenantId)
            ->first();

        if ($channelProduct === null) {
            Log::warning('PublishProductToChannelJob: ChannelProduct not found', [
                'channel_product_uuid' => $this->channelProductUuid,
                'tenant_id'            => $this->tenantId,
                'correlation_id'       => $this->correlationId,
            ]);

            return;
        }

        try {
            // Stub: no real external API call yet.
            // Future: resolve channel adapter → $adapter->publish($channelProduct)
            $externalReference = 'stub-' . $channelProduct->uuid;

            $channelProduct->markSynced($externalReference);

            ProductPublishedToChannel::dispatch(
                $channelProduct,
                $channelProduct->channel,
                $channelProduct->product_id,
            );

            Log::info('PublishProductToChannelJob: product synced', [
                'channel_product_uuid' => $this->channelProductUuid,
                'external_reference'   => $externalReference,
                'correlation_id'       => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            $channelProduct->markFailed();

            ChannelSyncFailed::dispatch(
                $channelProduct,
                $e->getMessage(),
                $this->attempts(),
            );

            Log::error('PublishProductToChannelJob: sync failed', [
                'channel_product_uuid' => $this->channelProductUuid,
                'error'                => $e->getMessage(),
                'attempt'              => $this->attempts(),
                'correlation_id'       => $this->correlationId,
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('PublishProductToChannelJob: exhausted retries', [
            'channel_product_uuid' => $this->channelProductUuid,
            'tenant_id'            => $this->tenantId,
            'error'                => $exception->getMessage(),
            'correlation_id'       => $this->correlationId,
        ]);
    }
}
