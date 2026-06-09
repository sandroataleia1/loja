<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Enums;

/**
 * Origem da mercadoria conforme tabela SEFAZ (campo orig da NF-e).
 */
enum ProductOriginEnum: int
{
    case Nacional                          = 0;
    case EstrangeiraImportacaoDireta       = 1;
    case EstrangeiraAdquiridaInternamente  = 2;
    case NacionalConteudoImportadoAcima40  = 3;
    case NacionalProcessosBasicos          = 4;
    case EstrangeiraImportacaoDiretaSemST  = 5;
    case EstrangeiraInternaAdquiridaSemST  = 6;
    case NacionalConteudoImportadoAte40    = 7;
    case NacionalConteudoImportadoAte70    = 8;

    public function label(): string
    {
        return match ($this) {
            self::Nacional                         => '0 – Nacional',
            self::EstrangeiraImportacaoDireta      => '1 – Estrangeira (importação direta)',
            self::EstrangeiraAdquiridaInternamente => '2 – Estrangeira (adquirida no mercado interno)',
            self::NacionalConteudoImportadoAcima40 => '3 – Nacional, conteúdo importado > 40%',
            self::NacionalProcessosBasicos         => '4 – Nacional (processos produtivos básicos)',
            self::EstrangeiraImportacaoDiretaSemST => '5 – Estrangeira (importação direta, sem similar)',
            self::EstrangeiraInternaAdquiridaSemST => '6 – Estrangeira (interna, sem similar)',
            self::NacionalConteudoImportadoAte40   => '7 – Nacional, conteúdo importado ≤ 40%',
            self::NacionalConteudoImportadoAte70   => '8 – Nacional, conteúdo importado > 40% e ≤ 70%',
        };
    }
}
