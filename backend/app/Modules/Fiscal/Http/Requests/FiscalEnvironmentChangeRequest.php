<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Http\Requests;

use App\Modules\Fiscal\Enums\FiscalModeEnum;
use App\Modules\Fiscal\Enums\FiscalPolicyModeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Request para upsert de fiscal settings.
 * Inclui validação de PIN obrigatória quando environment muda para production.
 */
final class FiscalEnvironmentChangeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'company_name'          => ['required', 'string', 'max:200'],
            'cnpj'                  => ['required', 'string', 'max:18'],
            'ie'                    => ['nullable', 'string', 'max:20'],
            'crt'                   => ['required', 'integer', 'in:1,2,3'],
            'csc'                   => ['nullable', 'string', 'max:36'],
            'csc_id'                => ['nullable', 'string', 'max:10'],
            'certificate_path'      => ['nullable', 'string', 'max:500'],
            'certificate_password'  => ['nullable', 'string', 'max:255'],
            'is_active'             => ['boolean'],
            'auto_issue_nfce'       => ['boolean'],
            'allow_manual_nfce'     => ['boolean'],
            'default_fiscal_mode'   => ['nullable', new Enum(FiscalModeEnum::class)],
            'policy_mode'           => ['sometimes', new Enum(FiscalPolicyModeEnum::class)],
            'min_sale_amount_cents' => ['sometimes', 'integer', 'min:0'],
            'nfe_default_email'     => ['nullable', 'email', 'max:150'],
            'environment'           => ['sometimes', 'string', 'in:homologation,production'],
            // PIN obrigatório somente ao ativar ambiente de produção
            'pin'                   => [
                'nullable',
                'string',
                'digits_between:4,6',
                \Illuminate\Validation\Rule::requiredIf(
                    fn () => $this->input('environment') === 'production',
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'pin.required' => 'PIN obrigatório para ativar o ambiente de produção SEFAZ.',
            'pin.digits_between' => 'O PIN deve ter entre 4 e 6 dígitos.',
        ];
    }
}
