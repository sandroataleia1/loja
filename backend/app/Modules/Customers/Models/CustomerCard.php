<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CustomerCard extends BaseModel
{
    protected $table = 'customer_cards';

    public const BRANDS = ['visa', 'mastercard', 'hipercard', 'amex', 'diners', 'elo', 'other'];

    protected $fillable = [
        'tenant_id', 'customer_id', 'card_brand', 'notes',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'uuid');
    }
}
