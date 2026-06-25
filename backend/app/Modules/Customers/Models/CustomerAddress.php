<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Shared\Enums\AddressTypeEnum;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CustomerAddress extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'customer_addresses';

    protected $primaryKey = 'uuid';

    public $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'customer_id',
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

    protected function casts(): array
    {
        return [
            'address_type' => AddressTypeEnum::class,
            'is_default'   => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'uuid');
    }
}
