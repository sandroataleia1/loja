<?php

declare(strict_types=1);

namespace App\Modules\Pix\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Casts\Attribute;

final class TenantPaymentGateway extends BaseModel
{
    protected $table = 'tenant_payment_gateways';

    protected $fillable = [
        'tenant_id',
        'gateway',
        'api_key',
        'environment',
        'webhook_token',
        'is_active',
        'pix_key',
        'pix_key_type',
        'settings',
    ];

    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'api_key'   => 'encrypted',
            'is_active' => 'boolean',
            'settings'  => 'array',
        ]);
    }

    public function isSandbox(): bool
    {
        return $this->environment === 'sandbox';
    }

    public function apiBaseUrl(): string
    {
        return $this->isSandbox()
            ? 'https://sandbox.asaas.com/api/v3'
            : 'https://api.asaas.com/v3';
    }
}
