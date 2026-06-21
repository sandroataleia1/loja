<?php

declare(strict_types=1);

namespace App\Core\Auth\Models;

use App\Core\Auth\Traits\HasTenantPermissions;
use App\Shared\Traits\Auditable;
use App\Shared\Traits\HasUuid;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

final class User extends Authenticatable
{
    use Auditable;
    use HasApiTokens;
    use HasTenantPermissions;
    use HasFactory;
    use HasUuid;
    use Notifiable;
    use SoftDeletes;

    /** @var array<string> */
    public array $auditExclude = ['password', 'remember_token'];

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected $table = 'users';

    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'is_active',
        'pin',
        'last_login_at',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TenantUser::class, 'user_id', 'uuid');
    }
}
