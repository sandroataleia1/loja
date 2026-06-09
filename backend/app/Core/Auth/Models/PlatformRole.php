<?php

declare(strict_types=1);

namespace App\Core\Auth\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Roles globais da plataforma SaaS.
 *
 * Usadas por PlatformUser (super_admin, suporte).
 * NÃO pertencem a nenhum tenant — sem tenant_id.
 */
final class PlatformRole extends Model
{
    use HasUuid;

    protected $table      = 'platform_roles';
    protected $primaryKey = 'uuid';

    protected $fillable = ['name', 'slug'];
}
