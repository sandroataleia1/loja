<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Core\Auth\Models\User;
use App\Modules\Catalog\Models\Variant;
use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InventoryAdjustment extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $table      = 'inventory_adjustments';
    protected $primaryKey = 'uuid';
    public    $incrementing = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'variant_id',
        'movement_id',
        'previous_quantity',
        'new_quantity',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'previous_quantity' => 'integer',
            'new_quantity'      => 'integer',
            'difference'        => 'integer',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'uuid');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'variant_id', 'uuid');
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'movement_id', 'uuid');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'uuid');
    }
}
