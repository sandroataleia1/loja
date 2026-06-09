<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\ProductImage;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;

final readonly class ReorderImagesAction
{
    /**
     * @param string[] $orderedUuids UUIDs em nova ordem
     */
    public function execute(string $imageableId, string $imageableType, array $orderedUuids): void
    {
        $images = ProductImage::where('imageable_id', $imageableId)
            ->where('imageable_type', $imageableType)
            ->get()
            ->keyBy('uuid');

        if ($images->count() !== count($orderedUuids)) {
            throw new BusinessException('Lista de UUIDs não corresponde às imagens existentes.');
        }

        DB::transaction(function () use ($orderedUuids, $images): void {
            foreach ($orderedUuids as $i => $uuid) {
                $images[$uuid]?->update(['sort_order' => $i]);
            }
        });
    }
}
