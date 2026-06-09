<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductPriceHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'              => $this->uuid,
            'product_id'        => $this->product_id,
            'variant_id'        => $this->variant_id,
            'old_price_cents'   => $this->old_price_cents,
            'new_price_cents'   => $this->new_price_cents,
            'variation_cents'   => $this->variationCents(),
            'variation_percent' => $this->variationPercent(),
            'reason'            => $this->reason,
            'changed_by'        => $this->changed_by,
            'changed_at'        => $this->changed_at?->toISOString(),
        ];
    }
}
