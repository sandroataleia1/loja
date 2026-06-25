<?php

declare(strict_types=1);

namespace App\Modules\Settings\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Core\Auth\Models\User;
use App\Core\Tenancy\Models\Tenant;

/**
 * Histórico imutável de alterações de configuração por tenant.
 *
 * SEM BelongsToTenant — admin da plataforma precisa ver tudo.
 * SEM SoftDeletes — histórico é permanente por definição.
 * SEM updated_at — append-only, usa changed_at como timestamp.
 */
final class ConfigHistory extends Model
{
    use HasUuid;

    protected $table = 'config_history';

    protected $primaryKey = 'uuid';

    public const UPDATED_AT = null; // sem updated_at

    protected $fillable = [
        'tenant_id',
        'section',
        'key',
        'old_value',
        'new_value',
        'changed_by',
        'changed_at',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'uuid');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by', 'uuid');
    }
}
