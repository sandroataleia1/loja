<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Models;

use App\Modules\Purchasing\Enums\SupplierStatusEnum;
use App\Modules\Purchasing\Enums\SupplyCategoryEnum;
use App\Shared\Models\BaseModel;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Supplier extends BaseModel
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    protected static function newFactory(): SupplierFactory
    {
        return SupplierFactory::new();
    }

    protected $table = 'suppliers';

    protected $fillable = [
        'tenant_id', 'code', 'person_type', 'name', 'trade_name',
        'document', 'ie', 'im', 'email', 'phone', 'is_active', 'notes',
        'status', 'suspension_reason', 'suspended_at',
        'performance_score', 'avg_delivery_days', 'return_rate',
        'supply_category', 'default_payment_term_days',
        'bank_name', 'bank_agency', 'bank_account', 'bank_account_type', 'bank_pix_key',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'status'          => SupplierStatusEnum::class,
            'supply_category' => SupplyCategoryEnum::class,
            'suspended_at'    => 'datetime',
            'is_active'       => 'boolean',
        ]);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SupplierStatusEnum::Active->value);
    }

    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('status', SupplierStatusEnum::Suspended->value);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', SupplierStatusEnum::Inactive->value);
    }

    public function suspend(string $reason): void
    {
        $this->update([
            'status'             => SupplierStatusEnum::Suspended->value,
            'suspension_reason'  => $reason,
            'suspended_at'       => now(),
            'is_active'          => false,
        ]);
    }

    public function reactivate(): void
    {
        $this->update([
            'status'            => SupplierStatusEnum::Active->value,
            'suspension_reason' => null,
            'suspended_at'      => null,
            'is_active'         => true,
        ]);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id', 'uuid');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(SupplierAddress::class, 'supplier_id', 'uuid');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class, 'supplier_id', 'uuid');
    }
}
