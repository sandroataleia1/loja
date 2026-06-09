<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FiscalSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'              => $this->uuid,
            'company_name'      => $this->company_name,
            'cnpj'              => $this->cnpj,
            'ie'                => $this->ie,
            'crt'               => $this->crt,
            'csc_id'            => $this->csc_id,
            'has_csc'           => $this->csc !== null,
            'has_certificate'   => $this->certificate_path !== null,
            'certificate_path'  => $this->certificate_path,
            'is_active'            => $this->is_active,
            'auto_issue_nfce'      => $this->auto_issue_nfce,
            'allow_manual_nfce'    => $this->allow_manual_nfce,
            'default_fiscal_mode'   => $this->default_fiscal_mode?->value,
            'policy_mode'           => $this->policy_mode?->value,
            'policy_mode_label'     => $this->policy_mode?->label(),
            'min_sale_amount_cents' => $this->min_sale_amount_cents ?? 0,
            'min_sale_amount'       => ($this->min_sale_amount_cents ?? 0) / 100,
            // certificate_password_encrypted NUNCA exposta
            'updated_at'            => $this->updated_at?->toISOString(),
        ];
    }
}
