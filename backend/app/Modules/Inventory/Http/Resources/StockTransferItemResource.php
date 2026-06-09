<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Resources;

use App\Modules\Catalog\Http\Resources\VariantResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StockTransferItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                => $this->uuid,
            'variant_id'          => $this->variant_id,
            'quantity_requested'  => $this->quantity_requested,
            'quantity_sent'       => $this->quantity_sent,
            'quantity_received'   => $this->quantity_received,
            'variant'             => $this->whenLoaded('variant', fn () => new VariantResource($this->variant)),
        ];
    }
}
