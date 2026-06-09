<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

// Sem SoftDeletes nem Auditable — imagens são descartáveis
final class ProductImage extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $table = 'catalog_images';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'imageable_id',
        'imageable_type',
        'url',
        'thumbnail_url',
        'alt_text',
        'sort_order',
        'is_primary',
        'width',
        'height',
        'size_bytes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_primary' => 'boolean',
            'width'      => 'integer',
            'height'     => 'integer',
            'size_bytes' => 'integer',
            'metadata'   => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function imageable(): MorphTo
    {
        return $this->morphTo('imageable', 'imageable_type', 'imageable_id', 'uuid');
    }
}
