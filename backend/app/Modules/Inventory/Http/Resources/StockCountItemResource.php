<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Resources;

use App\Modules\Catalog\Http\Resources\VariantResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StockCountItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'              => $this->uuid,
            'variant_id'        => $this->variant_id,
            'system_quantity'   => $this->system_quantity,
            'counted_quantity'  => $this->counted_quantity,
            'difference'        => $this->difference,
            'variant'           => $this->whenLoaded('variant', fn () => new VariantResource($this->variant)),
        ];
    }
}
