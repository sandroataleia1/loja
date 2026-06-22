<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Finance\Models\PaymentCondition;
use App\Modules\Finance\Models\PaymentMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedMethods();
        $this->seedConditions();
    }

    private function seedMethods(): void
    {
        if (PaymentMethod::whereNull('tenant_id')->exists()) {
            $this->updateExistingMethods();
            return;
        }

        $defaults = [
            [
                'name'                        => 'Dinheiro',
                'type'                        => 'cash',
                'sort_order'                  => 1,
                'accepts_change'              => true,
                'allow_installments'          => false,
                'max_installments'            => 1,
                'min_installment_value_cents' => 0,
                'requires_authorization'      => false,
                'integrates_financial'        => true,
            ],
            [
                'name'                        => 'PIX',
                'type'                        => 'pix',
                'sort_order'                  => 2,
                'accepts_change'              => false,
                'allow_installments'          => false,
                'max_installments'            => 1,
                'min_installment_value_cents' => 0,
                'requires_authorization'      => false,
                'integrates_financial'        => true,
            ],
            [
                'name'                        => 'Cartão de Crédito',
                'type'                        => 'credit_card',
                'sort_order'                  => 3,
                'accepts_change'              => false,
                'allow_installments'          => true,
                'max_installments'            => 12,
                'min_installment_value_cents' => 1000,
                'requires_authorization'      => true,
                'integrates_financial'        => true,
            ],
            [
                'name'                        => 'Cartão de Débito',
                'type'                        => 'debit_card',
                'sort_order'                  => 4,
                'accepts_change'              => false,
                'allow_installments'          => false,
                'max_installments'            => 1,
                'min_installment_value_cents' => 0,
                'requires_authorization'      => true,
                'integrates_financial'        => true,
            ],
            [
                'name'                        => 'Boleto Bancário',
                'type'                        => 'boleto',
                'sort_order'                  => 5,
                'accepts_change'              => false,
                'allow_installments'          => false,
                'max_installments'            => 1,
                'min_installment_value_cents' => 0,
                'requires_authorization'      => false,
                'integrates_financial'        => true,
            ],
            [
                'name'                        => 'Transferência Bancária',
                'type'                        => 'bank_transfer',
                'sort_order'                  => 6,
                'accepts_change'              => false,
                'allow_installments'          => false,
                'max_installments'            => 1,
                'min_installment_value_cents' => 0,
                'requires_authorization'      => false,
                'integrates_financial'        => true,
            ],
            [
                'name'                        => 'Cheque',
                'type'                        => 'check',
                'sort_order'                  => 7,
                'accepts_change'              => false,
                'allow_installments'          => false,
                'max_installments'            => 1,
                'min_installment_value_cents' => 0,
                'requires_authorization'      => false,
                'integrates_financial'        => true,
            ],
            [
                'name'                        => 'Crediário',
                'type'                        => 'store_credit',
                'sort_order'                  => 8,
                'accepts_change'              => false,
                'allow_installments'          => true,
                'max_installments'            => 24,
                'min_installment_value_cents' => 0,
                'requires_authorization'      => false,
                'integrates_financial'        => true,
            ],
            [
                'name'                        => 'Convênio',
                'type'                        => 'convention',
                'sort_order'                  => 9,
                'accepts_change'              => false,
                'allow_installments'          => true,
                'max_installments'            => 24,
                'min_installment_value_cents' => 0,
                'requires_authorization'      => false,
                'integrates_financial'        => true,
            ],
            [
                'name'                        => 'Vale / Voucher',
                'type'                        => 'voucher',
                'sort_order'                  => 10,
                'accepts_change'              => false,
                'allow_installments'          => false,
                'max_installments'            => 1,
                'min_installment_value_cents' => 0,
                'requires_authorization'      => false,
                'integrates_financial'        => true,
            ],
        ];

        $now  = now();
        $rows = array_map(fn ($d) => array_merge($d, [
            'uuid'       => (string) Str::uuid(),
            'tenant_id'  => null,
            'is_active'  => true,
            'is_system'  => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $defaults);

        PaymentMethod::insert($rows);
    }

    private function updateExistingMethods(): void
    {
        $updates = [
            'cash'         => ['accepts_change' => true,  'allow_installments' => false, 'max_installments' => 1,  'requires_authorization' => false, 'integrates_financial' => true],
            'pix'          => ['accepts_change' => false, 'allow_installments' => false, 'max_installments' => 1,  'requires_authorization' => false, 'integrates_financial' => true],
            'credit_card'  => ['accepts_change' => false, 'allow_installments' => true,  'max_installments' => 12, 'requires_authorization' => true,  'integrates_financial' => true, 'min_installment_value_cents' => 1000],
            'debit_card'   => ['accepts_change' => false, 'allow_installments' => false, 'max_installments' => 1,  'requires_authorization' => true,  'integrates_financial' => true],
            'boleto'       => ['accepts_change' => false, 'allow_installments' => false, 'max_installments' => 1,  'requires_authorization' => false, 'integrates_financial' => true],
            'bank_transfer'=> ['accepts_change' => false, 'allow_installments' => false, 'max_installments' => 1,  'requires_authorization' => false, 'integrates_financial' => true],
            'check'        => ['accepts_change' => false, 'allow_installments' => false, 'max_installments' => 1,  'requires_authorization' => false, 'integrates_financial' => true],
            'store_credit' => ['accepts_change' => false, 'allow_installments' => true,  'max_installments' => 24, 'requires_authorization' => false, 'integrates_financial' => true],
            'voucher'      => ['accepts_change' => false, 'allow_installments' => false, 'max_installments' => 1,  'requires_authorization' => false, 'integrates_financial' => true],
        ];

        foreach ($updates as $type => $fields) {
            PaymentMethod::whereNull('tenant_id')
                ->where('type', $type)
                ->update($fields);
        }

        // Adicionar Convênio se ainda não existir
        if (! PaymentMethod::whereNull('tenant_id')->where('type', 'convention')->exists()) {
            PaymentMethod::create([
                'uuid'                        => (string) Str::uuid(),
                'tenant_id'                   => null,
                'name'                        => 'Convênio',
                'type'                        => 'convention',
                'sort_order'                  => 9,
                'accepts_change'              => false,
                'allow_installments'          => true,
                'max_installments'            => 24,
                'min_installment_value_cents' => 0,
                'requires_authorization'      => false,
                'integrates_financial'        => true,
                'is_active'                   => true,
                'is_system'                   => true,
            ]);
        }
    }

    private function seedConditions(): void
    {
        if (PaymentCondition::whereNull('tenant_id')->exists()) {
            $this->updateExistingConditions();
            return;
        }

        $defaults = [
            [
                'name'              => 'À vista',
                'type'              => 'a_vista',
                'installment_count' => 1,
                'first_due_days'    => 0,
                'interval_days'     => 30,
                'sort_order'        => 1,
            ],
            [
                'name'              => '7 dias',
                'type'              => 'a_vista',
                'installment_count' => 1,
                'first_due_days'    => 7,
                'interval_days'     => 30,
                'sort_order'        => 2,
            ],
            [
                'name'              => '10 dias',
                'type'              => 'a_vista',
                'installment_count' => 1,
                'first_due_days'    => 10,
                'interval_days'     => 30,
                'sort_order'        => 3,
            ],
            [
                'name'              => '14 dias',
                'type'              => 'a_vista',
                'installment_count' => 1,
                'first_due_days'    => 14,
                'interval_days'     => 30,
                'sort_order'        => 4,
            ],
            [
                'name'              => '21 dias',
                'type'              => 'a_vista',
                'installment_count' => 1,
                'first_due_days'    => 21,
                'interval_days'     => 30,
                'sort_order'        => 5,
            ],
            [
                'name'              => '28 dias',
                'type'              => 'a_vista',
                'installment_count' => 1,
                'first_due_days'    => 28,
                'interval_days'     => 30,
                'sort_order'        => 6,
            ],
            [
                'name'              => '30 dias',
                'type'              => 'a_vista',
                'installment_count' => 1,
                'first_due_days'    => 30,
                'interval_days'     => 30,
                'sort_order'        => 7,
            ],
            [
                'name'              => '30/60 dias',
                'type'              => 'parcelado',
                'installment_count' => 2,
                'first_due_days'    => 30,
                'interval_days'     => 30,
                'sort_order'        => 8,
            ],
            [
                'name'              => '30/60/90 dias',
                'type'              => 'parcelado',
                'installment_count' => 3,
                'first_due_days'    => 30,
                'interval_days'     => 30,
                'sort_order'        => 9,
            ],
            [
                'name'              => '30/60/90/120 dias',
                'type'              => 'parcelado',
                'installment_count' => 4,
                'first_due_days'    => 30,
                'interval_days'     => 30,
                'sort_order'        => 10,
            ],
            [
                'name'              => 'Parcelado 2x',
                'type'              => 'parcelado',
                'installment_count' => 2,
                'first_due_days'    => 30,
                'interval_days'     => 30,
                'sort_order'        => 11,
            ],
            [
                'name'              => 'Parcelado 3x',
                'type'              => 'parcelado',
                'installment_count' => 3,
                'first_due_days'    => 30,
                'interval_days'     => 30,
                'sort_order'        => 12,
            ],
            [
                'name'              => 'Parcelado 4x',
                'type'              => 'parcelado',
                'installment_count' => 4,
                'first_due_days'    => 30,
                'interval_days'     => 30,
                'sort_order'        => 13,
            ],
            [
                'name'              => 'Parcelado 5x',
                'type'              => 'parcelado',
                'installment_count' => 5,
                'first_due_days'    => 30,
                'interval_days'     => 30,
                'sort_order'        => 14,
            ],
            [
                'name'              => 'Parcelado 6x',
                'type'              => 'parcelado',
                'installment_count' => 6,
                'first_due_days'    => 30,
                'interval_days'     => 30,
                'sort_order'        => 15,
            ],
            [
                'name'              => 'Parcelado 10x',
                'type'              => 'parcelado',
                'installment_count' => 10,
                'first_due_days'    => 30,
                'interval_days'     => 30,
                'sort_order'        => 16,
            ],
            [
                'name'              => 'Parcelado 12x',
                'type'              => 'parcelado',
                'installment_count' => 12,
                'first_due_days'    => 30,
                'interval_days'     => 30,
                'sort_order'        => 17,
            ],
            [
                'name'              => 'Entrada + 30/60',
                'type'              => 'entrada_parcelas',
                'installment_count' => 3,
                'first_due_days'    => 0,
                'interval_days'     => 30,
                'has_entry'         => true,
                'entry_percent'     => 33.33,
                'sort_order'        => 18,
            ],
            [
                'name'              => 'Parcelamento variável',
                'type'              => 'variavel',
                'installment_count' => 0,
                'first_due_days'    => 30,
                'interval_days'     => 30,
                'is_variable'       => true,
                'sort_order'        => 19,
            ],
        ];

        $now  = now();
        $rows = array_map(fn ($d) => array_merge([
            'discount_type'  => 'none',
            'discount_value' => 0,
            'interest_type'  => 'none',
            'interest_value' => 0,
            'fine_percent'   => 0,
            'fine_after_days'=> 0,
            'grace_days'     => 0,
            'has_entry'      => false,
            'entry_percent'  => 0,
            'is_variable'    => false,
        ], $d, [
            'uuid'       => (string) Str::uuid(),
            'tenant_id'  => null,
            'is_active'  => true,
            'is_system'  => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $defaults);

        PaymentCondition::insert($rows);
    }

    private function updateExistingConditions(): void
    {
        $updates = [
            'À vista'             => ['type' => 'a_vista',         'installment_count' => 1,  'first_due_days' => 0,  'interval_days' => 30],
            '7 dias'              => ['type' => 'a_vista',         'installment_count' => 1,  'first_due_days' => 7,  'interval_days' => 30],
            '10 dias'             => ['type' => 'a_vista',         'installment_count' => 1,  'first_due_days' => 10, 'interval_days' => 30],
            '14 dias'             => ['type' => 'a_vista',         'installment_count' => 1,  'first_due_days' => 14, 'interval_days' => 30],
            '21 dias'             => ['type' => 'a_vista',         'installment_count' => 1,  'first_due_days' => 21, 'interval_days' => 30],
            '28 dias'             => ['type' => 'a_vista',         'installment_count' => 1,  'first_due_days' => 28, 'interval_days' => 30],
            '30 dias'             => ['type' => 'a_vista',         'installment_count' => 1,  'first_due_days' => 30, 'interval_days' => 30],
            '30/60 dias'          => ['type' => 'parcelado',       'installment_count' => 2,  'first_due_days' => 30, 'interval_days' => 30],
            '30/60/90 dias'       => ['type' => 'parcelado',       'installment_count' => 3,  'first_due_days' => 30, 'interval_days' => 30],
            '30/60/90/120 dias'   => ['type' => 'parcelado',       'installment_count' => 4,  'first_due_days' => 30, 'interval_days' => 30],
            'Parcelado 2x'        => ['type' => 'parcelado',       'installment_count' => 2,  'first_due_days' => 30, 'interval_days' => 30],
            'Parcelado 3x'        => ['type' => 'parcelado',       'installment_count' => 3,  'first_due_days' => 30, 'interval_days' => 30],
            'Parcelado 4x'        => ['type' => 'parcelado',       'installment_count' => 4,  'first_due_days' => 30, 'interval_days' => 30],
            'Parcelado 5x'        => ['type' => 'parcelado',       'installment_count' => 5,  'first_due_days' => 30, 'interval_days' => 30],
            'Parcelado 6x'        => ['type' => 'parcelado',       'installment_count' => 6,  'first_due_days' => 30, 'interval_days' => 30],
            'Parcelado 10x'       => ['type' => 'parcelado',       'installment_count' => 10, 'first_due_days' => 30, 'interval_days' => 30],
            'Parcelado 12x'       => ['type' => 'parcelado',       'installment_count' => 12, 'first_due_days' => 30, 'interval_days' => 30],
        ];

        foreach ($updates as $name => $fields) {
            PaymentCondition::whereNull('tenant_id')
                ->where('name', $name)
                ->update($fields);
        }
    }
}
