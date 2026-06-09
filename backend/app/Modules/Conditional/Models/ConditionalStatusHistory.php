<?php

declare(strict_types=1);

namespace App\Modules\Conditional\Models;

use App\Core\Auth\Models\User;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ConditionalStatusHistory extends Model
{
    use HasUuid;

    /** No created_at / updated_at columns — only changed_at. */
    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    protected $table = 'conditional_status_history';

    protected $primaryKey = 'uuid';

    protected $fillable = [
        'conditional_id',
        'previous_status',
        'current_status',
        'changed_by',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function conditional(): BelongsTo
    {
        return $this->belongsTo(Conditional::class, 'conditional_id', 'uuid');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by', 'uuid');
    }
}
