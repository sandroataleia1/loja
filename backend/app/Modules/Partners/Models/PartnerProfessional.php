<?php

declare(strict_types=1);

namespace App\Modules\Partners\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PartnerProfessional extends BaseModel
{
    use SoftDeletes;

    protected $table = 'partner_professionals';

    protected $fillable = [
        'tenant_id',
        'code',
        'type',
        'person_type',
        'name',
        'company_name',
        'document',
        'email',
        'phone',
        'whatsapp',
        'notes',
        'is_active',
        'referral_commission_percent',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_active'                   => 'boolean',
            'referral_commission_percent' => 'decimal:2',
        ]);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(PartnerContact::class, 'partner_id', 'uuid');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(PartnerReferral::class, 'partner_id', 'uuid');
    }
}
