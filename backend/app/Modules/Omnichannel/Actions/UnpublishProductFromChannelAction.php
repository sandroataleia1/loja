<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Actions;

use App\Modules\Omnichannel\Events\ProductUnpublishedFromChannel;
use App\Modules\Omnichannel\Models\Channel;
use App\Modules\Omnichannel\Models\ChannelProduct;
use App\Shared\Exceptions\NotFoundException;

final readonly class UnpublishProductFromChannelAction
{
    public function execute(string $channelId, string $productId, string $tenantId): void
    {
        $channelProduct = ChannelProduct::where('channel_id', $channelId)
            ->where('product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($channelProduct === null) {
            throw new NotFoundException('Product not found in this channel.');
        }

        $channel = Channel::where('uuid', $channelId)->firstOrFail();

        $channelProduct->update(['is_published' => false]);

        ProductUnpublishedFromChannel::dispatch($channelProduct, $channel, $productId);
    }
}
