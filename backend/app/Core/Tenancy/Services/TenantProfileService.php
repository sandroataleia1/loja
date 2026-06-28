<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Services;

use App\Core\Features\FeatureEnum;
use App\Core\Tenancy\Enums\TenantProfileEnum;
use App\Core\Tenancy\Models\TenantSettings;

/**
 * Aplica um perfil de onboarding para um tenant.
 *
 * Ativa features pré-definidas e configura TenantSettings com valores padrão.
 * Operação idempotente — pode ser reaplicada sem efeitos colaterais.
 */
final class TenantProfileService
{
    public function __construct(private readonly FeatureManager $features) {}

    /**
     * Aplica o perfil e retorna um relatório do que foi configurado.
     *
     * @return array{profile: string, label: string, features_enabled: string[], settings_applied: array<string, array>}
     */
    public function apply(string $tenantId, TenantProfileEnum $profile, ?string $enabledBy = null): array
    {
        $enabledFeatures = [];

        foreach ($profile->defaultFeatures() as $feature) {
            $this->features->enable($tenantId, $feature, [], $enabledBy);
            $enabledFeatures[] = $feature->value;
        }

        $settingsApplied = [];
        $settings        = TenantSettings::forTenant($tenantId);

        foreach ($profile->defaultSettings() as $section => $values) {
            $existing = $settings->getSection($section);
            $merged   = array_merge($values, $existing); // existing sobrescreve defaults
            $settings->updateSection($section, $merged);
            $settingsApplied[$section] = $values;
        }

        return [
            'profile'          => $profile->value,
            'label'            => $profile->label(),
            'features_enabled' => $enabledFeatures,
            'settings_applied' => $settingsApplied,
        ];
    }

    /**
     * Lista todos os perfis disponíveis com seus defaults.
     *
     * @return array<int, array{value: string, label: string, description: string, default_features: string[]}>
     */
    public function availableProfiles(): array
    {
        return array_map(
            fn (TenantProfileEnum $p) => [
                'value'            => $p->value,
                'label'            => $p->label(),
                'description'      => $p->description(),
                'default_features' => array_map(
                    fn (FeatureEnum $f) => $f->value,
                    $p->defaultFeatures(),
                ),
            ],
            TenantProfileEnum::cases(),
        );
    }
}
