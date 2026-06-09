<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Modo do Stub Fiscal
    |--------------------------------------------------------------------------
    |
    | Controla o comportamento do StubFiscalProvider em desenvolvimento/testes.
    |
    | 'authorized'  → simula autorização imediata (default)
    | 'rejected'    → simula rejeição SEFAZ (para testes de rejeição)
    | 'contingency' → simula SEFAZ fora do ar (para testes de contingência)
    |
    */
    'stub_mode' => env('FISCAL_STUB_MODE', 'authorized'),

    /*
    |--------------------------------------------------------------------------
    | Provedor padrão
    |--------------------------------------------------------------------------
    |
    | Provedor usado quando nenhum é especificado na emissão manual.
    | Em produção, sobrescrever com o provedor real.
    |
    */
    'default_provider' => env('FISCAL_DEFAULT_PROVIDER', 'stub'),

    /*
    |--------------------------------------------------------------------------
    | Fila de processamento fiscal
    |--------------------------------------------------------------------------
    */
    'queue' => env('FISCAL_QUEUE', 'fiscal'),

    /*
    |--------------------------------------------------------------------------
    | Tentativas de reenvio automático
    |--------------------------------------------------------------------------
    */
    'max_retries' => (int) env('FISCAL_MAX_RETRIES', 3),
];
