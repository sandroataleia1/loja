<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Core\Tenancy\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação para atualização de Variante.
 *
 * Separado de StoreVariantRequest porque:
 * - product_id não é editável após criação
 * - sku precisa ignorar a variante atual na checagem de unicidade
 * - todos os campos são opcionais (PATCH semântico)
 */
final class UpdateVariantRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var \App\Modules\Catalog\Models\Variant $variant */
        $variant = $this->route('variant');

        return [
            'sku' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('catalog_variants', 'sku')
                    ->where('tenant_id', TenantContext::getId())
                    ->ignore($variant?->uuid, 'uuid'),
            ],
            'price_cents'      => ['sometimes', 'integer', 'min:0'],
            'name'             => ['sometimes', 'nullable', 'string', 'max:200'],
            'barcode'          => ['sometimes', 'nullable', 'string', 'max:50'],
            'gtin'             => ['sometimes', 'nullable', 'string', 'max:14'],
            'cost_cents'       => ['sometimes', 'nullable', 'integer', 'min:0'],
            'compare_at_cents' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'weight_g'         => ['sometimes', 'nullable', 'integer', 'min:0'],
            'dimensions'       => ['sometimes', 'nullable', 'array'],
            'dimensions.width_cm'  => ['nullable', 'numeric', 'min:0'],
            'dimensions.height_cm' => ['nullable', 'numeric', 'min:0'],
            'dimensions.depth_cm'  => ['nullable', 'numeric', 'min:0'],
            'is_active'        => ['sometimes', 'boolean'],
            'is_default'       => ['sometimes', 'boolean'],
            'sort_order'       => ['sometimes', 'integer', 'min:0'],
            'attribute_ids'    => ['sometimes', 'nullable', 'array'],
            'attribute_ids.*'  => ['uuid', 'exists:catalog_attributes,uuid'],
            // Fiscal
            'ncm'              => ['sometimes', 'nullable', 'string', 'regex:/^\d{8}$/'],
            'cest'             => ['sometimes', 'nullable', 'string', 'max:9'],
            'cfop_default'     => ['sometimes', 'nullable', 'string', 'max:5'],
            'origin_code'      => ['sometimes', 'nullable', 'integer', 'between:0,8'],
            'tax_profile_id'   => ['sometimes', 'nullable', 'uuid', 'exists:tax_profiles,uuid'],
        ];
    }
}
