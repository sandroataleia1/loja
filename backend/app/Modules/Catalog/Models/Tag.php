<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

final class Tag extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $table = 'catalog_tags';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function products(): MorphToMany
    {
        return $this->morphedByMany(
            Product::class,
            'taggable',
            'catalog_taggables',
            'tag_id',
            'taggable_id',
            'uuid',
            'uuid',
        );
    }
}
