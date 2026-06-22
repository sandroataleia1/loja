<?php

declare(strict_types=1);

namespace App\Modules\Sales\Models;

use App\Modules\Finance\Models\PaymentCondition;
use App\Modules\Finance\Models\PaymentMethod;
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
        'payment_method_id',
        'payment_condition_id',
        'method',
        'amount_cents',
        'discount_cents',
        'interest_cents',
        'fine_cents',
        'installment_number',
        'total_installments',
        'due_date',
        'status',
        'external_reference',
        'notes',
        'metadata',
        'paid_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'method'             => PaymentMethodEnum::class,
            'status'             => PaymentStatusEnum::class,
            'amount_cents'       => 'integer',
            'discount_cents'     => 'integer',
            'interest_cents'     => 'integer',
            'fine_cents'         => 'integer',
            'installment_number' => 'integer',
            'total_installments' => 'integer',
            'due_date'           => 'date',
            'metadata'           => 'array',
            'paid_at'            => 'datetime',
        ]);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'uuid');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'uuid');
    }

    public function paymentCondition(): BelongsTo
    {
        return $this->belongsTo(PaymentCondition::class, 'payment_condition_id', 'uuid');
    }
}
