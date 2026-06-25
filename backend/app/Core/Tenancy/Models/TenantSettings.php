<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Models;

use App\Modules\Settings\Services\TenantSettingsCache;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configurações operacionais do tenant — um registro por tenant.
 *
 * Cada seção é um array JSON com defaults semânticos.
 * Nunca acessar diretamente: usar TenantSettings::forTenant($tenantId).
 *
 * Leitura: verifica Redis antes de query.
 * Escrita: invalida cache da seção alterada.
 */
final class TenantSettings extends Model
{
    use HasUuid;

    protected $table = 'tenant_settings';

    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'commercial',
        'financial',
        'credit',
        'inventory',
        'billing',
        'commission',
        'logistics',
        'security',
    ];

    // ── Defaults por seção ────────────────────────────────────────────────────

    public const COMMERCIAL_DEFAULTS = [
        'require_salesperson'          => false,
        'require_quote_before_order'   => false,
        'allow_order_without_stock'    => false,
        'allow_free_discount'          => true,
        'default_discount_limit'       => 10,
        'require_discount_approval'    => false,
        'require_price_approval'       => false,
        'use_price_table'              => false,
        'quote_validity_days'          => 30,
        'freight_policy'               => 'free',
        'freight_fixed_cents'          => null,
        'free_above_cents'             => null,
        'min_order_by_channel'         => [
            'counter'        => 0,
            'delivery'       => 5000,
            'representative' => 10000,
        ],
    ];

    public const FINANCIAL_DEFAULTS = [
        'default_interest_rate'          => 1.0,
        'default_fine_rate'              => 2.0,
        'default_discount_rate'          => 0.0,
        'tolerance_days'                 => 0,
        'rounding_mode'                  => 'half_up',
        'auto_generate_bills'            => true,
        'auto_apply_customer_credit'     => false,
        'currency'                       => 'BRL',
        'locale'                         => 'pt_BR',
        'decimal_separator'              => 'comma',
        'default_payment_method'         => null,
        'default_bank_account_id'        => null,
    ];

    public const CREDIT_DEFAULTS = [
        'default_credit_limit'           => 0,
        'block_sale_without_credit'      => false,
        'allow_exceed_limit'             => true,
        'require_approval_to_exceed'     => false,
    ];

    public const INVENTORY_DEFAULTS = [
        'allow_negative_stock'           => false,
        'auto_reserve'                   => true,
        'auto_deduct'                    => true,
        'require_picking'                => false,
        'require_shipping'               => false,
        'require_counting'               => false,
        'auto_update_cost'               => true,
        'costing_method'                 => 'average',
        'lot_control_enabled'            => false,
        'expiry_control_enabled'         => false,
        'min_stock_alert'                => 0,
        'safety_stock'                   => 0,
    ];

    public const BILLING_DEFAULTS = [
        'billing_mode'                   => 'by_order',
    ];

    public const COMMISSION_DEFAULTS = [
        'commission_on_sale'             => true,
        'commission_on_payment'          => false,
        'proportional_commission'        => false,
        'commission_by_margin'           => false,
    ];

    public const LOGISTICS_DEFAULTS = [
        'require_picking'                => false,
        'require_shipping'               => false,
        'require_packing_list'           => false,
        'require_delivery'               => false,
        'require_delivery_receipt'       => false,
        'delivery_radius_km'             => null,
        'default_delivery_days'          => 3,
    ];

    public const SECURITY_DEFAULTS = [
        'max_login_attempts' => 5,
        'lockout_minutes'    => 15,
    ];

    // ── Casts ─────────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'commercial' => 'array',
            'financial'  => 'array',
            'credit'     => 'array',
            'inventory'  => 'array',
            'billing'    => 'array',
            'commission' => 'array',
            'logistics'  => 'array',
            'security'   => 'array',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'uuid');
    }

    // ── Static helpers ────────────────────────────────────────────────────────

    /** Retorna as configurações do tenant, criando com defaults se não existir. */
    public static function forTenant(string $tenantId): self
    {
        return static::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'commercial' => self::COMMERCIAL_DEFAULTS,
                'financial'  => self::FINANCIAL_DEFAULTS,
                'credit'     => self::CREDIT_DEFAULTS,
                'inventory'  => self::INVENTORY_DEFAULTS,
                'billing'    => self::BILLING_DEFAULTS,
                'commission' => self::COMMISSION_DEFAULTS,
                'logistics'  => self::LOGISTICS_DEFAULTS,
                'security'   => self::SECURITY_DEFAULTS,
            ],
        );
    }

    // ── Cache-aware setters/getters ───────────────────────────────────────────

    /**
     * Atualiza uma seção específica fazendo merge dos valores.
     * Invalida o cache da seção alterada.
     */
    public function updateSection(string $section, array $values): void
    {
        $current = $this->{$section} ?? [];
        $merged  = array_merge($current, $values);

        $this->update([$section => $merged]);

        app(TenantSettingsCache::class)->forget($this->tenant_id, $section);
    }

    /**
     * Retorna uma seção com os defaults mesclados (garante que todos os campos existam).
     * Usa cache Redis antes de acessar o banco.
     */
    public function getSection(string $section): array
    {
        $defaults = $this->defaultsForSection($section);

        $cached = app(TenantSettingsCache::class)->get($this->tenant_id, $section);

        if ($cached !== null) {
            return array_merge($defaults, $cached);
        }

        $data = array_merge($defaults, $this->{$section} ?? []);

        app(TenantSettingsCache::class)->put($this->tenant_id, $section, $this->{$section} ?? []);

        return $data;
    }

    private function defaultsForSection(string $section): array
    {
        return match ($section) {
            'commercial' => self::COMMERCIAL_DEFAULTS,
            'financial'  => self::FINANCIAL_DEFAULTS,
            'credit'     => self::CREDIT_DEFAULTS,
            'inventory'  => self::INVENTORY_DEFAULTS,
            'billing'    => self::BILLING_DEFAULTS,
            'commission' => self::COMMISSION_DEFAULTS,
            'logistics'  => self::LOGISTICS_DEFAULTS,
            'security'   => self::SECURITY_DEFAULTS,
            default      => [],
        };
    }

    public function maxLoginAttempts(): int
    {
        $sec = $this->getSection('security');
        return (int) ($sec['max_login_attempts'] ?? self::SECURITY_DEFAULTS['max_login_attempts']);
    }

    public function lockoutMinutes(): int
    {
        $sec = $this->getSection('security');
        return (int) ($sec['lockout_minutes'] ?? self::SECURITY_DEFAULTS['lockout_minutes']);
    }
}
