<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PriceListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                 => $this->uuid,
            'name'                 => $this->name,
            'code'                 => $this->code,
            'type'                 => $this->type->value,
            'type_label'           => $this->type->label(),
            'currency'             => $this->currency,
            'max_discount_percent' => (float) $this->max_discount_percent,
            'is_default'           => $this->is_default,
            'is_active'            => $this->is_active,
            'valid_from'           => $this->valid_from?->toDateString(),
            'valid_to'             => $this->valid_to?->toDateString(),
            'products_count'       => $this->whenLoaded(
                'productPrices',
                fn () => $this->productPrices->count(),
                fn () => $this->productPrices()->count(),
            ),
            'created_at'           => $this->created_at?->toISOString(),
        ];
    }
}
