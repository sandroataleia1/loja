<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductStorageLocation extends BaseModel
{
    protected $table = 'product_storage_locations';

    protected $fillable = [
        'tenant_id',
        'variant_id',
        'storage_address_id',
        'is_primary',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_primary' => 'boolean',
        ]);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'variant_id', 'uuid');
    }

    public function storageAddress(): BelongsTo
    {
        return $this->belongsTo(StorageAddress::class, 'storage_address_id', 'uuid');
    }
}
