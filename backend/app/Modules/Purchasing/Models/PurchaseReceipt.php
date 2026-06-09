<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Models;

use App\Core\Auth\Models\User;
use App\Modules\Purchasing\Enums\PurchaseReceiptStatusEnum;
use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PurchaseReceipt extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $table      = 'purchase_receipts';
    protected $primaryKey = 'uuid';
    public    $incrementing = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'purchase_order_id', 'received_by', 'status', 'received_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status'      => PurchaseReceiptStatusEnum::class,
            'received_at' => 'datetime',
            'created_at'  => 'datetime',
            'updated_at'  => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'uuid');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by', 'uuid');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReceiptItem::class, 'receipt_id', 'uuid');
    }
}
