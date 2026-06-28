<?php

declare(strict_types=1);

namespace App\Modules\Carriers\Models;

use App\Modules\Carriers\Enums\DeliveryModeEnum;
use App\Shared\Models\BaseModel;
use Database\Factories\CarrierFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Carrier extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected static function newFactory(): Factory
    {
        return CarrierFactory::new();
    }

    protected $table = 'carriers';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'trade_name',
        'cnpj',
        'ie',
        'email',
        'phone',
        'notes',
        'is_active',
        'delivery_mode',
        'rntrc',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'delivery_mode' => DeliveryModeEnum::class,
            'is_active'     => 'boolean',
        ]);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CarrierAddress::class, 'carrier_id', 'uuid');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CarrierContact::class, 'carrier_id', 'uuid');
    }

    public function freightTables(): HasMany
    {
        return $this->hasMany(CarrierFreightTable::class, 'carrier_id', 'uuid');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(CarrierOccurrence::class, 'carrier_id', 'uuid')->latest('occurred_at');
    }
}
