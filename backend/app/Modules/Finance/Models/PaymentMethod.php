<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

final class PaymentMethod extends Model
{
    use HasUuid;

    protected $primaryKey = 'uuid';

    protected $table = 'payment_methods';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'is_active',
        'is_system',
        'sort_order',
        'accepts_change',
        'allow_installments',
        'max_installments',
        'min_installment_value_cents',
        'requires_authorization',
        'integrates_financial',
    ];

    protected function casts(): array
    {
        return [
            'is_active'                  => 'boolean',
            'is_system'                  => 'boolean',
            'sort_order'                 => 'integer',
            'accepts_change'             => 'boolean',
            'allow_installments'         => 'boolean',
            'max_installments'           => 'integer',
            'min_installment_value_cents' => 'integer',
            'requires_authorization'     => 'boolean',
            'integrates_financial'       => 'boolean',
        ];
    }
}
