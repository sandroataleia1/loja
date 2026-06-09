<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Fiscal\Enums\FiscalModeEnum;
use App\Modules\Fiscal\Models\TenantFiscalSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantFiscalSettings>
 */
final class TenantFiscalSettingsFactory extends Factory
{
    protected $model = TenantFiscalSettings::class;

    public function definition(): array
    {
        return [
            'company_name'                   => $this->faker->company(),
            'cnpj'                           => $this->faker->numerify('##.###.###/####-##'),
            'ie'                             => $this->faker->numerify('###.###.###.###'),
            'crt'                            => 1,
            'csc'                            => null,
            'csc_id'                         => null,
            'certificate_path'               => null,
            'certificate_password_encrypted' => null,
            'is_active'                      => true,
            'auto_issue_nfce'                => true,
            'allow_manual_nfce'              => true,
            'default_fiscal_mode'            => FiscalModeEnum::Nfce->value,
        ];
    }

    public function withCsc(): static
    {
        return $this->state([
            'csc'    => $this->faker->uuid(),
            'csc_id' => $this->faker->numerify('######'),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function noAutoIssue(): static
    {
        return $this->state(['auto_issue_nfce' => false]);
    }

    public function noManualNfce(): static
    {
        return $this->state(['allow_manual_nfce' => false]);
    }

    public function withDefaultMode(FiscalModeEnum $mode): static
    {
        return $this->state(['default_fiscal_mode' => $mode->value]);
    }
}
