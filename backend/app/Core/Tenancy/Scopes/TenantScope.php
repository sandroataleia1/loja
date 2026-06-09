<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Scopes;

use App\Core\Tenancy\Exceptions\TenantContextMissingException;
use App\Core\Tenancy\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Aplica automaticamente o filtro por tenant_id em todas as queries.
 * Registrado via BelongsToTenant trait — nunca via controller ou action.
 */
final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = TenantContext::getId();

        // Falha fechada: sem contexto de tenant, jamais retornar dados de todos
        // os tenants. Fluxos cross-tenant legítimos usam withoutTenantScope().
        if ($tenantId === null) {
            throw TenantContextMissingException::for($model->getTable());
        }

        $builder->where($model->getTable().'.tenant_id', $tenantId);
    }

    public function extend(Builder $builder): void
    {
        $builder->macro('withoutTenantScope', function (Builder $builder): Builder {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('forTenant', function (Builder $builder, string $tenantId): Builder {
            return $builder
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId);
        });
    }
}
