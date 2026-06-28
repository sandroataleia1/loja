<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Core\Auth\Models\User;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CustomerInteraction extends BaseModel
{
    use SoftDeletes;

    protected $table = 'customer_interactions';

    public const TYPES = ['visit', 'call', 'whatsapp', 'email', 'complaint', 'note'];

    protected $fillable = [
        'tenant_id', 'customer_id', 'interaction_type',
        'subject', 'description', 'outcome', 'interacted_at', 'created_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'interacted_at' => 'datetime',
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'uuid');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'uuid');
    }
}
