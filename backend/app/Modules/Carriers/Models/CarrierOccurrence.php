<?php

declare(strict_types=1);

namespace App\Modules\Carriers\Models;

use App\Core\Auth\Models\User;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CarrierOccurrence extends BaseModel
{
    use SoftDeletes;

    protected $table = 'carrier_occurrences';

    public const TYPES = ['delivered', 'not_found', 'refused', 'damaged', 'lost', 'returned'];

    protected $fillable = [
        'tenant_id', 'carrier_id', 'occurrence_type',
        'tracking_code', 'order_reference', 'description', 'occurred_at',
        'registered_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'occurred_at' => 'datetime',
        ]);
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'carrier_id', 'uuid');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by', 'uuid');
    }
}
