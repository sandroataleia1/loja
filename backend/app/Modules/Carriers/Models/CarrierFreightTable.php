<?php

declare(strict_types=1);

namespace App\Modules\Carriers\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CarrierFreightTable extends BaseModel
{
    use SoftDeletes;

    protected $table = 'carrier_freight_tables';

    public const PRICING_TYPES = ['by_weight', 'by_value', 'by_cep_range', 'fixed', 'free'];

    protected $fillable = [
        'tenant_id', 'carrier_id', 'name', 'pricing_type', 'is_active',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_active' => 'boolean',
        ]);
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'carrier_id', 'uuid');
    }

    public function ranges(): HasMany
    {
        return $this->hasMany(CarrierFreightRange::class, 'freight_table_id', 'uuid');
    }
}
