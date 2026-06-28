<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use Illuminate\Database\Eloquent\Model;

class DeletionRequest extends Model
{
    protected $primaryKey = 'uuid';
    public    $incrementing = false;
    protected $keyType      = 'string';

    protected $fillable = [
        'tenant_id', 'entity_type', 'entity_id',
        'requested_by', 'reason', 'status',
        'reviewed_by', 'reviewed_at', 'executed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'executed_at' => 'datetime',
    ];
}
