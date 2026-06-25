<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Catalog\Models\NcmCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seed dos NCMs mais utilizados no setor de material de construção civil.
 *
 * Idempotente: usa upsert por `code` para poder ser re-executado sem duplicatas.
 * Fonte de referência: TIPI (Tabela de Incidência do IPI) vigente.
 */
final class NcmCodeSeeder extends Seeder
{
    public function run(): void
    {
        $records = $this->ncms();

        // Resolve UUIDs para novo registro; upsert atualiza description/ipi_rate
        $rows = array_map(fn (array $r) => array_merge(
            ['uuid' => Str::uuid()->toString(), 'created_at' => now(), 'updated_at' => now()],
            $r,
        ), $records);

        // upsert: se code já existe, atualiza apenas os campos mutáveis
        NcmCode::upsert(
            $rows,
            uniqueBy: ['code'],
            update:   ['description', 'unit_of_measure', 'ipi_rate', 'ncm_type', 'is_active', 'updated_at'],
        );

        $this->command?->info(sprintf('NcmCodeSeeder: %d NCMs inseridos/atualizados.', count($records)));
    }

    /** @return array<int, array{code:string, description:string, unit_of_measure:string, ipi_rate:float, ncm_type:string, is_active:bool}> */
    private function ncms(): array
    {
        return [
            // ── Cimento e argamassa ───────────────────────────────────────────
            ['code' => '2523.29.10', 'description' => 'Cimento Portland comum (CP-I, CP-II)',          'unit_of_measure' => 'KG', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '2523.90.19', 'description' => 'Outros cimentos Portland (CP-III, CP-IV, CP-V)','unit_of_measure' => 'KG', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '2522.10.00', 'description' => 'Cal viva',                                       'unit_of_measure' => 'KG', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '2522.20.00', 'description' => 'Cal extinta (hidratada)',                        'unit_of_measure' => 'KG', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '3214.10.00', 'description' => 'Argamassa, reboco e massas similares',           'unit_of_measure' => 'KG', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '3816.00.00', 'description' => 'Cimentos refratários, argamassas e betões refratários', 'unit_of_measure' => 'KG', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '6811.82.00', 'description' => 'Telhas e painéis de fibrocimento',               'unit_of_measure' => 'UN', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],

            // ── Tintas e vernizes ─────────────────────────────────────────────
            ['code' => '3209.10.00', 'description' => 'Tintas e vernizes à base de polímeros acrílicos ou vinílicos, dispersos em meio aquoso', 'unit_of_measure' => 'LT', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '3209.90.00', 'description' => 'Outras tintas e vernizes à base de polímeros sintéticos, dispersos em meio aquoso',      'unit_of_measure' => 'LT', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '3210.00.10', 'description' => 'Tintas de óleo para construção civil',           'unit_of_measure' => 'LT', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '3210.00.90', 'description' => 'Outras tintas e vernizes (esmalte sintético)',   'unit_of_measure' => 'LT', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '3208.10.00', 'description' => 'Tintas e vernizes à base de poliésteres',        'unit_of_measure' => 'LT', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '3208.20.00', 'description' => 'Tintas e vernizes à base de polímeros acrílicos (solvente orgânico)', 'unit_of_measure' => 'LT', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],

            // ── Tubos e conexões ──────────────────────────────────────────────
            ['code' => '3917.22.00', 'description' => 'Tubos rígidos de PVC',                           'unit_of_measure' => 'M',  'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '3917.23.00', 'description' => 'Tubos rígidos de outros plásticos',              'unit_of_measure' => 'M',  'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '3917.32.90', 'description' => 'Outras conexões de plástico (joelhos, tês, luvas)', 'unit_of_measure' => 'UN', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '3917.39.00', 'description' => 'Outras conexões de PVC para tubulações',          'unit_of_measure' => 'UN', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '7306.30.00', 'description' => 'Tubos de ferro ou aço, galvanizados',            'unit_of_measure' => 'M',  'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '7307.99.00', 'description' => 'Conexões para tubos de aço (cotovelos, uniões)', 'unit_of_measure' => 'UN', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],

            // ── Fios e cabos elétricos ────────────────────────────────────────
            ['code' => '8544.11.00', 'description' => 'Fios de cobre isolados para tensões até 80 V',   'unit_of_measure' => 'M',  'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '8544.19.00', 'description' => 'Outros fios isolados de cobre',                  'unit_of_measure' => 'M',  'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '8544.42.00', 'description' => 'Cabos coaxiais e outros condutores coaxiais',    'unit_of_measure' => 'M',  'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '8544.49.00', 'description' => 'Fios, cabos e outros condutores elétricos isolados', 'unit_of_measure' => 'M', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '8544.60.00', 'description' => 'Outros condutores elétricos para tensões superiores a 1000 V', 'unit_of_measure' => 'M', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],

            // ── Cerâmica, porcelanato e revestimentos ─────────────────────────
            ['code' => '6907.21.00', 'description' => 'Ladrilhos e placas cerâmicas esmaltadas (porcelanato esmaltado)', 'unit_of_measure' => 'M2', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '6907.22.00', 'description' => 'Ladrilhos e placas cerâmicas não esmaltadas (porcelanato técnico)', 'unit_of_measure' => 'M2', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '6907.23.00', 'description' => 'Ladrilhos e placas cerâmicas com absorção de água superior a 0,5%', 'unit_of_measure' => 'M2', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '6908.90.00', 'description' => 'Azulejos e ladrilhos cerâmicos esmaltados',      'unit_of_measure' => 'M2', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '6905.10.00', 'description' => 'Telhas de cerâmica',                             'unit_of_measure' => 'UN', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '6914.90.00', 'description' => 'Outras obras de cerâmica para construção',       'unit_of_measure' => 'UN', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],

            // ── Madeira e derivados ───────────────────────────────────────────
            ['code' => '4412.31.00', 'description' => 'Compensados de madeira com folhas de não-coníferas', 'unit_of_measure' => 'M2', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '4412.39.00', 'description' => 'Outros compensados de madeira',                  'unit_of_measure' => 'M2', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '4411.12.00', 'description' => 'MDF com espessura não superior a 5 mm',          'unit_of_measure' => 'M2', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '4411.14.00', 'description' => 'MDF com espessura superior a 9 mm',              'unit_of_measure' => 'M2', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '4418.10.00', 'description' => 'Janelas, portas-janelas e respectivos caixilhos de madeira', 'unit_of_measure' => 'UN', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '4418.20.00', 'description' => 'Portas e respectivos caixilhos e soleiras de madeira', 'unit_of_measure' => 'UN', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '4407.10.00', 'description' => 'Madeira serrada de coníferas (tábuas e pranchas)', 'unit_of_measure' => 'M3', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '4407.99.90', 'description' => 'Outras madeiras serradas (eucalipto, pinheiro)', 'unit_of_measure' => 'M3', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],

            // ── Metais e ferragens ────────────────────────────────────────────
            ['code' => '7308.90.10', 'description' => 'Estruturas metálicas e suas partes para construção', 'unit_of_measure' => 'KG', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '8302.41.00', 'description' => 'Fechaduras, dobradiças e ferragens para construção', 'unit_of_measure' => 'UN', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '8302.10.00', 'description' => 'Dobradiças para móveis e construção civil',      'unit_of_measure' => 'UN', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '7317.00.00', 'description' => 'Pregos, percevejos, escápulas, grampos ondulados e similares de ferro ou aço', 'unit_of_measure' => 'KG', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '7318.15.00', 'description' => 'Parafusos e porcas de ferro ou aço',            'unit_of_measure' => 'KG', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '7219.31.00', 'description' => 'Chapas de aço inoxidável laminadas a frio',     'unit_of_measure' => 'KG', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],

            // ── Vidros ────────────────────────────────────────────────────────
            ['code' => '7005.21.00', 'description' => 'Vidro float incolor',                            'unit_of_measure' => 'M2', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '7005.29.00', 'description' => 'Outros vidros float coloridos ou impressos',     'unit_of_measure' => 'M2', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '7007.19.00', 'description' => 'Vidros temperados para construção',              'unit_of_measure' => 'M2', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '7007.29.00', 'description' => 'Vidros laminados para construção',               'unit_of_measure' => 'M2', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],

            // ── Impermeabilizantes e químicos ─────────────────────────────────
            ['code' => '3824.99.89', 'description' => 'Impermeabilizantes, aditivos e compostos químicos para construção', 'unit_of_measure' => 'KG', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '3506.10.00', 'description' => 'Produtos adequados para uso como colas ou adesivos acondicionados para venda a retalho', 'unit_of_measure' => 'KG', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '3214.90.00', 'description' => 'Mástique de vidraceiro, cimentos de resina e calafetadores',       'unit_of_measure' => 'KG', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '3910.00.29', 'description' => 'Silicones em formas primárias (silicone para vedação)',             'unit_of_measure' => 'KG', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],

            // ── Gesso ─────────────────────────────────────────────────────────
            ['code' => '2520.20.00', 'description' => 'Gesso e anidrita',                               'unit_of_measure' => 'KG', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
            ['code' => '6809.11.00', 'description' => 'Painéis e placas de gesso acartonado (drywall)', 'unit_of_measure' => 'M2', 'ipi_rate' => 0.0, 'ncm_type' => 'product', 'is_active' => true],
        ];
    }
}
