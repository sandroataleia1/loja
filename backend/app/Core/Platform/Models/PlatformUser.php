<?php

declare(strict_types=1);

namespace App\Core\Platform\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Usuário da plataforma (super_admin).
 *
 * NÃO possui tenant_id.
 * NÃO usa BelongsToTenant nem global scopes de tenant.
 * Autenticado via guard 'platform' com provedor 'platform_users'.
 * Nunca chamar withoutGlobalScope() para este modelo.
 */
final class PlatformUser extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasUuid;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'platform_users';

    protected $primaryKey = 'uuid';

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active'      => 'boolean',
            'last_login_at'  => 'datetime',
            'password'       => 'hashed',
        ];
    }
}
