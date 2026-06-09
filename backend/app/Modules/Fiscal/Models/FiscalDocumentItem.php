<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Models;

use App\Modules\Catalog\Models\Variant;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item do documento fiscal — snapshot imutável no momento da emissão.
 *
 * tax_snapshot armazena todos os campos tributários (NCM, CFOP, CST, alíquotas)
 * sem depender do estado atual da variante, garantindo rastreabilidade fiscal.
 */
final class FiscalDocumentItem extends Model
{
    use HasUuid;

    protected $table      = 'fiscal_document_items';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'fiscal_document_id',
        'product_variant_id',
        'quantity',
        'unit_price_cents',
        'total_cents',
        'tax_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'quantity'         => 'integer',
            'unit_price_cents' => 'integer',
            'total_cents'      => 'integer',
            'tax_snapshot'     => 'array',
            'created_at'       => 'datetime',
            'updated_at'       => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function document(): BelongsTo
    {
        return $this->belongsTo(FiscalDocument::class, 'fiscal_document_id', 'uuid');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'product_variant_id', 'uuid');
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public function unitPrice(): float
    {
        return $this->unit_price_cents / 100;
    }

    public function total(): float
    {
        return $this->total_cents / 100;
    }

    public function ncm(): ?string
    {
        return $this->tax_snapshot['ncm'] ?? null;
    }

    public function cfop(): ?string
    {
        return $this->tax_snapshot['cfop'] ?? null;
    }
}
