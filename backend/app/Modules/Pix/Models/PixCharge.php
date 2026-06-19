<?php

declare(strict_types=1);

namespace App\Modules\Pix\Models;

use App\Shared\Models\BaseModel;

final class PixCharge extends BaseModel
{
    protected $table = 'pix_charges';

    protected $fillable = [
        'tenant_id',
        'sale_id',
        'gateway',
        'external_id',
        'status',
        'amount_cents',
        'qr_code_image',
        'pix_copy_paste',
        'expires_at',
        'paid_at',
        'gateway_response',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'amount_cents'     => 'integer',
            'expires_at'       => 'datetime',
            'paid_at'          => 'datetime',
            'gateway_response' => 'array',
        ]);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || ($this->expires_at && $this->expires_at->isPast());
    }
}
