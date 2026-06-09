<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Exceptions;

use RuntimeException;

/**
 * Lançada quando uma consulta a um modelo multi-tenant é construída sem que o
 * contexto de tenant esteja definido.
 *
 * Falha fechada: em vez de retornar dados de TODOS os tenants (vazamento), o
 * sistema interrompe a operação de forma ruidosa. Fluxos legítimos que precisam
 * ignorar o escopo (bootstrap, jobs cross-tenant, dashboards de plataforma)
 * devem usar explicitamente `withoutTenantScope()` ou `forTenant($id)`.
 */
final class TenantContextMissingException extends RuntimeException
{
    public static function for(string $table): self
    {
        return new self(
            "Consulta ao modelo multi-tenant '{$table}' sem contexto de tenant definido. "
            . 'Defina TenantContext::set() ou use withoutTenantScope()/forTenant() explicitamente.'
        );
    }
}
