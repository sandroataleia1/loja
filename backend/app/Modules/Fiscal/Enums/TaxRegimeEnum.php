<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Enums;

/**
 * Regime tributário conforme CRT da NF-e/NFC-e.
 */
enum TaxRegimeEnum: string
{
    case SimplesNacional      = 'simples_nacional';
    case LucroPresumido       = 'lucro_presumido';
    case LucroReal            = 'lucro_real';
    case Mei                  = 'mei';

    public function label(): string
    {
        return match ($this) {
            self::SimplesNacional => 'Simples Nacional',
            self::LucroPresumido  => 'Lucro Presumido',
            self::LucroReal       => 'Lucro Real',
            self::Mei             => 'MEI',
        };
    }

    /** CRT — Código de Regime Tributário conforme SEFAZ. */
    public function crt(): int
    {
        return match ($this) {
            self::Mei                  => 1, // Simples Nacional — MEI
            self::SimplesNacional      => 1,
            self::LucroPresumido       => 2,
            self::LucroReal            => 3,
        };
    }
}
