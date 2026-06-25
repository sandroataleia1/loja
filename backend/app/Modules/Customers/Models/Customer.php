<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Modules\Carriers\Models\Carrier;
use App\Modules\Customers\Enums\CustomerStatusEnum;
use App\Modules\Customers\Enums\PersonTypeEnum;
use App\Modules\Sellers\Models\SellerProfile;
use App\Shared\Models\BaseModel;
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

    protected $table = 'customers';

    protected $fillable = [
        'tenant_id',
        'code',
        'person_type',
        'name',
        'trade_name',
        'document',
        'rg',
        'ie',
        'im',
        'birth_date',
        'email',
        'is_default_consumer',
        'is_active',
        'situation',
        'status',
        'blocked_reason',
        'blocked_at',
        'credit_limit',
        'notes',
        'seller_id',
        'carrier_id',
        'last_purchase_at',
        'total_purchases',
        'total_orders',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'person_type'         => PersonTypeEnum::class,
            'status'              => CustomerStatusEnum::class,
            'birth_date'          => 'date',
            'blocked_at'          => 'datetime',
            'last_purchase_at'    => 'datetime',
            'total_purchases'     => 'decimal:2',
            'total_orders'        => 'integer',
            'credit_limit'        => 'decimal:2',
            'is_default_consumer' => 'boolean',
            'is_active'           => 'boolean',
        ]);
    }

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }

    /** Exclui o consumidor padrão dos resultados normais */
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

    public function seller(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class, 'seller_id', 'uuid');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'carrier_id', 'uuid');
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
}
