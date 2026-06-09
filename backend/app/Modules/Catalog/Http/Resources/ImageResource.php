<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'          => $this->uuid,
            'url'           => $this->url,
            'thumbnail_url' => $this->thumbnail_url,
            'alt_text'      => $this->alt_text,
            'sort_order'    => $this->sort_order,
            'is_primary'    => $this->is_primary,
            'width'         => $this->width,
            'height'        => $this->height,
            'size_bytes'    => $this->size_bytes,
        ];
    }
}
