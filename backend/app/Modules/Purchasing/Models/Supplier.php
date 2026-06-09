<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Models;

use App\Shared\Models\BaseModel;
use Database\Factories\SupplierFactory;
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
        'document', 'email', 'phone', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_active' => 'boolean',
        ]);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id', 'uuid');
    }
}
