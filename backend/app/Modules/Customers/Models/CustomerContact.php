<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Modules\Customers\Enums\ContactTypeEnum;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CustomerContact extends Model
{
    use HasUuid;

    protected $table = 'customer_contacts';

    protected $primaryKey = 'uuid';

    public $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'customer_id',
        'type',
        'value',
        'label',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'type'       => ContactTypeEnum::class,
            'is_primary' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'uuid');
    }
}
