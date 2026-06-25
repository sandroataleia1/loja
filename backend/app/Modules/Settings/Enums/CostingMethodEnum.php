<?php

declare(strict_types=1);

namespace App\Modules\Settings\Enums;

enum CostingMethodEnum: string
{
    case Peps        = 'peps';
    case Ueps        = 'ueps';
    case AverageCost = 'average';

    public function label(): string
    {
        return match ($this) {
            self::Peps        => 'PEPS (Primeiro a entrar, primeiro a sair)',
            self::Ueps        => 'UEPS (Último a entrar, primeiro a sair)',
            self::AverageCost => 'Custo Médio Ponderado',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Peps        => 'Os primeiros itens comprados são os primeiros vendidos. Comum quando os produtos têm validade.',
            self::Ueps        => 'Os últimos itens comprados são os primeiros vendidos. Não permitido pelo fisco brasileiro para estoque fiscal.',
            self::AverageCost => 'O custo é calculado pela média ponderada de todas as entradas. Método mais comum no Brasil.',
        };
    }
}
