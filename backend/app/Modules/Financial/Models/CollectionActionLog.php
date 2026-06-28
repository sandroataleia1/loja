<?php

declare(strict_types=1);

namespace App\Modules\Financial\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log de ações executadas pelas regras de cobrança automática.
 */
final class CollectionActionLog extends BaseModel
{
    protected $table = 'collection_actions_log';

    protected $fillable = [
        'tenant_id',
        'collection_rule_id',
        'installment_id',
        'customer_id',
        'action_type',
        'status',
        'notes',
        'executed_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'executed_at' => 'datetime',
        ]);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(CollectionRule::class, 'collection_rule_id', 'uuid');
    }
}
