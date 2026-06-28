<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Modules\Carriers\Models\Carrier;
use App\Modules\Catalog\Models\PriceList;
use App\Modules\Customers\Enums\CustomerStatusEnum;
use App\Modules\Customers\Enums\EducationLevelEnum;
use App\Modules\Customers\Enums\GenderEnum;
use App\Modules\Customers\Enums\HousingTypeEnum;
use App\Modules\Customers\Enums\MaritalStatusEnum;
use App\Modules\Customers\Enums\PersonTypeEnum;
use App\Modules\Customers\Enums\SpcStatusEnum;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Sellers\Models\SellerProfile;
use App\Shared\Models\BaseModel;
use App\Shared\Traits\HasCustomFields;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Customer extends BaseModel
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;
    use HasCustomFields;

    protected $table = 'customers';

    protected $fillable = [
        'tenant_id', 'code', 'person_type', 'name', 'trade_name',
        'document', 'rg', 'ie', 'im', 'birth_date', 'email',
        'is_default_consumer', 'is_active', 'situation', 'status',
        'blocked_reason', 'blocked_at', 'credit_limit', 'notes',
        'seller_id', 'carrier_id', 'last_purchase_at',
        'total_purchases', 'total_orders',
        // Estado civil / cônjuge legado (inline)
        'civil_status',
        'spouse_name', 'spouse_document', 'spouse_phone',
        'spouse_employer', 'spouse_income',
        'spouse_profession', 'spouse_birth_date', 'spouse_gender',
        // Avalista inline (legado)
        'guarantor_name', 'guarantor_document', 'guarantor_phone',
        'guarantor_address', 'guarantor_income',
        'guarantor_profession', 'guarantor_birth_date', 'guarantor_gender',
        // Preço
        'price_list_id',
        // Cônjuge estendido (M04 sessão anterior)
        'spouse_rg', 'spouse_cpf', 'spouse_monthly_income',
        // Análise de crédito (M04 sessão anterior)
        'monthly_income', 'profession', 'employer', 'employer_phone',
        'work_start_date', 'credit_score', 'credit_analysis_date',
        'credit_analyzed_by', 'credit_notes', 'spc_consulted_at', 'spc_status',
        // BLOCO 1 — Dados gerais
        'is_final_consumer', 'is_free_zone', 'is_store_chain', 'is_public_entity',
        'representative_id', 'collection_bank_id',
        'website', 'contact_name', 'postal_box', 'economic_activity', 'capital_stock_cents',
        'withholds_pis_cofins', 'withholds_irpj', 'withholds_iss', 'iss_rate',
        'withholds_social_security', 'calculates_icms_discount', 'discount_type',
        // BLOCO 2 — Dados pessoais completos
        'father_name', 'mother_name', 'gender', 'nationality',
        'birth_city', 'birth_state', 'education_level',
        'years_at_address', 'housing_type', 'rent_cents',
        'professional_card', 'employer_address',
        'other_income', 'other_income_source',
        'marital_status', 'credit_limit_cents',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'person_type'           => PersonTypeEnum::class,
            'status'                => CustomerStatusEnum::class,
            'gender'                => GenderEnum::class,
            'education_level'       => EducationLevelEnum::class,
            'housing_type'          => HousingTypeEnum::class,
            'marital_status'        => MaritalStatusEnum::class,
            'spc_status'            => SpcStatusEnum::class,
            'birth_date'            => 'date',
            'spouse_birth_date'     => 'date',
            'guarantor_birth_date'  => 'date',
            'work_start_date'       => 'date',
            'credit_analysis_date'  => 'date',
            'blocked_at'            => 'datetime',
            'last_purchase_at'      => 'datetime',
            'spc_consulted_at'      => 'datetime',
            'total_purchases'       => 'decimal:2',
            'credit_limit'          => 'decimal:2',
            'spouse_monthly_income' => 'decimal:2',
            'monthly_income'        => 'decimal:2',
            'other_income'          => 'decimal:2',
            'total_orders'          => 'integer',
            'credit_limit_cents'    => 'integer',
            'capital_stock_cents'   => 'integer',
            'rent_cents'            => 'integer',
            'is_default_consumer'   => 'boolean',
            'is_active'             => 'boolean',
            'is_final_consumer'     => 'boolean',
            'is_free_zone'          => 'boolean',
            'is_store_chain'        => 'boolean',
            'is_public_entity'      => 'boolean',
            'withholds_pis_cofins'  => 'boolean',
            'withholds_irpj'        => 'boolean',
            'withholds_iss'         => 'boolean',
            'withholds_social_security' => 'boolean',
            'calculates_icms_discount'  => 'boolean',
            'iss_rate'              => 'decimal:2',
        ]);
    }

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }

    // ────────────────────────────────────────────────── scopes

    public function scopeNotDefaultConsumer(Builder $query): Builder
    {
        return $query->where('is_default_consumer', false);
    }

    public function scopeIsActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CustomerStatusEnum::Active->value);
    }

    public function scopeBlocked(Builder $query): Builder
    {
        return $query->where('status', CustomerStatusEnum::Blocked->value);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', CustomerStatusEnum::Inactive->value);
    }

    public function scopeWithRestriction(Builder $query): Builder
    {
        return $query->where('spc_status', SpcStatusEnum::Restricted->value);
    }

    public function scopeCreditApproved(Builder $query): Builder
    {
        return $query->whereNotNull('credit_score')->where('credit_score', '>=', 5)
            ->where('spc_status', '!=', SpcStatusEnum::Restricted->value);
    }

    // ────────────────────────────────────────────────── computed / helpers

    public function isFinalConsumer(): bool
    {
        return (bool) $this->is_final_consumer;
    }

    public function isPjWithRetention(): bool
    {
        return $this->person_type === PersonTypeEnum::Company
            && ($this->withholds_pis_cofins || $this->withholds_iss || $this->withholds_irpj);
    }

    public function totalIncome(): float
    {
        return (float) ($this->monthly_income ?? 0)
            + (float) ($this->other_income ?? 0)
            + (float) ($this->spouse_monthly_income ?? 0);
    }

    public function creditLimitUsedPercent(): float
    {
        if (! $this->credit_limit_cents) {
            return 0.0;
        }

        $used = $this->credit_limit_cents - ($this->credit_limit_available_cents ?? $this->credit_limit_cents);

        return round(($used / $this->credit_limit_cents) * 100, 2);
    }

    public function hasSpouseData(): bool
    {
        return ! empty($this->spouse_name)
            || ! empty($this->spouse_document)
            || ! empty($this->spouse_cpf);
    }

    public function spouseIncludedInAnalysis(): bool
    {
        $hasSpouseStatus = $this->marital_status?->hasSpouse()
            ?? ($this->civil_status === 'casado');

        return $this->hasSpouseData() && $hasSpouseStatus;
    }

    public function hasIncome(): bool
    {
        return $this->monthly_income !== null && (float) $this->monthly_income > 0;
    }

    public function creditScoreLabel(): string
    {
        return match (true) {
            $this->credit_score === null => 'Não analisado',
            $this->credit_score >= 9    => 'Excelente',
            $this->credit_score >= 7    => 'Bom',
            $this->credit_score >= 5    => 'Regular',
            $this->credit_score >= 3    => 'Ruim',
            default                      => 'Péssimo',
        };
    }

    public function maritalStatusLabel(): string
    {
        if ($this->marital_status instanceof MaritalStatusEnum) {
            return $this->marital_status->label();
        }

        return match ($this->civil_status) {
            'casado'    => 'Casado(a)',
            'solteiro'  => 'Solteiro(a)',
            'divorciado'=> 'Divorciado(a)',
            'viuvo'     => 'Viúvo(a)',
            default     => $this->civil_status ?? '—',
        };
    }

    public function block(string $reason): void
    {
        $this->update([
            'status'         => CustomerStatusEnum::Blocked->value,
            'blocked_reason' => $reason,
            'blocked_at'     => now(),
            'is_active'      => false,
        ]);
    }

    public function unblock(): void
    {
        $this->update([
            'status'         => CustomerStatusEnum::Active->value,
            'blocked_reason' => null,
            'blocked_at'     => null,
            'is_active'      => true,
        ]);
    }

    // ────────────────────────────────────────────────── relations

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_id', 'uuid');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class, 'seller_id', 'uuid');
    }

    public function representative(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class, 'representative_id', 'uuid');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'carrier_id', 'uuid');
    }

    public function collectionBank(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'collection_bank_id', 'uuid');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id', 'uuid');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class, 'customer_id', 'uuid');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            CustomerTag::class,
            'customer_tag_assignments',
            'customer_id',
            'tag_id',
            'uuid',
            'uuid',
        );
    }

    public function commercialReferences(): HasMany
    {
        return $this->hasMany(CustomerCommercialReference::class, 'customer_id', 'uuid');
    }

    public function purchaseReferences(): HasMany
    {
        return $this->hasMany(CustomerPurchaseReference::class, 'customer_id', 'uuid');
    }

    public function guarantors(): HasMany
    {
        return $this->hasMany(CustomerGuarantor::class, 'customer_id', 'uuid');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class, 'customer_id', 'uuid');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(CustomerInteraction::class, 'customer_id', 'uuid')
            ->latest('interacted_at');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(CustomerAsset::class, 'customer_id', 'uuid');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(CustomerCard::class, 'customer_id', 'uuid');
    }

    public function bankReferences(): HasMany
    {
        return $this->hasMany(CustomerBankReference::class, 'customer_id', 'uuid');
    }

    public function authorizedBuyers(): HasMany
    {
        return $this->hasMany(CustomerAuthorizedBuyer::class, 'customer_id', 'uuid');
    }

    public function activeAuthorizedBuyers(): HasMany
    {
        return $this->hasMany(CustomerAuthorizedBuyer::class, 'customer_id', 'uuid')
            ->where('is_active', true)
            ->where(function (Builder $q): void {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()->toDateString());
            });
    }
}
