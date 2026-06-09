<?php

declare(strict_types=1);

namespace App\Modules\Media\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MediaAssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'          => $this->uuid,
            'type'          => $this->type->value,
            'type_label'    => $this->type->label(),
            'path'          => $this->path,
            'original_name' => $this->original_name,
            'mime_type'     => $this->mime_type,
            'file_size'     => $this->file_size,
            'file_size_mb'  => $this->fileSizeInMb(),
            'is_active'     => $this->is_active,
            'is_primary'    => $this->whenPivotLoaded('product_media', fn () => (bool) $this->pivot->is_primary),
            'position'      => $this->whenPivotLoaded('product_media', fn () => $this->pivot->position),
            'uploaded_by'   => $this->uploaded_by,
            'metadata'      => $this->metadata,
            'created_at'    => $this->created_at?->toISOString(),
        ];
    }
}
