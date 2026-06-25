<?php

declare(strict_types=1);

namespace App\Modules\Carriers\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CarrierContact extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'carrier_contacts';

    protected $fillable = [
        'carrier_id',
        'type',
        'value',
        'label',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'carrier_id', 'uuid');
    }
}
