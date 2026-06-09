<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\AttachImageDTO;
use App\Modules\Catalog\Models\ProductImage;
use Illuminate\Support\Facades\DB;

final readonly class AttachImageAction
{
    public function execute(AttachImageDTO $dto): ProductImage
    {
        return DB::transaction(function () use ($dto): ProductImage {
            if ($dto->isPrimary) {
                ProductImage::where('imageable_id', $dto->imageableId)
                    ->where('imageable_type', $dto->imageableType)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            return ProductImage::create([
                'imageable_id'   => $dto->imageableId,
                'imageable_type' => $dto->imageableType,
                'url'            => $dto->url,
                'thumbnail_url'  => $dto->thumbnailUrl,
                'alt_text'       => $dto->altText,
                'sort_order'     => $dto->sortOrder,
                'is_primary'     => $dto->isPrimary,
                'width'          => $dto->width,
                'height'         => $dto->height,
                'size_bytes'     => $dto->sizeBytes,
            ]);
        });
    }
}
