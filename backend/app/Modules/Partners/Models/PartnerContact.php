<?php

declare(strict_types=1);

namespace App\Modules\Partners\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PartnerContact extends BaseModel
{
    protected $table = 'partner_contacts';

    protected $fillable = [
        'partner_id',
        'type',
        'value',
        'label',
        'is_primary',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_primary' => 'boolean',
        ]);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(PartnerProfessional::class, 'partner_id', 'uuid');
    }
}
