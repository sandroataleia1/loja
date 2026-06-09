<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'        => $this->uuid,
            'code'        => $this->code,
            'parent_id'   => $this->parent_id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'image_url'   => $this->image_url,
            'sort_order'  => $this->sort_order,
            'is_active'   => $this->is_active,
            'children'    => $this->whenLoaded('children', fn () => CategoryResource::collection($this->children)),
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
