<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Enums;

/**
 * Provedores fiscais suportados pelo adapter pattern.
 *
 * Para adicionar um novo provedor:
 *   1. Adicione o case aqui.
 *   2. Implemente FiscalProviderContract em Providers/.
 *   3. Registre no FiscalProviderResolver.
 *
 * Nenhum outro arquivo precisa ser alterado.
 */
enum FiscalProviderEnum: string
{
    case Stub        = 'stub';         // desenvolvimento e testes — nunca acessa SEFAZ
    case FocusNfe    = 'focus_nfe';    // Focus NFe (webmania/focus)
    case NuvemFiscal = 'nuvem_fiscal'; // Nuvem Fiscal
    case Enotas      = 'enotas';       // eNotas
    case Plugnotas   = 'plugnotas';    // PlugNotas
    case Custom      = 'custom';       // integração própria via webhook

    public function label(): string
    {
        return match ($this) {
            self::Stub        => 'Stub (Desenvolvimento)',
            self::FocusNfe    => 'Focus NFe',
            self::NuvemFiscal => 'Nuvem Fiscal',
            self::Enotas      => 'eNotas',
            self::Plugnotas   => 'PlugNotas',
            self::Custom      => 'Personalizado',
        };
    }

    /** Provedores que nunca conectam à SEFAZ real. */
    public function isSandbox(): bool
    {
        return $this === self::Stub;
    }

    /** Provedores que requerem configuração de certificado A1. */
    public function requiresCertificate(): bool
    {
        return ! $this->isSandbox();
    }
}
