<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Services;

use App\Core\Features\FeatureEnum;
use App\Core\Tenancy\Models\TenantFeature;
use Illuminate\Support\Facades\Cache;

/**
 * Ponto único para verificar e alterar feature flags por tenant.
 *
 * Cache: `features:{tenantId}` com TTL de 600s (10 min).
 * Sempre invalide via invalidate() após alterações.
 */
final class FeatureManager
{
    private const CACHE_TTL = 600;

    public function isEnabled(string $tenantId, FeatureEnum $feature): bool
    {
        return $this->allForTenant($tenantId)[$feature->value] ?? false;
    }

    public function isEnabledByKey(string $tenantId, string $featureKey): bool
    {
        return $this->allForTenant($tenantId)[$featureKey] ?? false;
    }

    /** @return array<string, bool> slug => is_enabled */
    public function allForTenant(string $tenantId): array
    {
        return Cache::remember(
            "features:{$tenantId}",
            self::CACHE_TTL,
            fn (): array => TenantFeature::where('tenant_id', $tenantId)
                ->get(['feature', 'is_enabled'])
                ->pluck('is_enabled', 'feature')
                ->map(fn ($v) => (bool) $v)
                ->toArray(),
        );
    }

    /** @return string[] slugs das features ativas */
    public function allEnabled(string $tenantId): array
    {
        return array_keys(array_filter($this->allForTenant($tenantId)));
    }

    public function enable(string $tenantId, FeatureEnum $feature, array $config = [], ?string $enabledBy = null): void
    {
        TenantFeature::enable($tenantId, $feature, $config, $enabledBy);
        $this->invalidate($tenantId);
    }

    public function disable(string $tenantId, FeatureEnum $feature): void
    {
        TenantFeature::disable($tenantId, $feature);
        $this->invalidate($tenantId);
    }

    /**
     * Habilita/desabilita um conjunto de features de uma vez.
     *
     * @param array<string, bool> $featureMap slug => enabled
     */
    public function syncFeatures(string $tenantId, array $featureMap, ?string $enabledBy = null): void
    {
        foreach ($featureMap as $slug => $enabled) {
            $enum = FeatureEnum::tryFrom($slug);

            if ($enum === null) {
                continue;
            }

            if ($enabled) {
                TenantFeature::enable($tenantId, $enum, [], $enabledBy);
            } else {
                TenantFeature::disable($tenantId, $enum);
            }
        }

        $this->invalidate($tenantId);
    }

    public function invalidate(string $tenantId): void
    {
        Cache::forget("features:{$tenantId}");
    }

    /**
     * Lista completa de todas as features com status para o tenant.
     *
     * @return array<int, array{feature: string, label: string, description: string, is_enabled: bool}>
     */
    public function listAll(string $tenantId): array
    {
        $enabled = $this->allForTenant($tenantId);

        return array_map(
            fn (FeatureEnum $f) => [
                'feature'     => $f->value,
                'label'       => $f->label(),
                'description' => $f->description(),
                'is_enabled'  => $enabled[$f->value] ?? false,
            ],
            FeatureEnum::cases(),
        );
    }
}
