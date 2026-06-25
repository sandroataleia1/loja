<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Unidade de medida configurável.
 *
 * tenant_id = NULL  → unidade global da plataforma (compartilhada por todos)
 * tenant_id = UUID  → unidade personalizada do tenant
 *
 * Não usa BelongsToTenant pois as unidades globais (NULL) ficariam invisíveis
 * ao TenantScope fail-closed. Use o escopo forTenant() para consultas seguras.
 */
final class Unit extends Model
{
    use HasUuid;

    protected $table = 'units';

    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'symbol',
        'decimal_places',
        'unit_group',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'is_active'      => 'boolean',
            'created_at'     => 'datetime',
            'updated_at'     => 'datetime',
        ];
    }

    // ── Escopos ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Unidades globais da plataforma (tenant_id IS NULL). */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('tenant_id');
    }

    /**
     * Unidades visíveis para o tenant: as globais + as próprias do tenant.
     * É o escopo correto para listagens em contexto de tenant.
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where(function (Builder $q) use ($tenantId): void {
            $q->whereNull('tenant_id')
              ->orWhere('tenant_id', $tenantId);
        });
    }

    // ── Relacionamentos ───────────────────────────────────────────────────────

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'unit_id', 'uuid');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class, 'unit_id', 'uuid');
    }

    // ── Helpers de domínio ────────────────────────────────────────────────────

    public function isGlobal(): bool
    {
        return $this->tenant_id === null;
    }

    public function formatQuantity(float $quantity): string
    {
        return number_format($quantity, $this->decimal_places, ',', '.');
    }
}
