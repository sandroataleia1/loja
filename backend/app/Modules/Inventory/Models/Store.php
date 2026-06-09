<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Shared\Models\BaseModel;
use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Store extends BaseModel
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    protected static function newFactory(): StoreFactory
    {
        return StoreFactory::new();
    }

    protected $table = 'stores';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'type',
        'phone',
        'email',
        'address',
        'is_active',
        'is_ecommerce',
        'metadata',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'address'      => 'array',
            'is_active'    => 'boolean',
            'is_ecommerce' => 'boolean',
        ]);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class, 'store_id', 'uuid');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'store_id', 'uuid');
    }

    public function outboundTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'origin_store_id', 'uuid');
    }

    public function inboundTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'destination_store_id', 'uuid');
    }
}
