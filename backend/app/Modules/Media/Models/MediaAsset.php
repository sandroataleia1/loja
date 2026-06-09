<?php

declare(strict_types=1);

namespace App\Modules\Media\Models;

use App\Modules\Media\Enums\MediaTypeEnum;
use App\Shared\Models\BaseModel;
use Database\Factories\MediaAssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Domínio desacoplado de mídia.
 *
 * Um MediaAsset é reutilizável: pode ser usado em produtos, coleções,
 * campanhas futuras e social commerce sem duplicação.
 *
 * metadata JSONB serve como extensão para:
 * - thumbnails gerados automaticamente
 * - dimensões originais
 * - cores dominantes
 * - AI labels futuros (CLIP embeddings, tags automáticas)
 */
final class MediaAsset extends BaseModel
{
    /** @use HasFactory<MediaAssetFactory> */
    use HasFactory;

    protected $table = 'media_assets';

    protected $fillable = [
        'tenant_id',
        'type',
        'path',
        'original_name',
        'mime_type',
        'file_size',
        'is_active',
        'uploaded_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'type'      => MediaTypeEnum::class,
            'is_active' => 'boolean',
            'file_size' => 'integer',
            'metadata'  => 'array',
        ]);
    }

    protected static function newFactory(): MediaAssetFactory
    {
        return MediaAssetFactory::new();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Auth\Models\User::class, 'uploaded_by', 'uuid');
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public function isImage(): bool
    {
        return $this->type === MediaTypeEnum::Image;
    }

    public function fileSizeInMb(): float
    {
        return $this->file_size !== null ? round($this->file_size / 1048576, 2) : 0.0;
    }
}
