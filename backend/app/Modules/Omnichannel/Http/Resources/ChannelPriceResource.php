<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ChannelPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'              => $this->uuid,
            'channel_id'        => $this->channel_id,
            'product_id'        => $this->product_id,
            'price'             => $this->price,
            'promotional_price' => $this->promotional_price,
            'effective_price'   => $this->effectivePrice(),
            'promotion_active'  => $this->isPromotionActive(),
            'starts_at'         => $this->starts_at?->toISOString(),
            'ends_at'           => $this->ends_at?->toISOString(),
        ];
    }
}
