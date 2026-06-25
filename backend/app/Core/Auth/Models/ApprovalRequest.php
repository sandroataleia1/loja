<?php

declare(strict_types=1);

namespace App\Core\Auth\Models;

use App\Core\Auth\Enums\ApprovalRequiredEnum;
use App\Core\Auth\Enums\ApprovalStatusEnum;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ApprovalRequest extends Model
{
    use HasUuid;

    protected $table = 'approval_requests';

    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'operation_type',
        'entity_type',
        'entity_uuid',
        'requested_by',
        'requested_at',
        'context',
        'status',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'operation_type' => ApprovalRequiredEnum::class,
            'status'         => ApprovalStatusEnum::class,
            'context'        => 'array',
            'requested_at'   => 'datetime',
            'resolved_at'    => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by', 'uuid');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by', 'uuid');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ApprovalStatusEnum::Pending->value);
    }

    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === ApprovalStatusEnum::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === ApprovalStatusEnum::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === ApprovalStatusEnum::Rejected;
    }

    public function isExpired(): bool
    {
        return $this->status === ApprovalStatusEnum::Expired;
    }

    public function isTerminal(): bool
    {
        return $this->status?->isTerminal() ?? false;
    }
}
