<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canViewCost = $request->user()?->can('products.view_cost') ?? false;

        return [
            'uuid'                   => $this->uuid,
            'price_list_id'          => $this->price_list_id,
            'product'                => $this->whenLoaded('product', fn () => [
                'uuid' => $this->product->uuid,
                'name' => $this->product->name,
                'code' => $this->product->code,
            ]),
            'variant'                => $this->whenLoaded('variant', fn () => [
                'uuid'       => $this->variant->uuid,
                'sku'        => $this->variant->sku,
                'name'       => $this->variant->name,
                'price_cents'=> $this->variant->price_cents,
            ]),
            'price_cents'            => $this->price_cents,
            'price_formatted'        => 'R$ ' . number_format($this->price_cents / 100, 2, ',', '.'),
            'min_price_cents'        => $this->min_price_cents,
            'min_price_formatted'    => $this->min_price_cents !== null
                ? 'R$ ' . number_format($this->min_price_cents / 100, 2, ',', '.')
                : null,
            'cost_price_cents'       => $canViewCost ? $this->cost_price_cents : null,
            'packaging_price_cents'  => $this->packaging_price_cents,
            'packaging_qty'          => $this->packaging_qty,
            'valid_from'             => $this->valid_from?->toDateString(),
            'valid_to'               => $this->valid_to?->toDateString(),
            'is_currently_valid'     => $this->isCurrentlyValid(),
        ];
    }
}
