<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AttributeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'               => $this->uuid,
            'attribute_group_id' => $this->attribute_group_id,
            'value'              => $this->value,
            'label'              => $this->label,
            'color_hex'          => $this->color_hex,
            'sort_order'         => $this->sort_order,
            'created_at'         => $this->created_at?->toISOString(),
        ];
    }
}
