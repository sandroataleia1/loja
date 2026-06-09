<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AttributeGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'       => $this->uuid,
            'name'       => $this->name,
            'slug'       => $this->slug,
            'type'       => $this->type->value,
            'type_label' => $this->type->label(),
            'sort_order' => $this->sort_order,
            'attributes' => $this->whenLoaded('attributes', fn () => AttributeResource::collection($this->attributes)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
