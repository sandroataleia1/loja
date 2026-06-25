<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tabela global de NCMs — sem tenant_id, sem BelongsToTenant.
 * Gerenciada pela plataforma e compartilhada entre todos os tenants.
 */
final class NcmCode extends Model
{
    use HasUuid;

    protected $table = 'ncm_codes';

    protected $primaryKey = 'uuid';

    protected $fillable = [
        'code',
        'description',
        'unit_of_measure',
        'ipi_rate',
        'ncm_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'ipi_rate'   => 'decimal:4',
            'is_active'  => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'ncm_code_id', 'uuid');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class, 'ncm_code_id', 'uuid');
    }
}
