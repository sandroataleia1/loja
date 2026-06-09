<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Actions;

use App\Modules\Omnichannel\DTOs\PublishProductDTO;
use App\Modules\Omnichannel\Enums\ChannelSyncStatusEnum;
use App\Modules\Omnichannel\Events\ProductPublishedToChannel;
use App\Modules\Omnichannel\Jobs\PublishProductToChannelJob;
use App\Modules\Omnichannel\Models\Channel;
use App\Modules\Omnichannel\Models\ChannelProduct;
use App\Shared\Exceptions\BusinessException;

final readonly class PublishProductToChannelAction
{
    public function execute(PublishProductDTO $dto): ChannelProduct
    {
        $channel = Channel::where('uuid', $dto->channelId)
            ->where('tenant_id', $dto->tenantId)
            ->firstOrFail();

        if (! $channel->is_active) {
            throw new BusinessException("Channel '{$channel->name}' is not active.");
        }

        // Upsert the publication record — idempotent
        $channelProduct = ChannelProduct::updateOrCreate(
            [
                'channel_id' => $dto->channelId,
                'product_id' => $dto->productId,
            ],
            [
                'tenant_id'   => $dto->tenantId,
                'sync_status' => ChannelSyncStatusEnum::Pending,
                'metadata'    => $dto->metadataOverrides,
            ]
        );

        // Async publication — enqueue the actual channel sync job
        if ($channel->isAsyncChannel()) {
            PublishProductToChannelJob::dispatch(
                $channelProduct->uuid,
                $dto->tenantId,
            );
        } else {
            // PDV channels sync immediately — mark as synced
            $channelProduct->markSynced('pdv-internal');
        }

        ProductPublishedToChannel::dispatch($channelProduct, $channel, $dto->productId);

        return $channelProduct->fresh();
    }
}
