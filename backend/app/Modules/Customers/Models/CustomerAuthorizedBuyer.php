<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Shared\Models\BaseModel;
use Database\Factories\CustomerAuthorizedBuyerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Core\Auth\Models\User;
use Illuminate\Support\Carbon;

final class CustomerAuthorizedBuyer extends BaseModel
{
    /** @use HasFactory<CustomerAuthorizedBuyerFactory> */
    use HasFactory;

    protected static function newFactory(): CustomerAuthorizedBuyerFactory
    {
        return CustomerAuthorizedBuyerFactory::new();
    }
    use SoftDeletes;

    protected $table = 'customer_authorized_buyers';

    protected $fillable = [
        'tenant_id', 'customer_id', 'name', 'cpf', 'rg', 'phone',
        'relationship', 'credit_limit_cents', 'valid_until',
        'is_active', 'authorized_at', 'authorized_by',
        'revoked_at', 'revoked_reason',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'authorized_at'      => 'date',
            'valid_until'        => 'date',
            'revoked_at'         => 'datetime',
            'is_active'          => 'boolean',
            'credit_limit_cents' => 'integer',
        ]);
    }

    public function isValid(): bool
    {
        return $this->is_active
            && ($this->valid_until === null || $this->valid_until->gte(Carbon::today()));
    }

    public function revoke(string $reason): void
    {
        $this->update([
            'is_active'      => false,
            'revoked_at'     => now(),
            'revoked_reason' => $reason,
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'uuid');
    }

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'authorized_by', 'uuid');
    }
}
