<?php

declare(strict_types=1);

namespace App\Shared\Models;

use App\Shared\Traits\Auditable;
use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo base para todas as entidades de domínio.
 *
 * Consolida os comportamentos obrigatórios em um único ponto de extensão,
 * garantindo que nenhuma entidade seja criada sem UUID, tenant_id e soft delete.
 */
abstract class BaseModel extends Model
{
    use Auditable;
    use BelongsToTenant;
    use HasUuid;
    use SoftDeletes;

    protected $primaryKey = 'uuid';

    /**
     * Casts padrão estendidos pelas subclasses via parent::casts().
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
