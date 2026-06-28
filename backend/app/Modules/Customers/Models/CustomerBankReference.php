<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CustomerBankReference extends BaseModel
{
    use SoftDeletes;

    protected $table = 'customer_bank_references';

    public const ACCOUNT_TYPES = ['checking', 'savings', 'investment'];

    protected $fillable = [
        'tenant_id', 'customer_id', 'bank_name', 'bank_agency', 'account_type',
        'contact_name', 'phone', 'consulted_at',
        'email_1', 'email_2',
        'first_purchase_at', 'first_purchase_value_cents',
        'highest_purchase_value_cents',
        'last_purchase_at', 'last_purchase_value_cents',
        'notes',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'consulted_at'                => 'date',
            'first_purchase_at'           => 'date',
            'last_purchase_at'            => 'date',
            'first_purchase_value_cents'  => 'integer',
            'highest_purchase_value_cents' => 'integer',
            'last_purchase_value_cents'   => 'integer',
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'uuid');
    }
}
