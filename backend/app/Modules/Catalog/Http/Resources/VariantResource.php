<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'             => $this->uuid,
            'code'             => $this->code,
            'product_id'       => $this->product_id,
            'sku'              => $this->sku,
            'name'             => $this->name,
            'barcode'          => $this->barcode,
            'gtin'             => $this->gtin,
            'price_cents'      => $this->price_cents,
            'cost_cents'       => $this->cost_cents,
            'compare_at_cents' => $this->compare_at_cents,
            'sale_price'       => $this->price_cents / 100,
            'cost_price'       => $this->cost_cents ? $this->cost_cents / 100 : null,
            'has_discount'     => $this->hasDiscount(),
            'discount_percent' => $this->discountPercent(),
            'weight_g'         => $this->weight_g,
            'dimensions'       => $this->dimensions,
            'is_active'        => $this->is_active,
            'is_default'       => $this->is_default,
            'sort_order'       => $this->sort_order,
            'grid_combination' => $this->grid_combination,
            'ncm'              => $this->ncm,
            'cest'             => $this->cest,
            'cfop_default'     => $this->cfop_default,
            'origin_code'      => $this->origin_code,
            'tax_profile_id'   => $this->tax_profile_id,
            'attributes'       => $this->whenLoaded('attributes', fn () => AttributeResource::collection($this->attributes)),
            'images'           => $this->whenLoaded('images', fn () => ImageResource::collection($this->images)),
            'created_at'       => $this->created_at?->toISOString(),
            'updated_at'       => $this->updated_at?->toISOString(),
        ];
    }
}
