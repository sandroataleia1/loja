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
        $this->seedConditions();
        $this->seedMethods();
    }

    private function seedConditions(): void
    {
        if (PaymentCondition::whereNull('tenant_id')->exists()) {
            return;
        }

        $defaults = [
            ['name' => 'À vista',            'sort_order' => 1],
            ['name' => '7 dias',             'sort_order' => 2],
            ['name' => '10 dias',            'sort_order' => 3],
            ['name' => '14 dias',            'sort_order' => 4],
            ['name' => '21 dias',            'sort_order' => 5],
            ['name' => '28 dias',            'sort_order' => 6],
            ['name' => '30 dias',            'sort_order' => 7],
            ['name' => '30/60 dias',         'sort_order' => 8],
            ['name' => '30/60/90 dias',      'sort_order' => 9],
            ['name' => '30/60/90/120 dias',  'sort_order' => 10],
            ['name' => 'Parcelado 2x',       'sort_order' => 11],
            ['name' => 'Parcelado 3x',       'sort_order' => 12],
            ['name' => 'Parcelado 4x',       'sort_order' => 13],
            ['name' => 'Parcelado 5x',       'sort_order' => 14],
            ['name' => 'Parcelado 6x',       'sort_order' => 15],
            ['name' => 'Parcelado 10x',      'sort_order' => 16],
            ['name' => 'Parcelado 12x',      'sort_order' => 17],
        ];

        $now = now();
        $rows = array_map(fn ($d) => array_merge($d, [
            'uuid'       => (string) Str::uuid(),
            'tenant_id'  => null,
            'is_active'  => true,
            'is_system'  => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $defaults);

        PaymentCondition::insert($rows);
    }

    private function seedMethods(): void
    {
        if (PaymentMethod::whereNull('tenant_id')->exists()) {
            return;
        }

        $defaults = [
            ['name' => 'Dinheiro',              'type' => 'cash',          'sort_order' => 1],
            ['name' => 'PIX',                   'type' => 'pix',           'sort_order' => 2],
            ['name' => 'Cartão de Crédito',     'type' => 'credit_card',   'sort_order' => 3],
            ['name' => 'Cartão de Débito',      'type' => 'debit_card',    'sort_order' => 4],
            ['name' => 'Boleto Bancário',       'type' => 'boleto',        'sort_order' => 5],
            ['name' => 'Transferência Bancária','type' => 'bank_transfer', 'sort_order' => 6],
            ['name' => 'Cheque',                'type' => 'check',         'sort_order' => 7],
            ['name' => 'Crediário',             'type' => 'store_credit',  'sort_order' => 8],
            ['name' => 'Vale / Voucher',        'type' => 'voucher',       'sort_order' => 9],
        ];

        $now = now();
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
}
