<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Models;

use App\Modules\Fiscal\Enums\FiscalEnvironmentEnum;
use App\Modules\Fiscal\Enums\FiscalModeEnum;
use App\Modules\Fiscal\Enums\FiscalPolicyModeEnum;
use App\Shared\Models\BaseModel;
use Database\Factories\TenantFiscalSettingsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Configuração fiscal do tenant — um registro por tenant.
 *
 * certificate_password_encrypted é armazenado cifrado com a chave da aplicação.
 * Nunca expor via Resource sem remover o campo.
 */
final class TenantFiscalSettings extends BaseModel
{
    /** @use HasFactory<TenantFiscalSettingsFactory> */
    use HasFactory;

    protected $table = 'tenant_fiscal_settings';

    protected $fillable = [
        'tenant_id',
        'environment',
        'series',
        'next_number',
        'company_name',
        'cnpj',
        'ie',
        'crt',
        'csc',
        'csc_id',
        'certificate_path',
        'certificate_password_encrypted',
        'is_active',
        'auto_issue_nfce',
        'allow_manual_nfce',
        'default_fiscal_mode',
        'certificate_expires_at',
        'fiscal_retry_max',
        'policy_mode',
        'min_sale_amount_cents',
        'nfe_default_email',
    ];

    /** Nunca serializar a senha do certificado. */
    protected $hidden = ['certificate_password_encrypted'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'environment'                    => FiscalEnvironmentEnum::class,
            'series'                         => 'integer',
            'next_number'                    => 'integer',
            'crt'                            => 'integer',
            'is_active'                      => 'boolean',
            'auto_issue_nfce'                => 'boolean',
            'allow_manual_nfce'              => 'boolean',
            'default_fiscal_mode'            => FiscalModeEnum::class,
            'certificate_password_encrypted' => 'encrypted',
            'certificate_expires_at'         => 'date',
            'fiscal_retry_max'               => 'integer',
            'policy_mode'                    => FiscalPolicyModeEnum::class,
            'min_sale_amount_cents'          => 'integer',
        ]);
    }

    protected static function newFactory(): TenantFiscalSettingsFactory
    {
        return TenantFiscalSettingsFactory::new();
    }
}
