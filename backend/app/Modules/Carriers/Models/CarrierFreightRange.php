<?php

declare(strict_types=1);

namespace App\Modules\Carriers\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CarrierFreightRange extends BaseModel
{
    protected $table = 'carrier_freight_ranges';

    protected $fillable = [
        'tenant_id', 'freight_table_id',
        'min_weight_g', 'max_weight_g',
        'min_value_cents', 'max_value_cents',
        'min_cep', 'max_cep',
        'price_cents', 'estimated_days',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'min_weight_g'     => 'decimal:3',
            'max_weight_g'     => 'decimal:3',
            'min_value_cents'  => 'integer',
            'max_value_cents'  => 'integer',
            'price_cents'      => 'integer',
            'estimated_days'   => 'integer',
        ]);
    }

    public function freightTable(): BelongsTo
    {
        return $this->belongsTo(CarrierFreightTable::class, 'freight_table_id', 'uuid');
    }
}
