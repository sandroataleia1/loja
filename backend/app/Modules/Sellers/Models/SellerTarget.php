<?php

declare(strict_types=1);

namespace App\Modules\Sellers\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SellerTarget extends BaseModel
{
    protected $table = 'seller_targets';

    protected $fillable = [
        'tenant_id', 'seller_id', 'year', 'month',
        'target_cents', 'achieved_cents', 'commission_rate_override',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'year'                    => 'integer',
            'month'                   => 'integer',
            'target_cents'            => 'integer',
            'achieved_cents'          => 'integer',
            'commission_rate_override' => 'decimal:2',
        ]);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class, 'seller_id', 'uuid');
    }

    public function achievementPercent(): float
    {
        if ($this->target_cents === 0) {
            return 0.0;
        }

        return round(($this->achieved_cents / $this->target_cents) * 100, 2);
    }

    public function isAchieved(): bool
    {
        return $this->achieved_cents >= $this->target_cents;
    }

    public function bonusEarned(): bool
    {
        return $this->isAchieved() && $this->commission_rate_override !== null;
    }
}
