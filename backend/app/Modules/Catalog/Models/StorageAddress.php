<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Shared\Models\BaseModel;
use Database\Factories\StorageAddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class StorageAddress extends BaseModel
{
    /** @use HasFactory<StorageAddressFactory> */
    use HasFactory;

    protected static function newFactory(): StorageAddressFactory
    {
        return StorageAddressFactory::new();
    }

    protected $table = 'storage_addresses';

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'aisle',
        'rack',
        'shelf',
        'position',
        'capacity',
        'is_active',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'capacity'  => 'decimal:4',
            'is_active' => 'boolean',
        ]);
    }

    public function storageLocations(): HasMany
    {
        return $this->hasMany(ProductStorageLocation::class, 'storage_address_id', 'uuid');
    }

    public function getFullAddressAttribute(): string
    {
        return collect([$this->aisle, $this->rack, $this->shelf, $this->position])
            ->filter()
            ->implode('-');
    }
}
