<?php

declare(strict_types=1);

namespace App\Core\Auth\Models;

use App\Modules\Inventory\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Allowlist de lojas para um TenantUser.
 *
 * Ausência de registros = acesso irrestrito a todas as lojas.
 * Presença de registros = apenas as lojas listadas são acessíveis.
 */
final class TenantUserStore extends Model
{
    protected $table      = 'tenant_user_stores';
    protected $primaryKey = null;

    public $incrementing = false;
    public $timestamps   = false;

    protected $fillable = ['tenant_user_id', 'store_id'];

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'tenant_user_id', 'uuid');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'uuid');
    }
}
