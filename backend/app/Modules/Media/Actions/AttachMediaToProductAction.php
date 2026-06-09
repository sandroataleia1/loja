<?php

declare(strict_types=1);

namespace App\Modules\Media\Actions;

use App\Modules\Catalog\Events\ProductMediaAttached;
use App\Modules\Catalog\Models\Product;
use App\Modules\Media\Models\MediaAsset;
use App\Shared\Exceptions\BusinessException;

final readonly class AttachMediaToProductAction
{
    public function execute(
        Product    $product,
        MediaAsset $asset,
        int        $position = 0,
        bool       $isPrimary = false,
    ): void {
        if ($product->media()->where('media_asset_id', $asset->uuid)->exists()) {
            throw new BusinessException('Esta mídia já está vinculada ao produto.');
        }

        // Se definida como primária, remove o primário anterior (se houver).
        if ($isPrimary) {
            $currentPrimaryUuid = $product->media()
                ->where('is_primary', true)
                ->pluck('media_assets.uuid')
                ->first();

            // Sem primário anterior → nada a fazer. updateExistingPivot('')
            // dispararia UPDATE com uuid vazio (SQLSTATE 22P02).
            if ($currentPrimaryUuid !== null) {
                $product->media()->updateExistingPivot(
                    $currentPrimaryUuid,
                    ['is_primary' => false],
                );
            }
        }

        $product->media()->attach($asset->uuid, [
            'position'   => $position,
            'is_primary' => $isPrimary,
        ]);

        ProductMediaAttached::dispatch($product, $asset, $isPrimary);
    }
}
