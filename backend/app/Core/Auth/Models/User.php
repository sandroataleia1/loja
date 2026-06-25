<?php

declare(strict_types=1);

namespace App\Core\Auth\Models;

use App\Core\Auth\Traits\HasTenantPermissions;
use App\Shared\Traits\Auditable;
use App\Shared\Traits\HasUuid;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
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
    public array $auditExclude = ['password', 'remember_token', 'pin'];

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected $table = 'users';

    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'name',
        'username',
        'email',
        'phone',
        'avatar',
        'preferences',
        'password',
        'is_active',
        'pin',
        'last_login_at',
        'email_verified_at',
        'failed_login_count',
        'locked_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'pin',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'last_login_at'      => 'datetime',
            'locked_until'       => 'datetime',
            'password'           => 'hashed',
            'is_active'          => 'boolean',
            'preferences'        => 'array',
            'failed_login_count' => 'integer',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TenantUser::class, 'user_id', 'uuid');
    }

    /**
     * PIN armazenado como SHA-256(APP_KEY + pin) — nunca em plaintext.
     * A leitura retorna o hash; a escrita converte automaticamente.
     */
    protected function pin(): Attribute
    {
        return Attribute::make(
            set: static fn (?string $value): ?string => $value !== null
                ? hash('sha256', config('app.key', '') . $value)
                : null,
        );
    }

    /** URL do avatar — usa o campo avatar ou gera um via UI Avatars. */
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->avatar) {
                    return $this->avatar;
                }

                $name = urlencode((string) $this->name);
                return "https://ui-avatars.com/api/?name={$name}&background=random&color=fff&size=128";
            },
        );
    }
}
