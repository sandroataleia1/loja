<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Services;

use App\Modules\Fiscal\Contracts\FiscalProviderContract;
use App\Modules\Fiscal\Enums\FiscalProviderEnum;
use App\Modules\Fiscal\Providers\StubFiscalProvider;
use InvalidArgumentException;

/**
 * Resolve o adapter correto para o provedor fiscal informado.
 *
 * Para adicionar um novo provedor real:
 *   1. Crie a classe em Providers/ implementando FiscalProviderContract.
 *   2. Adicione o case aqui.
 *   Nenhum outro arquivo precisa ser alterado.
 */
final class FiscalProviderResolver
{
    public function resolve(FiscalProviderEnum $provider): FiscalProviderContract
    {
        return match ($provider) {
            FiscalProviderEnum::Stub        => app(StubFiscalProvider::class),
            FiscalProviderEnum::FocusNfe    => $this->notImplemented('Focus NFe'),
            FiscalProviderEnum::NuvemFiscal => $this->notImplemented('Nuvem Fiscal'),
            FiscalProviderEnum::Enotas      => $this->notImplemented('eNotas'),
            FiscalProviderEnum::Plugnotas   => $this->notImplemented('PlugNotas'),
            FiscalProviderEnum::Custom      => $this->notImplemented('Custom'),
        };
    }

    private function notImplemented(string $name): never
    {
        throw new InvalidArgumentException(
            "Provedor fiscal '{$name}' ainda não implementado. "
            . 'Crie a classe em App\\Modules\\Fiscal\\Providers e registre aqui.'
        );
    }
}
