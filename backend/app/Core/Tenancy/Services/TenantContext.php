<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Services;

final class TenantContext
{
    private static ?string $tenantId = null;

    public static function set(string $tenantId): void
    {
        self::$tenantId = $tenantId;
    }

    public static function getId(): ?string
    {
        return self::$tenantId;
    }

    public static function getIdOrFail(): string
    {
        if (self::$tenantId === null) {
            throw new \RuntimeException('Tenant context not set.');
        }

        return self::$tenantId;
    }

    public static function clear(): void
    {
        self::$tenantId = null;
    }

    /**
     * Executa o callback sob o tenant informado e RESTAURA o contexto anterior
     * ao final (em vez de limpá-lo).
     *
     * Seguro para listeners que podem rodar de forma síncrona dentro de uma
     * request já escopada (ex.: ShouldQueue com QUEUE_CONNECTION=sync): não
     * destrói o contexto do chamador.
     *
     * @template T
     * @param  callable():T  $callback
     * @return T
     */
    public static function runFor(string $tenantId, callable $callback): mixed
    {
        $previous = self::$tenantId;
        self::$tenantId = $tenantId;

        try {
            return $callback();
        } finally {
            self::$tenantId = $previous;
        }
    }

    public static function isSet(): bool
    {
        return self::$tenantId !== null;
    }
}
