<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Modules\Customers\Enums\PersonTypeEnum;
use App\Shared\Models\BaseModel;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'birth_date',
        'cpf',
        'cnpj',
        'email',
        'is_default_consumer',
        'is_active',
        'notes',
        'last_purchase_at',
        'total_purchases',
        'total_orders',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'person_type'      => PersonTypeEnum::class,
            'birth_date'       => 'date',
            'last_purchase_at' => 'datetime',
            'total_purchases'  => 'decimal:2',
            'total_orders'     => 'integer',
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

    /** Retorna apenas clientes ativos */
    public function scopeIsActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
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
