<?php

declare(strict_types=1);

namespace App\Modules\Sales\Models;

use App\Core\Auth\Models\User;
use App\Modules\Sales\Enums\DiscountTypeEnum;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SaleDiscount extends BaseModel
{
    protected $table = 'sale_discounts';

    protected $fillable = [
        'tenant_id',
        'sale_id',
        'sale_item_id',
        'type',
        'percentage',
        'amount_cents',
        'reason',
        'approved_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'type'         => DiscountTypeEnum::class,
            'percentage'   => 'float',
            'amount_cents' => 'integer',
        ]);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'uuid');
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class, 'sale_item_id', 'uuid');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'uuid');
    }
}
