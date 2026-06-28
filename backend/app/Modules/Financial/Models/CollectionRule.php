<?php

declare(strict_types=1);

namespace App\Modules\Financial\Models;

use App\Modules\Financial\Enums\CollectionActionEnum;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Regra de cobrança automática por tenant.
 *
 * Executada diariamente pelo ExecuteCollectionRulesJob às 08:00.
 * Cada regra define: quantos dias após o vencimento (trigger_days) e qual ação.
 */
final class CollectionRule extends BaseModel
{
    use SoftDeletes;

    protected $table = 'collection_rules';

    protected $fillable = [
        'tenant_id',
        'name',
        'trigger_days',
        'action_type',
        'message_template',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'action_type'  => CollectionActionEnum::class,
            'trigger_days' => 'integer',
            'is_active'    => 'boolean',
            'sort_order'   => 'integer',
        ]);
    }

    public function actionsLog(): HasMany
    {
        return $this->hasMany(CollectionActionLog::class, 'collection_rule_id', 'uuid');
    }
}
