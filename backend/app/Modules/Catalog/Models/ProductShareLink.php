<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Shared\Models\BaseModel;

final class ProductShareLink extends BaseModel
{
    protected $table = 'product_share_links';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'token',
        'expires_at',
        'view_count',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'expires_at' => 'datetime',
            'view_count' => 'integer',
        ]);
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'uuid');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
