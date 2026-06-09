<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Services;

use App\Modules\Omnichannel\Actions\PublishProductToChannelAction;
use App\Modules\Omnichannel\Actions\UnpublishProductFromChannelAction;
use App\Modules\Omnichannel\DTOs\PublishProductDTO;
use App\Modules\Omnichannel\Enums\ChannelSyncStatusEnum;
use App\Modules\Omnichannel\Models\Channel;
use App\Modules\Omnichannel\Models\ChannelProduct;
use Illuminate\Support\Collection;

/**
 * Publication pipeline orchestrator.
 *
 * Coordinates multi-channel publication: one product → many channels,
 * or one channel → many products. Handles bulk operations and retry queuing.
 */
final readonly class ChannelPublicationService
{
    public function __construct(
        private PublishProductToChannelAction   $publishAction,
        private UnpublishProductFromChannelAction $unpublishAction,
    ) {}

    /**
     * Publish a product to all active channels for a tenant.
     * Each channel gets its own async job — failures are isolated.
     */
    public function publishToAllActiveChannels(string $productId, string $tenantId): void
    {
        Channel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->each(function (Channel $channel) use ($productId, $tenantId): void {
                $this->publishAction->execute(new PublishProductDTO(
                    tenantId:  $tenantId,
                    channelId: $channel->uuid,
                    productId: $productId,
                ));
            });
    }

    /**
     * Mark existing ChannelProduct records as outdated when a product changes.
     * The sync job will re-publish them on next retry cycle.
     */
    public function markOutdated(string $productId, string $tenantId): void
    {
        ChannelProduct::where('product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->where('sync_status', ChannelSyncStatusEnum::Synced)
            ->update(['sync_status' => ChannelSyncStatusEnum::Outdated]);
    }

    /**
     * Return all ChannelProduct records that need a sync attempt.
     *
     * @return Collection<int, ChannelProduct>
     */
    public function pendingSync(string $tenantId): Collection
    {
        return ChannelProduct::where('tenant_id', $tenantId)
            ->needsSync()
            ->with('channel')
            ->get();
    }

    /**
     * Unpublish a product from a specific channel.
     */
    public function unpublish(string $channelId, string $productId, string $tenantId): void
    {
        $this->unpublishAction->execute($channelId, $productId, $tenantId);
    }
}
