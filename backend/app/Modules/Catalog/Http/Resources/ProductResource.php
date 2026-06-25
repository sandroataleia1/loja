<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use App\Modules\Media\Http\Resources\MediaAssetResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                   => $this->uuid,
            'code'                   => $this->code,
            'name'                   => $this->name,
            'slug'                   => $this->slug,
            'type'                   => $this->type->value,
            'type_label'             => $this->type->label(),
            'unit_id'                => $this->unit_id,
            'unit'                   => $this->whenLoaded('unit', fn () => [
                'uuid'   => $this->unit->uuid,
                'code'   => $this->unit->code,
                'name'   => $this->unit->name,
                'symbol' => $this->unit->symbol,
            ]),
            'ncm'                    => $this->ncm,
            'cest'                   => $this->cest,
            'cfop_default'           => $this->cfop_default,
            'origin_code'            => $this->origin_code?->value,
            'origin_code_label'      => $this->origin_code?->label(),
            'status'                 => $this->status->value,
            'status_label'           => $this->status->label(),
            'visibility'             => $this->visibility?->value,
            'visibility_label'       => $this->visibility?->label(),
            'base_price_cents'       => $this->base_price_cents,
            'base_price'             => $this->base_price_formatted,
            'cost_price_cents'       => $this->cost_price_cents,
            'cost_price'             => $this->cost_price_formatted,
            'weight_gross_g'         => $this->weight_gross_g,
            'weight_net_g'           => $this->weight_net_g,
            'dimensions'             => $this->dimensions,
            'qr_code_url'            => $this->qr_code_url,
            'season'                 => $this->season,
            'launch_date'            => $this->launch_date?->toDateString(),
            'is_featured'            => $this->is_featured,
            'is_digital'             => $this->is_digital,
            'is_publishable'         => $this->is_publishable,
            'is_ready_to_publish'    => $this->isReadyToPublish(),
            'description'            => $this->description,
            'short_description'      => $this->short_description,
            'marketing_description'  => $this->marketing_description,
            'internal_notes'         => $this->internal_notes,
            'seo'                    => $this->seo,
            // Analytics foundation — AI/campaigns future
            'sales_velocity'         => $this->sales_velocity,
            'days_without_sale'      => $this->days_without_sale,
            'stock_age'              => $this->stock_age,
            'last_sale_at'           => $this->last_sale_at?->toISOString(),
            // Relationships
            'brand'                  => $this->whenLoaded('brand',                fn () => new BrandResource($this->brand)),
            'categories'             => $this->whenLoaded('categories',           fn () => CategoryResource::collection($this->categories)),
            'collection'             => $this->whenLoaded('collection',           fn () => new CollectionResource($this->collection)),
            'commercial_collections' => $this->whenLoaded('commercialCollections', fn () => CollectionResource::collection($this->commercialCollections)),
            'grid'                   => $this->whenLoaded('grid',                 fn () => new GridResource($this->grid)),
            'variants'               => $this->whenLoaded('variants',             fn () => VariantResource::collection($this->variants)),
            'images'                 => $this->whenLoaded('images',               fn () => ImageResource::collection($this->images)),
            'media'                  => $this->whenLoaded('media',                fn () => MediaAssetResource::collection($this->media)),
            'tags'                   => $this->whenLoaded('tags',                 fn () => $this->tags->pluck('name')),
            'created_at'             => $this->created_at?->toISOString(),
            'updated_at'             => $this->updated_at?->toISOString(),
        ];
    }
}
