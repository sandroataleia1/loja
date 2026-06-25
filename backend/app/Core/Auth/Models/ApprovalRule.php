<?php

declare(strict_types=1);

namespace App\Core\Auth\Models;

use App\Core\Auth\Enums\ApprovalThresholdTypeEnum;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ApprovalRule extends BaseModel
{
    protected $table = 'approval_rules';

    protected $fillable = [
        'tenant_id',
        'operation_type',
        'threshold_type',
        'threshold_value',
        'required_role_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'threshold_type'  => ApprovalThresholdTypeEnum::class,
            'threshold_value' => 'decimal:2',
            'is_active'       => 'boolean',
        ]);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function requiredRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'required_role_id', 'uuid');
    }

    // ── Domain logic ──────────────────────────────────────────────────────────

    /**
     * Avalia se o valor fornecido dispara a necessidade de aprovação.
     *
     * @param float $value Valor a avaliar (montante em reais ou percentual de desconto)
     */
    public function requiresApproval(float $value): bool
    {
        return match ($this->threshold_type) {
            ApprovalThresholdTypeEnum::Always     => true,
            ApprovalThresholdTypeEnum::Amount     => $value >= (float) $this->threshold_value,
            ApprovalThresholdTypeEnum::Percentage => $value >= (float) $this->threshold_value,
        };
    }
}
