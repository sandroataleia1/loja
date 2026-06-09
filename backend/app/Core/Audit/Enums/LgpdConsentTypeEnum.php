<?php

declare(strict_types=1);

namespace App\Core\Audit\Enums;

/**
 * Tipos de consentimento LGPD (Art. 7º e Art. 11 da Lei 13.709/2018).
 *
 * Fundação para gestão de consentimentos. Workflow completo não implementado.
 * Modelos com dados pessoais devem referenciar estes tipos em seus metadados.
 */
enum LgpdConsentTypeEnum: string
{
    case Marketing     = 'marketing';
    case Analytics     = 'analytics';
    case DataSharing   = 'data_sharing';
    case ThirdParty    = 'third_party';
    case Profiling     = 'profiling';
    case Communication = 'communication';

    public function label(): string
    {
        return match ($this) {
            self::Marketing     => 'Marketing e promoções',
            self::Analytics     => 'Análise de comportamento',
            self::DataSharing   => 'Compartilhamento de dados',
            self::ThirdParty    => 'Parceiros e terceiros',
            self::Profiling     => 'Personalização de perfil',
            self::Communication => 'Comunicações operacionais',
        };
    }

    public function isOptional(): bool
    {
        return in_array($this, [self::Marketing, self::Analytics, self::ThirdParty, self::Profiling], true);
    }
}
