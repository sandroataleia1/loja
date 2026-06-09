<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Actions;

use App\Modules\Omnichannel\DTOs\UpdateChannelPriceDTO;
use App\Modules\Omnichannel\Models\ChannelPrice;

final readonly class UpdateChannelPriceAction
{
    public function execute(UpdateChannelPriceDTO $dto): ChannelPrice
    {
        return ChannelPrice::updateOrCreate(
            [
                'channel_id' => $dto->channelId,
                'product_id' => $dto->productId,
            ],
            [
                'tenant_id'         => $dto->tenantId,
                'price'             => $dto->price,
                'promotional_price' => $dto->promotionalPrice,
                'starts_at'         => $dto->startsAt,
                'ends_at'           => $dto->endsAt,
            ]
        );
    }
}
