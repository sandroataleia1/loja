<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_setting_defaults', function (Blueprint $table): void {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('section', 50);
            $table->string('key', 100);
            $table->text('value');
            $table->enum('value_type', ['string', 'integer', 'boolean', 'json'])->default('string');
            $table->text('description')->nullable();
            $table->boolean('is_overridable')->default(true);
            $table->timestamps();

            $table->unique(['section', 'key'], 'platform_defaults_section_key_uq');
        });

        // Seed com todos os defaults atuais das constants PHP
        $now   = now();
        $rows  = [];

        foreach ($this->allDefaults() as $section => $defaults) {
            foreach ($defaults as $key => $value) {
                $rows[] = [
                    'uuid'           => \Illuminate\Support\Str::uuid()->toString(),
                    'section'        => $section,
                    'key'            => $key,
                    'value'          => is_array($value) ? json_encode($value) : (string) ($value ?? ''),
                    'value_type'     => $this->detectType($value),
                    'description'    => null,
                    'is_overridable' => true,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }
        }

        DB::table('platform_setting_defaults')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_setting_defaults');
    }

    private function detectType(mixed $value): string
    {
        if (is_array($value))   return 'json';
        if (is_bool($value))    return 'boolean';
        if (is_int($value))     return 'integer';
        return 'string';
    }

    private function allDefaults(): array
    {
        return [
            'commercial' => [
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
                'min_order_by_channel'         => ['counter' => 0, 'delivery' => 5000, 'representative' => 10000],
            ],
            'financial' => [
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
            ],
            'credit' => [
                'default_credit_limit'           => 0,
                'block_sale_without_credit'      => false,
                'allow_exceed_limit'             => true,
                'require_approval_to_exceed'     => false,
            ],
            'inventory' => [
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
            ],
            'billing' => [
                'billing_mode'                   => 'by_order',
            ],
            'commission' => [
                'commission_on_sale'             => true,
                'commission_on_payment'          => false,
                'proportional_commission'        => false,
                'commission_by_margin'           => false,
            ],
            'logistics' => [
                'require_picking'                => false,
                'require_shipping'               => false,
                'require_packing_list'           => false,
                'require_delivery'               => false,
                'require_delivery_receipt'       => false,
                'delivery_radius_km'             => null,
                'default_delivery_days'          => 3,
            ],
            'security' => [
                'max_login_attempts' => 5,
                'lockout_minutes'    => 15,
            ],
        ];
    }
};
