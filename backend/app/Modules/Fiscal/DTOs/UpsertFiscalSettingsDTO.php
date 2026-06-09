<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\DTOs;

use App\Modules\Fiscal\Enums\FiscalEnvironmentEnum;
use App\Modules\Fiscal\Enums\FiscalModeEnum;
use App\Modules\Fiscal\Enums\FiscalProviderEnum;
use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class UpsertFiscalSettingsDTO extends BaseDTO
{
    public function __construct(
        public string                 $companyName,
        public string                 $cnpj,
        public ?string                $ie,
        public int                    $crt,
        public FiscalEnvironmentEnum  $environment,
        public int                    $series,
        public ?string                $csc,
        public ?string                $cscId,
        public ?string                $certificatePath,
        public ?string                $certificatePassword,
        public bool                   $isActive,
        public bool                   $autoIssueNfce,
        public bool                   $allowManualNfce,
        public ?FiscalModeEnum        $defaultFiscalMode,
        public ?FiscalProviderEnum    $defaultProvider,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $defaultModeRaw = $request->string('default_fiscal_mode')->value() ?: null;
        $envRaw         = $request->string('environment')->value() ?: 'homologation';
        $providerRaw    = $request->string('default_provider')->value() ?: null;

        return new static(
            companyName:         $request->string('company_name')->toString(),
            cnpj:                $request->string('cnpj')->toString(),
            ie:                  $request->string('ie')->value() ?: null,
            crt:                 $request->integer('crt'),
            environment:         FiscalEnvironmentEnum::from($envRaw),
            series:              $request->integer('series', 1),
            csc:                 $request->string('csc')->value() ?: null,
            cscId:               $request->string('csc_id')->value() ?: null,
            certificatePath:     $request->string('certificate_path')->value() ?: null,
            certificatePassword: $request->string('certificate_password')->value() ?: null,
            isActive:            $request->boolean('is_active', true),
            autoIssueNfce:       $request->boolean('auto_issue_nfce', true),
            allowManualNfce:     $request->boolean('allow_manual_nfce', true),
            defaultFiscalMode:   $defaultModeRaw !== null ? FiscalModeEnum::from($defaultModeRaw) : null,
            defaultProvider:     $providerRaw !== null ? FiscalProviderEnum::from($providerRaw) : null,
        );
    }
}
