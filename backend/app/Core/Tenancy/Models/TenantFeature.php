<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Models;

use App\Core\Tenancy\Enums\TenantFeatureEnum;
use App\Shared\Models\BaseModel;

/**
 * Feature flag por tenant.
 *
 * Controla módulos opcionais: fiscal, financeiro, multi-loja, offline, IA, etc.
 * Verificação rápida: TenantFeature::isEnabled($tenantId, TenantFeatureEnum::FiscalEnabled)
 */
final class TenantFeature extends BaseModel
{
    protected $table = 'tenant_features';

    protected $fillable = ['tenant_id', 'feature', 'is_enabled', 'metadata'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'feature'    => TenantFeatureEnum::class,
            'is_enabled' => 'boolean',
            'metadata'   => 'array',
        ]);
    }

    // ── Static helpers ────────────────────────────────────────────────────────

    public static function isEnabled(string $tenantId, TenantFeatureEnum $feature): bool
    {
        return static::where('tenant_id', $tenantId)
            ->where('feature', $feature->value)
            ->where('is_enabled', true)
            ->exists();
    }

    public static function enable(string $tenantId, TenantFeatureEnum $feature, array $metadata = []): self
    {
        return static::updateOrCreate(
            ['tenant_id' => $tenantId, 'feature' => $feature->value],
            ['is_enabled' => true, 'metadata' => $metadata],
        );
    }

    public static function disable(string $tenantId, TenantFeatureEnum $feature): void
    {
        static::where('tenant_id', $tenantId)
            ->where('feature', $feature->value)
            ->update(['is_enabled' => false]);
    }
}
