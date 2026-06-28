<?php

declare(strict_types=1);

namespace App\Modules\Sellers\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SellerCommission extends BaseModel
{
    use SoftDeletes;

    protected $table = 'seller_commissions';

    public const STATUSES = ['pending', 'approved', 'paid'];

    protected $fillable = [
        'tenant_id', 'seller_id', 'reference_year', 'reference_month',
        'gross_amount_cents', 'commission_rate', 'commission_cents',
        'discount_given_cents', 'net_commission_cents',
        'status', 'paid_at', 'notes',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'reference_year'       => 'integer',
            'reference_month'      => 'integer',
            'gross_amount_cents'   => 'integer',
            'commission_rate'      => 'decimal:2',
            'commission_cents'     => 'integer',
            'discount_given_cents' => 'integer',
            'net_commission_cents' => 'integer',
            'paid_at'              => 'datetime',
        ]);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class, 'seller_id', 'uuid');
    }
}
