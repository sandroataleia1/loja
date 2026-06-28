<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Modules\Customers\Enums\HousingTypeEnum;
use App\Modules\Customers\Enums\MaritalStatusEnum;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CustomerGuarantor extends BaseModel
{
    use SoftDeletes;

    protected $table = 'customer_guarantors';

    protected $fillable = [
        'tenant_id', 'customer_id', 'guarantor_type',
        'name', 'document', 'rg', 'profession', 'employer', 'monthly_income',
        'phone', 'email',
        'zip_code', 'street', 'number', 'complement', 'neighborhood', 'city', 'state',
        'relationship', 'notes',
        // BLOCO 10 — campos complementares
        'birth_date', 'marital_status', 'years_at_address', 'housing_type',
        'other_income', 'assets_description', 'is_same_address_as_customer',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'monthly_income'              => 'decimal:2',
            'other_income'                => 'decimal:2',
            'birth_date'                  => 'date',
            'marital_status'              => MaritalStatusEnum::class,
            'housing_type'                => HousingTypeEnum::class,
            'is_same_address_as_customer' => 'boolean',
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'uuid');
    }

    public function totalIncome(): float
    {
        return (float) ($this->monthly_income ?? 0) + (float) ($this->other_income ?? 0);
    }
}
