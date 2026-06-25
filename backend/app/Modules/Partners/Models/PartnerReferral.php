<?php

declare(strict_types=1);

namespace App\Modules\Partners\Models;

use App\Modules\Customers\Models\Customer;
use App\Modules\Partners\Enums\PartnerCommissionStatusEnum;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PartnerReferral extends BaseModel
{
    protected $table = 'partner_referrals';

    protected $fillable = [
        'tenant_id',
        'partner_id',
        'customer_id',
        'referred_at',
        'first_purchase_at',
        'commission_percent',
        'commission_value',
        'commission_status',
        'commission_paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'commission_status'  => PartnerCommissionStatusEnum::class,
            'referred_at'        => 'date',
            'first_purchase_at'  => 'date',
            'commission_paid_at' => 'date',
            'commission_percent' => 'decimal:2',
            'commission_value'   => 'decimal:2',
        ]);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(PartnerProfessional::class, 'partner_id', 'uuid');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'uuid');
    }
}
