<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Enums;

enum FiscalDocumentStatusEnum: string
{
    case Draft       = 'draft';         // criado mas não enviado ainda
    case Pending     = 'pending';       // aguardando processamento na fila
    case Processing  = 'processing';    // sendo transmitido ao provedor/SEFAZ
    case Authorized  = 'authorized';    // autorizado pela SEFAZ
    case Rejected    = 'rejected';      // rejeitado pela SEFAZ (pode retentar)
    case Cancelled   = 'cancelled';     // cancelado após autorização
    case Contingency = 'contingency';   // emitido em contingência (transmissão pendente)
    case Error       = 'error';         // erro técnico não-SEFAZ

    public function label(): string
    {
        return match ($this) {
            self::Draft       => 'Rascunho',
            self::Pending     => 'Pendente',
            self::Processing  => 'Processando',
            self::Authorized  => 'Autorizado',
            self::Rejected    => 'Rejeitado',
            self::Cancelled   => 'Cancelado',
            self::Contingency => 'Contingência',
            self::Error       => 'Erro',
        };
    }

    public function canCancel(): bool
    {
        return $this === self::Authorized;
    }

    public function canRetry(): bool
    {
        return match ($this) {
            self::Rejected, self::Error, self::Contingency => true,
            default                                        => false,
        };
    }

    /** Status imutável — não pode mais ser processado. */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Authorized, self::Cancelled => true,
            default                           => false,
        };
    }

    /** Ainda pode ser enviado ao provedor (não está terminado ou cancelado). */
    public function isProcessable(): bool
    {
        return match ($this) {
            self::Draft, self::Pending, self::Error, self::Contingency => true,
            default                                                     => false,
        };
    }

    /** O documento impactou ou impacta o estoque/financeiro. */
    public function stockWasDecremented(): bool
    {
        return match ($this) {
            self::Authorized, self::Contingency => true,
            default                             => false,
        };
    }
}
