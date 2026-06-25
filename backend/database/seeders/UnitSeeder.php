<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Catalog\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seed das unidades de medida globais da plataforma (tenant_id = NULL).
 *
 * Idempotente: usa upsert por `code` onde tenant_id IS NULL.
 * Cobre todas as unidades do UnitOfMeasureEnum + unidades extras para
 * material de construção (PC, RL, GL, T).
 */
final class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = $this->globalUnits();

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['code' => $unit['code'], 'tenant_id' => null],
                array_merge($unit, ['uuid' => Unit::where('code', $unit['code'])->whereNull('tenant_id')->value('uuid') ?? Str::uuid()->toString()]),
            );
        }

        $this->command?->info(sprintf('UnitSeeder: %d unidades globais inseridas/atualizadas.', count($units)));
    }

    /** @return array<int, array{code:string, name:string, symbol:string, decimal_places:int, unit_group:string, is_active:bool}> */
    private function globalUnits(): array
    {
        return [
            // ── Quantidade (sem decimal) ──────────────────────────────────────
            ['code' => 'UN',  'name' => 'Unidade',        'symbol' => 'un',  'decimal_places' => 0, 'unit_group' => 'quantity', 'is_active' => true],
            ['code' => 'CX',  'name' => 'Caixa',          'symbol' => 'cx',  'decimal_places' => 0, 'unit_group' => 'quantity', 'is_active' => true],
            ['code' => 'SC',  'name' => 'Saco',            'symbol' => 'sc',  'decimal_places' => 0, 'unit_group' => 'quantity', 'is_active' => true],
            ['code' => 'PC',  'name' => 'Peça',            'symbol' => 'pc',  'decimal_places' => 0, 'unit_group' => 'quantity', 'is_active' => true],
            ['code' => 'RL',  'name' => 'Rolo',            'symbol' => 'rl',  'decimal_places' => 0, 'unit_group' => 'quantity', 'is_active' => true],
            ['code' => 'PR',  'name' => 'Par',             'symbol' => 'pr',  'decimal_places' => 0, 'unit_group' => 'quantity', 'is_active' => true],
            ['code' => 'DZ',  'name' => 'Dúzia',           'symbol' => 'dz',  'decimal_places' => 0, 'unit_group' => 'quantity', 'is_active' => true],
            ['code' => 'CT',  'name' => 'Centena',         'symbol' => 'ct',  'decimal_places' => 0, 'unit_group' => 'quantity', 'is_active' => true],

            // ── Comprimento ───────────────────────────────────────────────────
            ['code' => 'M',   'name' => 'Metro linear',   'symbol' => 'm',   'decimal_places' => 3, 'unit_group' => 'length',   'is_active' => true],
            ['code' => 'CM',  'name' => 'Centímetro',     'symbol' => 'cm',  'decimal_places' => 2, 'unit_group' => 'length',   'is_active' => true],
            ['code' => 'MM',  'name' => 'Milímetro',      'symbol' => 'mm',  'decimal_places' => 2, 'unit_group' => 'length',   'is_active' => true],

            // ── Área ──────────────────────────────────────────────────────────
            ['code' => 'M2',  'name' => 'Metro quadrado', 'symbol' => 'm²',  'decimal_places' => 4, 'unit_group' => 'area',     'is_active' => true],

            // ── Volume ────────────────────────────────────────────────────────
            ['code' => 'M3',  'name' => 'Metro cúbico',   'symbol' => 'm³',  'decimal_places' => 4, 'unit_group' => 'volume',   'is_active' => true],

            // ── Peso / Massa ──────────────────────────────────────────────────
            ['code' => 'KG',  'name' => 'Quilograma',     'symbol' => 'kg',  'decimal_places' => 3, 'unit_group' => 'weight',   'is_active' => true],
            ['code' => 'G',   'name' => 'Grama',          'symbol' => 'g',   'decimal_places' => 2, 'unit_group' => 'weight',   'is_active' => true],
            ['code' => 'T',   'name' => 'Tonelada',       'symbol' => 't',   'decimal_places' => 3, 'unit_group' => 'weight',   'is_active' => true],

            // ── Capacidade / Volume líquido ───────────────────────────────────
            ['code' => 'LT',  'name' => 'Litro',          'symbol' => 'l',   'decimal_places' => 3, 'unit_group' => 'capacity', 'is_active' => true],
            ['code' => 'ML',  'name' => 'Mililitro',      'symbol' => 'ml',  'decimal_places' => 2, 'unit_group' => 'capacity', 'is_active' => true],
            ['code' => 'GL',  'name' => 'Galão',          'symbol' => 'gl',  'decimal_places' => 3, 'unit_group' => 'capacity', 'is_active' => true],
        ];
    }
}
