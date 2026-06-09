<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'        => $this->uuid,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'cover_url'   => $this->cover_url,
            'starts_at'   => $this->starts_at?->toISOString(),
            'ends_at'     => $this->ends_at?->toISOString(),
            'is_active'   => $this->is_active,
            'is_current'  => $this->isActive(),
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
