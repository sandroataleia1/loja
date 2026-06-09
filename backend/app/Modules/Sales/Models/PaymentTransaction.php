<?php

declare(strict_types=1);

namespace App\Modules\Sales\Models;

use App\Modules\Sales\Enums\PaymentMethodEnum;
use App\Modules\Sales\Enums\PaymentStatusEnum;
use App\Shared\Models\BaseModel;
use Database\Factories\PaymentTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PaymentTransaction extends BaseModel
{
    /** @use HasFactory<PaymentTransactionFactory> */
    use HasFactory;

    protected static function newFactory(): PaymentTransactionFactory
    {
        return PaymentTransactionFactory::new();
    }

    protected $table = 'payment_transactions';

    protected $fillable = [
        'tenant_id',
        'sale_id',
        'method',
        'amount_cents',
        'status',
        'external_reference',
        'notes',
        'metadata',
        'paid_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'method'       => PaymentMethodEnum::class,
            'status'       => PaymentStatusEnum::class,
            'amount_cents' => 'integer',
            'metadata'     => 'array',
            'paid_at'      => 'datetime',
        ]);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'uuid');
    }
}
