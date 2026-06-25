<?php

declare(strict_types=1);

namespace App\Modules\Carriers\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CarrierAddress extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'carrier_addresses';

    protected $fillable = [
        'carrier_id',
        'zipcode',
        'street',
        'number',
        'complement',
        'district',
        'city',
        'state',
        'country',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'carrier_id', 'uuid');
    }
}
