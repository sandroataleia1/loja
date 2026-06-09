<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CustomerAddress extends Model
{
    use HasUuid;

    protected $table = 'customer_addresses';

    protected $primaryKey = 'uuid';

    public $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'customer_id',
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
            'is_default' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'uuid');
    }
}
