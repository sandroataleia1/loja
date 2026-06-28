<?php

declare(strict_types=1);

namespace App\Modules\Sellers\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SellerRegion extends BaseModel
{
    protected $table = 'seller_regions';

    public const TYPES = ['city', 'state', 'cep_range', 'neighborhood'];

    protected $fillable = [
        'tenant_id', 'seller_id', 'region_type', 'value',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class, 'seller_id', 'uuid');
    }
}
