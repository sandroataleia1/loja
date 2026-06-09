<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Media\Models\MediaAsset;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot de mídia por produto.
 *
 * Desacoplado de catalog_images (URL-based).
 * Gerencia posição e designação de mídia primária por produto.
 */
final class ProductMedia extends Pivot
{
    protected $table = 'product_media';

    public $incrementing = true;

    protected $fillable = [
        'product_id',
        'media_asset_id',
        'position',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'position'   => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'uuid');
    }

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id', 'uuid');
    }
}
