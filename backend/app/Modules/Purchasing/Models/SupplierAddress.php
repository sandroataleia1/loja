<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Models;

use App\Shared\Enums\AddressTypeEnum;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SupplierAddress extends Model
{
    use HasUuid;
    use SoftDeletes;
    protected $table = 'supplier_addresses';

    protected $fillable = [
        'supplier_id',
        'address_type',
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
        'address_type' => AddressTypeEnum::class,
        'is_default'   => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'uuid');
    }
}
