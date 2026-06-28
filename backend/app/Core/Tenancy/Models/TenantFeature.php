<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Models;

use App\Core\Features\FeatureEnum;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Feature flag por tenant.
 *
 * A coluna `feature` armazena o slug (e.g. 'customer.guarantor').
 * Verificação rápida via FeatureManager — não chamar diretamente em hot paths.
 */
final class TenantFeature extends BaseModel
{
    protected $table = 'tenant_features';

    protected $fillable = [
        'tenant_id',
        'feature',
        'is_enabled',
        'metadata',
        'config',
        'enabled_at',
        'enabled_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_enabled' => 'boolean',
            'metadata'   => 'array',
            'config'     => 'array',
            'enabled_at' => 'datetime',
        ]);
    }

    public function enabledByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Auth\Models\User::class, 'enabled_by', 'uuid');
    }

    // ── Static helpers ────────────────────────────────────────────────────────

    public static function isEnabled(string $tenantId, FeatureEnum $feature): bool
    {
        return static::where('tenant_id', $tenantId)
            ->where('feature', $feature->value)
            ->where('is_enabled', true)
            ->exists();
    }

    public static function enable(string $tenantId, FeatureEnum $feature, array $config = [], ?string $enabledBy = null): self
    {
        return static::updateOrCreate(
            ['tenant_id' => $tenantId, 'feature' => $feature->value],
            [
                'is_enabled' => true,
                'config'     => $config ?: null,
                'enabled_at' => now(),
                'enabled_by' => $enabledBy,
            ],
        );
    }

    public static function disable(string $tenantId, FeatureEnum $feature): void
    {
        static::where('tenant_id', $tenantId)
            ->where('feature', $feature->value)
            ->update(['is_enabled' => false]);
    }
}
