<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Http\Controllers;

use App\Core\Tenancy\Enums\TenantFeatureEnum;
use App\Core\Tenancy\Models\TenantFeature;
use App\Core\Tenancy\Models\TenantSettings;
use App\Core\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\Settings\Enums\CostingMethodEnum;
use App\Modules\Settings\Enums\FreightPolicyEnum;
use App\Modules\Settings\Services\ConfigHistoryService;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Configurações operacionais do tenant.
 *
 * Todas as configurações são por seção: commercial, financial, credit,
 * inventory, billing, commission, logistics.
 *
 * Feature flags são gerenciados separadamente via /system-settings/features.
 */
final class TenantSettingsController extends Controller
{
    use HasApiResponse;

    private const SECTIONS = [
        'commercial',
        'financial',
        'credit',
        'inventory',
        'billing',
        'commission',
        'logistics',
    ];

    public function __construct(private readonly ConfigHistoryService $history) {}

    public function show(): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();
        $settings = TenantSettings::forTenant($tenantId);

        return $this->success([
            'commercial' => $settings->getSection('commercial'),
            'financial'  => $settings->getSection('financial'),
            'credit'     => $settings->getSection('credit'),
            'inventory'  => $settings->getSection('inventory'),
            'billing'    => $settings->getSection('billing'),
            'commission' => $settings->getSection('commission'),
            'logistics'  => $settings->getSection('logistics'),
        ]);
    }

    public function update(Request $request, string $section): JsonResponse
    {
        if (! in_array($section, self::SECTIONS, true)) {
            return $this->error("Seção inválida: {$section}.", 422);
        }

        $tenantId = TenantContext::getIdOrFail();
        $data     = $request->validate($this->rulesForSection($section));

        $settings   = TenantSettings::forTenant($tenantId);
        $oldSection = $settings->getSection($section);

        $settings->updateSection($section, $data);

        // Registra histórico das chaves alteradas
        $this->history->record(
            tenantId:  $tenantId,
            section:   $section,
            oldValues: $oldSection,
            newValues: array_merge($oldSection, $data),
            changedBy: auth()->user(),
            request:   $request,
        );

        return $this->success(
            data: $settings->fresh()->getSection($section),
            message: 'Configurações atualizadas com sucesso.',
        );
    }

    public function showFeatures(): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $enabled = TenantFeature::where('tenant_id', $tenantId)
            ->where('is_enabled', true)
            ->pluck('is_enabled', 'feature')
            ->toArray();

        $features = array_map(fn (TenantFeatureEnum $f) => [
            'feature'     => $f->value,
            'label'       => $f->label(),
            'description' => $f->description(),
            'is_enabled'  => (bool) ($enabled[$f->value] ?? false),
        ], TenantFeatureEnum::cases());

        return $this->success($features);
    }

    public function updateFeatures(Request $request): JsonResponse
    {
        $tenantId      = TenantContext::getIdOrFail();
        $validFeatures = array_column(TenantFeatureEnum::cases(), 'value');

        $request->validate([
            'features'   => ['required', 'array'],
            'features.*' => ['boolean'],
        ]);

        /** @var array<string, bool> $features */
        $features = $request->input('features', []);

        foreach ($features as $feature => $isEnabled) {
            if (! in_array($feature, $validFeatures, true)) {
                continue;
            }

            $enum = TenantFeatureEnum::from($feature);

            if ($isEnabled) {
                TenantFeature::enable($tenantId, $enum);
            } else {
                TenantFeature::disable($tenantId, $enum);
            }
        }

        return $this->success(message: 'Recursos atualizados com sucesso.');
    }

    // ── Regras de validação por seção ─────────────────────────────────────────

    /** @return array<string, mixed> */
    private function rulesForSection(string $section): array
    {
        return match ($section) {
            'commercial' => [
                'require_salesperson'        => ['sometimes', 'boolean'],
                'require_quote_before_order' => ['sometimes', 'boolean'],
                'allow_order_without_stock'  => ['sometimes', 'boolean'],
                'allow_free_discount'        => ['sometimes', 'boolean'],
                'default_discount_limit'     => ['sometimes', 'numeric', 'min:0', 'max:100'],
                'require_discount_approval'  => ['sometimes', 'boolean'],
                'require_price_approval'     => ['sometimes', 'boolean'],
                'use_price_table'            => ['sometimes', 'boolean'],
                'quote_validity_days'        => ['sometimes', 'integer', 'min:1', 'max:3650'],
                'freight_policy'             => ['sometimes', Rule::enum(FreightPolicyEnum::class)],
                'freight_fixed_cents'        => [
                    'nullable', 'integer', 'min:0',
                    Rule::requiredIf(fn () => request()->input('freight_policy') === 'fixed'),
                ],
                'free_above_cents'           => [
                    'nullable', 'integer', 'min:0',
                    Rule::requiredIf(fn () => request()->input('freight_policy') === 'free_above_amount'),
                ],
                'min_order_by_channel'             => ['sometimes', 'nullable', 'array'],
                'min_order_by_channel.counter'     => ['sometimes', 'nullable', 'integer', 'min:0'],
                'min_order_by_channel.delivery'    => ['sometimes', 'nullable', 'integer', 'min:0'],
                'min_order_by_channel.representative' => ['sometimes', 'nullable', 'integer', 'min:0'],
            ],
            'financial' => [
                'default_interest_rate'      => ['sometimes', 'numeric', 'min:0', 'max:100'],
                'default_fine_rate'          => ['sometimes', 'numeric', 'min:0', 'max:100'],
                'default_discount_rate'      => ['sometimes', 'numeric', 'min:0', 'max:100'],
                'tolerance_days'             => ['sometimes', 'integer', 'min:0', 'max:365'],
                'rounding_mode'              => ['sometimes', Rule::in(['half_up', 'half_down', 'ceil', 'floor'])],
                'auto_generate_bills'        => ['sometimes', 'boolean'],
                'auto_apply_customer_credit' => ['sometimes', 'boolean'],
                'currency'                   => ['sometimes', 'string', 'size:3'],
                'locale'                     => ['sometimes', Rule::in(['pt_BR', 'en_US'])],
                'decimal_separator'          => ['sometimes', Rule::in(['comma', 'dot'])],
                'default_payment_method'     => ['sometimes', 'nullable', 'string', 'max:50'],
                'default_bank_account_id'    => ['sometimes', 'nullable', 'uuid'],
            ],
            'credit' => [
                'default_credit_limit'       => ['sometimes', 'numeric', 'min:0'],
                'block_sale_without_credit'  => ['sometimes', 'boolean'],
                'allow_exceed_limit'         => ['sometimes', 'boolean'],
                'require_approval_to_exceed' => ['sometimes', 'boolean'],
            ],
            'inventory' => [
                'allow_negative_stock'       => ['sometimes', 'boolean'],
                'auto_reserve'               => ['sometimes', 'boolean'],
                'auto_deduct'                => ['sometimes', 'boolean'],
                'require_picking'            => ['sometimes', 'boolean'],
                'require_shipping'           => ['sometimes', 'boolean'],
                'require_counting'           => ['sometimes', 'boolean'],
                'auto_update_cost'           => ['sometimes', 'boolean'],
                'costing_method'             => ['sometimes', Rule::enum(CostingMethodEnum::class)],
                'lot_control_enabled'        => ['sometimes', 'boolean'],
                'expiry_control_enabled'     => ['sometimes', 'boolean'],
                'min_stock_alert'            => ['sometimes', 'integer', 'min:0'],
                'safety_stock'               => [
                    'sometimes', 'integer', 'min:0',
                    function (string $attribute, mixed $value, \Closure $fail): void {
                        $minAlert = (int) request()->input('min_stock_alert', 0);
                        if ($minAlert > 0 && (int) $value > $minAlert) {
                            $fail('O estoque de segurança não pode ser maior que o estoque mínimo de alerta.');
                        }
                    },
                ],
            ],
            'billing' => [
                'billing_mode'               => ['sometimes', Rule::in(['by_order', 'by_invoice', 'future'])],
            ],
            'commission' => [
                'commission_on_sale'         => ['sometimes', 'boolean'],
                'commission_on_payment'      => ['sometimes', 'boolean'],
                'proportional_commission'    => ['sometimes', 'boolean'],
                'commission_by_margin'       => ['sometimes', 'boolean'],
            ],
            'logistics' => [
                'require_picking'            => ['sometimes', 'boolean'],
                'require_shipping'           => ['sometimes', 'boolean'],
                'require_packing_list'       => ['sometimes', 'boolean'],
                'require_delivery'           => ['sometimes', 'boolean'],
                'require_delivery_receipt'   => ['sometimes', 'boolean'],
                'delivery_radius_km'         => ['sometimes', 'nullable', 'integer', 'min:1', 'max:500'],
                'default_delivery_days'      => ['sometimes', 'integer', 'min:0', 'max:90'],
            ],
            default => [],
        };
    }
}
