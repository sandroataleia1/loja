<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

final class PaymentCondition extends Model
{
    use HasUuid;

    protected $primaryKey = 'uuid';

    protected $table = 'payment_conditions';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'discount_type',
        'discount_value',
        'interest_type',
        'interest_value',
        'fine_percent',
        'fine_after_days',
        'grace_days',
        'installment_count',
        'first_due_days',
        'interval_days',
        'has_entry',
        'entry_percent',
        'is_variable',
        'is_active',
        'is_system',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active'         => 'boolean',
            'is_system'         => 'boolean',
            'sort_order'        => 'integer',
            'discount_value'    => 'decimal:4',
            'interest_value'    => 'decimal:4',
            'fine_percent'      => 'decimal:4',
            'fine_after_days'   => 'integer',
            'grace_days'        => 'integer',
            'installment_count' => 'integer',
            'first_due_days'    => 'integer',
            'interval_days'     => 'integer',
            'has_entry'         => 'boolean',
            'entry_percent'     => 'decimal:4',
            'is_variable'       => 'boolean',
        ];
    }
}
