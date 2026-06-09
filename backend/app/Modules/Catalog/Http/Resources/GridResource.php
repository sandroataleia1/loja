<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class GridResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'               => $this->uuid,
            'attribute_group_id' => $this->attribute_group_id,
            'name'               => $this->name,
            'description'        => $this->description,
            'attributes'         => $this->whenLoaded('attributes', fn () => AttributeResource::collection($this->attributes)),
            'created_at'         => $this->created_at?->toISOString(),
            'updated_at'         => $this->updated_at?->toISOString(),
        ];
    }
}
