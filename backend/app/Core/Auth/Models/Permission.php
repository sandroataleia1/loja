<?php

declare(strict_types=1);

namespace App\Core\Auth\Models;

use App\Core\Auth\Enums\PermissionEnum;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Permissão atômica do sistema.
 *
 * Permissões são globais (sem tenant_id) — seus slugs são contratos de código
 * definidos em PermissionEnum. Não criar permissões ad-hoc fora do seeder.
 */
final class Permission extends Model
{
    use HasUuid;

    protected $table      = 'permissions';
    protected $primaryKey = 'uuid';

    public $timestamps    = false;

    protected $fillable = ['name', 'slug', 'module', 'description', 'is_system'];

    protected function casts(): array
    {
        return [
            'is_system'  => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class, 'role_permissions', 'permission_id', 'role_id', 'uuid', 'uuid',
        );
    }

    public static function fromEnum(PermissionEnum $enum): self
    {
        return new self([
            'name'      => $enum->label(),
            'slug'      => $enum->value,
            'module'    => $enum->module(),
            'is_system' => true,
        ]);
    }
}
