<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Models;

use App\Shared\Enums\SequenceEntityEnum;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Registra a sequência numérica por entidade por tenant.
 * Não usa BelongsToTenant (precisa ser acessada sem filtro de tenant em jobs de plataforma).
 * Não usa SoftDeletes — registros de sequência são permanentes.
 */
final class TenantSequence extends Model
{
    use HasUuid;

    protected $table      = 'tenant_sequences';

    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'entity',
        'sub_entity_id',
        'current_value',
    ];

    protected function casts(): array
    {
        return [
            'entity'        => SequenceEntityEnum::class,
            'current_value' => 'integer',
        ];
    }
}
