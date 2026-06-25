<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Enums\UnitOfMeasureEnum;
use App\Modules\Catalog\Exceptions\InvalidUnitConversionException;
use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UnitConversion extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $table = 'unit_conversions';

    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'from_unit',
        'to_unit',
        'factor',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'factor'     => 'float',
            'is_active'  => 'boolean',
            'from_unit'  => UnitOfMeasureEnum::class,
            'to_unit'    => UnitOfMeasureEnum::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ── Validação de grupo ────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->assertCompatibleGroups();
        });

        static::updating(function (self $model): void {
            $model->assertCompatibleGroups();
        });
    }

    /**
     * Grupos de unidades derivados do UnitOfMeasureEnum.
     * Quando a migração para a tabela `units` estiver completa,
     * este mapa pode ser substituído por lookup na tabela.
     *
     * @return array<string, string>
     */
    private static function unitGroupMap(): array
    {
        return [
            'UN' => 'quantity',
            'CX' => 'quantity',
            'SC' => 'quantity',
            'M'  => 'length',
            'M2' => 'area',
            'M3' => 'volume',
            'KG' => 'weight',
            'LT' => 'capacity',
        ];
    }

    private function assertCompatibleGroups(): void
    {
        $fromValue = $this->from_unit instanceof UnitOfMeasureEnum
            ? $this->from_unit->value
            : (string) $this->from_unit;

        $toValue = $this->to_unit instanceof UnitOfMeasureEnum
            ? $this->to_unit->value
            : (string) $this->to_unit;

        $map = self::unitGroupMap();

        $fromGroup = $map[$fromValue] ?? null;
        $toGroup   = $map[$toValue] ?? null;

        // Se alguma das unidades não está no mapa (unidade customizada futura),
        // não bloqueia — a validação fica no domínio da tabela units.
        if ($fromGroup === null || $toGroup === null) {
            return;
        }

        if ($fromGroup !== $toGroup) {
            throw InvalidUnitConversionException::incompatibleGroups($fromValue, $toValue);
        }
    }

    // ── Relacionamentos ───────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'uuid');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'variant_id', 'uuid');
    }

    // ── Helpers de domínio ────────────────────────────────────────────────────

    /** 1 to_unit = 1/factor from_unit (conversão inversa). */
    public function inverseFactor(): float
    {
        return $this->factor != 0 ? 1 / $this->factor : 0;
    }

    public function isGlobal(): bool
    {
        return $this->product_id === null && $this->variant_id === null;
    }
}
