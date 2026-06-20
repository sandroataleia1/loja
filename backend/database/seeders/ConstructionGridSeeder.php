<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Models\Attribute;
use App\Modules\Catalog\Models\AttributeGroup;
use App\Modules\Catalog\Models\Grid;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed de grades de variação para ERP de Material de Construção.
 *
 * Cria 7 grades prontas para os principais tipos de produto:
 * Cabo Elétrico, Tubo PVC, Madeira Serrada, Tinta Volume, Tinta Cor,
 * Ferramentas Elétricas e Acabamento Porcelanato.
 *
 * Depende de ConstructionAttributeSeeder ter sido executado primeiro.
 */
final class ConstructionGridSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureTenantContext();

        foreach ($this->grids() as $gridData) {
            $this->upsertGrid($gridData);
        }

        $this->command?->info('ConstructionGridSeeder: 7 grades de variação criadas.');
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
                'ConstructionGridSeeder requer um tenant. Execute DatabaseSeeder primeiro ou configure TenantContext.'
            );
        }

        TenantContext::set($tenant->uuid);
        $this->command?->info("ConstructionGridSeeder: usando tenant '{$tenant->name}'.");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** @param array{name: string, group_slug: string, values: string[], description?: string} $gridData */
    private function upsertGrid(array $gridData): void
    {
        if (Grid::where('name', $gridData['name'])->exists()) {
            return;
        }

        $group = AttributeGroup::where('slug', $gridData['group_slug'])->first();

        if ($group === null) {
            $this->command?->warn(
                "Grupo '{$gridData['group_slug']}' não encontrado. Execute ConstructionAttributeSeeder primeiro."
            );
            return;
        }

        $attributes = Attribute::where('attribute_group_id', $group->uuid)
            ->whereIn('value', $gridData['values'])
            ->orderBy('sort_order')
            ->get();

        if ($attributes->isEmpty()) {
            $this->command?->warn("Nenhum atributo encontrado para grade '{$gridData['name']}'.");
            return;
        }

        $grid = Grid::create([
            'attribute_group_id' => $group->uuid,
            'name'               => $gridData['name'],
            'description'        => $gridData['description'] ?? null,
        ]);

        $pivot = $attributes->values()->map(fn (Attribute $a, int $i) => [
            'grid_id'      => $grid->uuid,
            'attribute_id' => $a->uuid,
            'sort_order'   => $i,
        ])->all();

        DB::table('catalog_grid_items')->insert($pivot);
    }

    // ── Grades ────────────────────────────────────────────────────────────────

    /**
     * @return array<int, array{name: string, group_slug: string, values: string[], description?: string}>
     */
    private function grids(): array
    {
        return [
            [
                'name'        => 'Grade Cabo Elétrico',
                'group_slug'  => 'bitola',
                'description' => 'Variações por bitola (mm²) para cabos flexíveis e rígidos.',
                'values'      => ['1,5mm2', '2,5mm2', '4,0mm2', '6,0mm2', '10,0mm2'],
            ],
            [
                'name'        => 'Grade Tubo PVC',
                'group_slug'  => 'diametro',
                'description' => 'Variações por diâmetro (mm) para tubos PVC soldável e roscável.',
                'values'      => ['20mm', '25mm', '32mm', '40mm', '50mm', '75mm', '100mm'],
            ],
            [
                'name'        => 'Grade Madeira Serrada',
                'group_slug'  => 'espessura',
                'description' => 'Variações por espessura (mm) para tábuas, MDF e compensado.',
                'values'      => ['15mm', '18mm', '20mm', '25mm'],
            ],
            [
                'name'        => 'Grade Tinta Volume',
                'group_slug'  => 'volume',
                'description' => 'Variações por volume para tintas, vernizes e impermeabilizantes.',
                'values'      => ['900ml', '3,6L', '18L'],
            ],
            [
                'name'        => 'Grade Cor Tinta',
                'group_slug'  => 'cor',
                'description' => 'Variações de cor para tintas acrílicas e esmaltes.',
                'values'      => ['branco', 'marfim', 'bege', 'cinza-claro', 'cinza-escuro'],
            ],
            [
                'name'        => 'Grade Ferramentas Elétricas',
                'group_slug'  => 'tensao',
                'description' => 'Variações por tensão para ferramentas e equipamentos elétricos.',
                'values'      => ['127V', '220V', 'bivolt'],
            ],
            [
                'name'        => 'Grade Acabamento Porcelanato',
                'group_slug'  => 'acabamento',
                'description' => 'Variações de acabamento superficial para porcelanato e cerâmica.',
                'values'      => ['polido', 'acetinado', 'rustico'],
            ],
        ];
    }
}
