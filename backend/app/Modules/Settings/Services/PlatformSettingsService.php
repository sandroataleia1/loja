<?php

declare(strict_types=1);

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Models\PlatformSettingDefault;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Leitura dos defaults globais da plataforma com cache Redis (TTL 1h).
 *
 * Hierarquia de fallback:
 *   1. Redis
 *   2. Banco de dados (platform_setting_defaults)
 *   3. null (caller decide o fallback)
 *
 * Constants PHP nas models existentes servem como fallback de emergência
 * quando nem Redis nem banco estão disponíveis.
 */
final class PlatformSettingsService
{
    private const TTL = 3600; // 1 hora

    public function getDefault(string $section, string $key): mixed
    {
        $cacheKey = "platform_defaults:{$section}:{$key}";

        try {
            return Cache::remember(
                $cacheKey,
                self::TTL,
                fn () => $this->loadFromDb($section, $key),
            );
        } catch (Throwable $e) {
            Log::warning('PlatformSettingsService::getDefault failed', [
                'section' => $section,
                'key'     => $key,
                'error'   => $e->getMessage(),
            ]);

            // Fallback direto ao banco sem cache
            try {
                return $this->loadFromDb($section, $key);
            } catch (Throwable) {
                return null;
            }
        }
    }

    /** Retorna todos os defaults de uma seção como array chave→valor. */
    public function getSectionDefaults(string $section): array
    {
        $cacheKey = "platform_defaults:{$section}";

        try {
            $cached = Cache::remember(
                $cacheKey,
                self::TTL,
                fn () => PlatformSettingDefault::where('section', $section)
                    ->get()
                    ->mapWithKeys(fn (PlatformSettingDefault $row) => [
                        $row->key => $row->typedValue(),
                    ])
                    ->toArray(),
            );

            return is_array($cached) ? $cached : [];
        } catch (Throwable $e) {
            Log::warning('PlatformSettingsService::getSectionDefaults failed', [
                'section' => $section,
                'error'   => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function invalidate(string $section, string $key): void
    {
        try {
            Cache::forget("platform_defaults:{$section}:{$key}");
            Cache::forget("platform_defaults:{$section}");
        } catch (Throwable) {
        }
    }

    private function loadFromDb(string $section, string $key): mixed
    {
        $row = PlatformSettingDefault::where('section', $section)
            ->where('key', $key)
            ->first();

        return $row?->typedValue();
    }
}
