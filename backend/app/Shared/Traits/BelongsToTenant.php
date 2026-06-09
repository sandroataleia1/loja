<?php

declare(strict_types=1);

namespace App\Shared\Traits;

use App\Core\Tenancy\Scopes\TenantScope;
use App\Core\Tenancy\Services\TenantContext;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (Model $model): void {
            if (empty($model->tenant_id)) {
                $model->tenant_id = TenantContext::getIdOrFail();
            }
        });
    }
}
