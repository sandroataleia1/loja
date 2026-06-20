<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Enums\AttributeTypeEnum;
use App\Modules\Catalog\Models\Attribute;
use App\Modules\Catalog\Models\AttributeGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seed de grupos de atributos técnicos para ERP de Material de Construção.
 *
 * Cria 10 grupos (Bitola, Diâmetro, Comprimento, Espessura, Volume, Potência,
 * Tensão, Material, Cor, Acabamento) com seus valores de referência.
 * Idempotente: ignora grupos e atributos cujo slug/value já existem.
 */
final class ConstructionAttributeSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureTenantContext();

        foreach ($this->taxonomy() as $order => $groupData) {
            $group = $this->upsertGroup(
                name:      $groupData['name'],
                type:      $groupData['type'],
                sortOrder: ($order + 1) * 10,
            );

            foreach ($groupData['values'] as $valueOrder => $valueData) {
                $this->upsertAttribute(
                    group:     $group,
                    value:     $valueData['value'],
                    label:     $valueData['label'],
                    colorHex:  $valueData['color_hex'] ?? null,
                    sortOrder: ($valueOrder + 1) * 10,
                );
            }
        }

        $this->command?->info('ConstructionAttributeSeeder: 10 grupos de atributos e valores criados.');
    }

    // ── Tenant ────────────────────────────────────────────────────────────────

    private function ensureTenantContext(): void
    {
        if (TenantContext::isSet()) {
            return;
        }

        $tenant = \App\Core\Tenancy\Models\Tenant::where('slug', 'loja-demo')->first()
            ?? \App\Core\Tenancy\Models\Tenant::first();

        if ($tenant === null) {
            throw new \RuntimeException(
                'ConstructionAttributeSeeder requer um tenant. Execute DatabaseSeeder primeiro ou configure TenantContext.'
            );
        }

        TenantContext::set($tenant->uuid);
        $this->command?->info("ConstructionAttributeSeeder: usando tenant '{$tenant->name}'.");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function upsertGroup(string $name, AttributeTypeEnum $type, int $sortOrder): AttributeGroup
    {
        $slug = Str::slug($name);

        $existing = AttributeGroup::where('slug', $slug)->first();
        if ($existing !== null) {
            return $existing;
        }

        return AttributeGroup::create([
            'name'       => $name,
            'slug'       => $slug,
            'type'       => $type,
            'sort_order' => $sortOrder,
        ]);
    }

    private function upsertAttribute(
        AttributeGroup $group,
        string $value,
        string $label,
        ?string $colorHex,
        int $sortOrder,
    ): Attribute {
        $existing = Attribute::where('attribute_group_id', $group->uuid)
            ->where('value', $value)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return Attribute::create([
            'attribute_group_id' => $group->uuid,
            'value'              => $value,
            'label'              => $label,
            'color_hex'          => $colorHex,
            'sort_order'         => $sortOrder,
        ]);
    }

    // ── Taxonomia ─────────────────────────────────────────────────────────────

    /**
     * @return array<int, array{
     *   name: string,
     *   type: AttributeTypeEnum,
     *   values: array<int, array{value: string, label: string, color_hex?: string}>
     * }>
     */
    private function taxonomy(): array
    {
        return [
            [
                'name'   => 'Bitola',
                'type'   => AttributeTypeEnum::Text,
                'values' => [
                    ['value' => '1,5mm2',  'label' => '1,5 mm²'],
                    ['value' => '2,5mm2',  'label' => '2,5 mm²'],
                    ['value' => '4,0mm2',  'label' => '4,0 mm²'],
                    ['value' => '6,0mm2',  'label' => '6,0 mm²'],
                    ['value' => '10,0mm2', 'label' => '10,0 mm²'],
                    ['value' => '16,0mm2', 'label' => '16,0 mm²'],
                ],
            ],
            [
                'name'   => 'Diâmetro',
                'type'   => AttributeTypeEnum::Text,
                'values' => [
                    ['value' => '20mm',  'label' => '20 mm'],
                    ['value' => '25mm',  'label' => '25 mm'],
                    ['value' => '32mm',  'label' => '32 mm'],
                    ['value' => '40mm',  'label' => '40 mm'],
                    ['value' => '50mm',  'label' => '50 mm'],
                    ['value' => '75mm',  'label' => '75 mm'],
                    ['value' => '100mm', 'label' => '100 mm'],
                ],
            ],
            [
                'name'   => 'Comprimento',
                'type'   => AttributeTypeEnum::Text,
                'values' => [
                    ['value' => '1m',  'label' => '1 m'],
                    ['value' => '2m',  'label' => '2 m'],
                    ['value' => '3m',  'label' => '3 m'],
                    ['value' => '6m',  'label' => '6 m'],
                    ['value' => '9m',  'label' => '9 m'],
                    ['value' => '12m', 'label' => '12 m'],
                ],
            ],
            [
                'name'   => 'Espessura',
                'type'   => AttributeTypeEnum::Text,
                'values' => [
                    ['value' => '6mm',  'label' => '6 mm'],
                    ['value' => '8mm',  'label' => '8 mm'],
                    ['value' => '10mm', 'label' => '10 mm'],
                    ['value' => '12mm', 'label' => '12 mm'],
                    ['value' => '15mm', 'label' => '15 mm'],
                    ['value' => '18mm', 'label' => '18 mm'],
                    ['value' => '20mm', 'label' => '20 mm'],
                    ['value' => '25mm', 'label' => '25 mm'],
                ],
            ],
            [
                'name'   => 'Volume',
                'type'   => AttributeTypeEnum::Text,
                'values' => [
                    ['value' => '900ml', 'label' => '900 ml'],
                    ['value' => '3,6L',  'label' => '3,6 L'],
                    ['value' => '18L',   'label' => '18 L'],
                    ['value' => '25L',   'label' => '25 L'],
                ],
            ],
            [
                'name'   => 'Potência',
                'type'   => AttributeTypeEnum::Text,
                'values' => [
                    ['value' => '600W',  'label' => '600 W'],
                    ['value' => '900W',  'label' => '900 W'],
                    ['value' => '1500W', 'label' => '1500 W'],
                    ['value' => '2000W', 'label' => '2000 W'],
                    ['value' => '3500W', 'label' => '3500 W'],
                ],
            ],
            [
                'name'   => 'Tensão',
                'type'   => AttributeTypeEnum::Text,
                'values' => [
                    ['value' => '127V',   'label' => '127 V'],
                    ['value' => '220V',   'label' => '220 V'],
                    ['value' => 'bivolt', 'label' => 'Bivolt'],
                ],
            ],
            [
                'name'   => 'Material',
                'type'   => AttributeTypeEnum::Text,
                'values' => [
                    ['value' => 'pvc',            'label' => 'PVC'],
                    ['value' => 'cobre',           'label' => 'Cobre'],
                    ['value' => 'aco-galvanizado', 'label' => 'Aço Galvanizado'],
                    ['value' => 'aluminio',        'label' => 'Alumínio'],
                    ['value' => 'madeira-macica',  'label' => 'Madeira Maciça'],
                    ['value' => 'fibra-de-vidro',  'label' => 'Fibra de Vidro'],
                ],
            ],
            [
                'name'   => 'Cor',
                'type'   => AttributeTypeEnum::Color,
                'values' => [
                    ['value' => 'branco',       'label' => 'Branco',       'color_hex' => '#FFFFFF'],
                    ['value' => 'marfim',       'label' => 'Marfim',       'color_hex' => '#FFFFF0'],
                    ['value' => 'bege',         'label' => 'Bege',         'color_hex' => '#F5F5DC'],
                    ['value' => 'cinza-claro',  'label' => 'Cinza Claro',  'color_hex' => '#D3D3D3'],
                    ['value' => 'cinza-escuro', 'label' => 'Cinza Escuro', 'color_hex' => '#696969'],
                    ['value' => 'preto',        'label' => 'Preto',        'color_hex' => '#000000'],
                    ['value' => 'azul',         'label' => 'Azul',         'color_hex' => '#4169E1'],
                    ['value' => 'verde',        'label' => 'Verde',        'color_hex' => '#228B22'],
                    ['value' => 'vermelho',     'label' => 'Vermelho',     'color_hex' => '#DC143C'],
                    ['value' => 'amarelo',      'label' => 'Amarelo',      'color_hex' => '#FFD700'],
                ],
            ],
            [
                'name'   => 'Acabamento',
                'type'   => AttributeTypeEnum::Text,
                'values' => [
                    ['value' => 'polido',    'label' => 'Polido'],
                    ['value' => 'acetinado', 'label' => 'Acetinado'],
                    ['value' => 'rustico',   'label' => 'Rústico'],
                    ['value' => 'natural',   'label' => 'Natural'],
                    ['value' => 'fosco',     'label' => 'Fosco'],
                    ['value' => 'brilhante', 'label' => 'Brilhante'],
                ],
            ],
        ];
    }
}
