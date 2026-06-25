<?php

declare(strict_types=1);

namespace App\Modules\Settings\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cache Redis das configurações operacionais por tenant.
 *
 * TTL longo (10 min) pois settings mudam raramente.
 * Toda falha de cache é silenciosa — nunca bloqueia o caller.
 *
 * Chaves: "tenant_settings:{tenantId}:{section}"
 */
final class TenantSettingsCache
{
    private const TTL = 600; // 10 minutos

    /** Seções conhecidas (para forgetAll sem SCAN). */
    public const SECTIONS = [
        'commercial',
        'financial',
        'credit',
        'inventory',
        'billing',
        'commission',
        'logistics',
        'security',
    ];

    public function get(string $tenantId, string $section): ?array
    {
        try {
            $value = Cache::get($this->key($tenantId, $section));
            return is_array($value) ? $value : null;
        } catch (Throwable $e) {
            Log::warning('TenantSettingsCache::get failed', [
                'tenant_id' => $tenantId,
                'section'   => $section,
                'error'     => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function put(string $tenantId, string $section, array $data): void
    {
        try {
            Cache::put($this->key($tenantId, $section), $data, self::TTL);
        } catch (Throwable $e) {
            Log::warning('TenantSettingsCache::put failed', [
                'tenant_id' => $tenantId,
                'section'   => $section,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    public function forget(string $tenantId, string $section): void
    {
        try {
            Cache::forget($this->key($tenantId, $section));
        } catch (Throwable $e) {
            Log::warning('TenantSettingsCache::forget failed', [
                'tenant_id' => $tenantId,
                'section'   => $section,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    public function forgetAll(string $tenantId): void
    {
        foreach (self::SECTIONS as $section) {
            $this->forget($tenantId, $section);
        }
    }

    private function key(string $tenantId, string $section): string
    {
        return "tenant_settings:{$tenantId}:{$section}";
    }
}
