<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Models\Category;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seed de categorias para ERP de Material de Construção.
 *
 * Cria 10 categorias pai e 40 subcategorias (4 por pai).
 * Idempotente: ignora categorias cujo slug já existe no tenant.
 * Requer TenantContext configurado pelo chamador (DatabaseSeeder).
 */
final class ConstructionCategorySeeder extends Seeder
{
    public function __construct(
        private readonly GenerateInternalCodeAction $generateCode,
    ) {}

    public function run(): void
    {
        $this->ensureTenantContext();

        foreach ($this->taxonomy() as $order => $parentData) {
            $parent = $this->upsert(
                name:        $parentData['name'],
                parentId:    null,
                sortOrder:   ($order + 1) * 10,
                description: $parentData['description'] ?? null,
            );

            foreach ($parentData['children'] as $childOrder => $childData) {
                $this->upsert(
                    name:        $childData['name'],
                    parentId:    $parent->uuid,
                    sortOrder:   ($childOrder + 1) * 10,
                    description: $childData['description'] ?? null,
                );
            }
        }

        $this->command?->info('ConstructionCategorySeeder: 10 categorias pai e 40 subcategorias criadas.');
    }

    private function ensureTenantContext(): void
    {
        if (TenantContext::isSet()) {
            return;
        }

        // Execução standalone: usa o tenant demo ou o primeiro disponível
        $tenant = \App\Core\Tenancy\Models\Tenant::where('slug', 'loja-demo')->first()
            ?? \App\Core\Tenancy\Models\Tenant::first();

        if ($tenant === null) {
            throw new \RuntimeException(
                'ConstructionCategorySeeder requer um tenant. Execute DatabaseSeeder primeiro ou passe um TenantContext.'
            );
        }

        TenantContext::set($tenant->uuid);
        $this->command?->info("ConstructionCategorySeeder: usando tenant '{$tenant->name}'.");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function upsert(string $name, ?string $parentId, int $sortOrder, ?string $description): Category
    {
        $slug = Str::slug($name);

        $existing = Category::where('slug', $slug)->first();
        if ($existing !== null) {
            return $existing;
        }

        $code = $this->generateCode->execute(
            tenantId: TenantContext::getIdOrFail(),
            entity:   SequenceEntityEnum::Category,
        );

        return Category::create([
            'code'        => $code,
            'name'        => $name,
            'slug'        => $slug,
            'description' => $description,
            'parent_id'   => $parentId,
            'sort_order'  => $sortOrder,
            'is_active'   => true,
        ]);
    }

    // ── Taxonomia ─────────────────────────────────────────────────────────────

    /** @return array<int, array{name: string, description?: string, children: array<int, array{name: string, description?: string}>}> */
    private function taxonomy(): array
    {
        return [
            [
                'name'        => 'Cimento e Argamassa',
                'description' => 'Cimentos, argamassas, cal e produtos para alvenaria estrutural.',
                'children'    => [
                    ['name' => 'Cimento Portland',          'description' => 'CP-II, CP-III, CP-IV e CP-V para uso geral e estrutural.'],
                    ['name' => 'Argamassa de Assentamento', 'description' => 'Argamassas para assentar tijolos, blocos e elementos de alvenaria.'],
                    ['name' => 'Argamassa de Revestimento', 'description' => 'Argamassas industriais e traçadas para reboco e emboço.'],
                    ['name' => 'Cal e Gesso',               'description' => 'Cal hidratada, gesso em pó e pasta para acabamento interno.'],
                ],
            ],
            [
                'name'        => 'Pisos e Revestimentos',
                'description' => 'Porcelanato, cerâmica, pedras naturais e pisos laminados.',
                'children'    => [
                    ['name' => 'Porcelanato',              'description' => 'Porcelanato técnico e esmaltado em diversas dimensões e acabamentos.'],
                    ['name' => 'Cerâmica',                 'description' => 'Cerâmica para piso e parede, PEI 1 a 5, formatos variados.'],
                    ['name' => 'Pedras Naturais',          'description' => 'Granito, mármore, ardósia e pedras naturais para piso e revestimento.'],
                    ['name' => 'Piso Vinílico e Laminado', 'description' => 'Pisos laminados, vinílico clicado e manta vinílica para ambientes internos.'],
                ],
            ],
            [
                'name'        => 'Hidráulica',
                'description' => 'Tubos, conexões, metais sanitários e sistemas de abastecimento de água.',
                'children'    => [
                    ['name' => 'Tubos e Conexões PVC',         'description' => 'Tubos soldáveis, roscáveis, desgotamento e conexões em PVC.'],
                    ['name' => 'Metais e Louças Sanitárias',   'description' => 'Torneiras, chuveiros, vasos sanitários, cubas e pias.'],
                    ['name' => 'Caixas d\'Água e Cisternas',   'description' => 'Reservatórios polietileno, fibra e cisternas de concreto.'],
                    ['name' => 'Bombas e Pressurizadores',     'description' => 'Bombas d\'água, pressurizadores e sistemas de recalque.'],
                ],
            ],
            [
                'name'        => 'Elétrica',
                'description' => 'Cabos, fios, quadros elétricos, tomadas, interruptores e iluminação.',
                'children'    => [
                    ['name' => 'Cabos e Fios',              'description' => 'Cabos flexíveis, rígidos e cordões para instalações elétricas.'],
                    ['name' => 'Disjuntores e Quadros',     'description' => 'Disjuntores, DR, DPS e quadros de distribuição residencial e industrial.'],
                    ['name' => 'Tomadas e Interruptores',   'description' => 'Tomadas NBR, interruptores simples, dimmer e tomadas USB.'],
                    ['name' => 'Iluminação',                'description' => 'Lâmpadas LED, luminárias, spots e arandelas para uso geral.'],
                ],
            ],
            [
                'name'        => 'Ferragens',
                'description' => 'Parafusos, pregos, dobradiças, fechaduras e perfis metálicos.',
                'children'    => [
                    ['name' => 'Parafusos e Buchas',       'description' => 'Parafusos Phillips, sextavado, chipboard, buchas de nylon e metálicas.'],
                    ['name' => 'Pregos e Arames',          'description' => 'Pregos de aço, arames recozidos e galvanizados para construção civil.'],
                    ['name' => 'Dobradiças e Fechaduras',  'description' => 'Dobradiças para portas e janelas, fechaduras, cadeados e trincos.'],
                    ['name' => 'Perfis e Cantoneiras',     'description' => 'Perfis U, L, T, cantoneiras de aço e alumínio para estruturas e acabamentos.'],
                ],
            ],
            [
                'name'        => 'Ferramentas',
                'description' => 'Ferramentas manuais, elétricas, EPIs e instrumentos de medição.',
                'children'    => [
                    ['name' => 'Ferramentas Manuais',   'description' => 'Martelos, chaves, alicates, serras manuais e ferramentas de corte.'],
                    ['name' => 'Ferramentas Elétricas', 'description' => 'Furadeiras, parafusadeiras, lixadeiras, serras circulares e moto-serras.'],
                    ['name' => 'EPIs e Segurança',      'description' => 'Capacetes, luvas, óculos de proteção, botas e cintos de segurança.'],
                    ['name' => 'Medição e Nível',       'description' => 'Trenas, níveis a bolha, laser, esquadros e paquímetros.'],
                ],
            ],
            [
                'name'        => 'Tintas',
                'description' => 'Tintas acrílicas, esmaltes, vernizes, impermeabilizantes e complementos.',
                'children'    => [
                    ['name' => 'Tinta Acrílica',          'description' => 'Tintas acrílicas premium, standard e econômicas para paredes internas e externas.'],
                    ['name' => 'Tinta Esmalte e Verniz',  'description' => 'Esmaltes sintéticos e à base d\'água, vernizes e lacas para madeira e metal.'],
                    ['name' => 'Impermeabilizante',       'description' => 'Impermeabilizantes acrílicos, poliuretano e cimentícios para lajes e paredes.'],
                    ['name' => 'Massa Corrida e Selador', 'description' => 'Massa corrida PVA e acrílica, seladores e fundos preparadores de parede.'],
                ],
            ],
            [
                'name'        => 'Madeira',
                'description' => 'Tábuas, vigas, MDF, compensado, portas, janelas e decks.',
                'children'    => [
                    ['name' => 'Tábuas e Vigas',    'description' => 'Madeira serrada em tábuas, caibros, vigas e ripas para estruturas e forros.'],
                    ['name' => 'MDF e Compensado',  'description' => 'Chapas de MDF, MDP, compensado naval e OSB para marcenaria e construção.'],
                    ['name' => 'Portas e Janelas',  'description' => 'Portas de madeira maciça e oca, janelas de madeira e esquadrias.'],
                    ['name' => 'Decks e Pergolados','description' => 'Decks em madeira natural e plástica, pergolados e estruturas para área externa.'],
                ],
            ],
            [
                'name'        => 'Cobertura',
                'description' => 'Telhas, calhas, rufos, impermeabilizantes para telhado e estruturas metálicas.',
                'children'    => [
                    ['name' => 'Telhas',                       'description' => 'Telhas de fibrocimento, cerâmica, metálica, shingle e polipropileno.'],
                    ['name' => 'Calhas e Rufos',               'description' => 'Calhas PVC, alumínio e zinco, rufos e cumeeiras para telhados.'],
                    ['name' => 'Impermeabilizante para Telhado','description' => 'Mantas asfálticas, telas de poliéster e impermeabilizantes elastoméricos.'],
                    ['name' => 'Estrutura Metálica',           'description' => 'Tesouras, terças, perfis galvanizados e estruturas para cobertura industrial.'],
                ],
            ],
            [
                'name'        => 'Acabamentos',
                'description' => 'Rodapés, soleiras, molduras, rejuntes, silicone e vedações.',
                'children'    => [
                    ['name' => 'Rodapés e Soleiras',  'description' => 'Rodapés de MDF, PVC, madeira, mármore e granito; soleiras e peitoris.'],
                    ['name' => 'Molduras e Perfis',   'description' => 'Perfis de transição, molduras de gesso e isopor, sancas e cantoneiras decorativas.'],
                    ['name' => 'Silicone e Vedação',  'description' => 'Silicones neutro e acetato, vedantes, fitas e espumas expansivas.'],
                    ['name' => 'Rejuntes e Caulins',  'description' => 'Rejuntes epóxi e cimentício, caulins e fitas de junta para pisos e azulejos.'],
                ],
            ],
        ];
    }
}
