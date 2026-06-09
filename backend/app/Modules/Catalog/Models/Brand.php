<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Shared\Models\BaseModel;
use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Brand extends BaseModel
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    protected static function newFactory(): BrandFactory
    {
        return BrandFactory::new();
    }

    protected $table = 'catalog_brands';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'slug',
        'description',
        'logo_url',
        'website_url',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_active' => 'boolean',
            'metadata'  => 'array',
        ]);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id', 'uuid');
    }
}
