<?php

declare(strict_types=1);

namespace App\Modules\Customers\Enums;

enum EducationLevelEnum: string
{
    case None          = 'none';
    case Elementary    = 'elementary';
    case HighSchool    = 'high_school';
    case Technical     = 'technical';
    case Undergraduate = 'undergraduate';
    case Graduate      = 'graduate';

    public function label(): string
    {
        return match ($this) {
            self::None          => 'Sem escolaridade',
            self::Elementary    => 'Ensino fundamental',
            self::HighSchool    => 'Ensino médio',
            self::Technical     => 'Técnico',
            self::Undergraduate => 'Superior',
            self::Graduate      => 'Pós-graduação',
        };
    }
}
