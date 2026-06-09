<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Models;

use App\Modules\Fiscal\Enums\FiscalEventTypeEnum;
use App\Modules\Fiscal\Enums\FiscalProviderEnum;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trilha de auditoria de eventos de um documento fiscal — append-only.
 *
 * Cada ação sobre o documento (emissão, autorização, rejeição, cancelamento)
 * gera um FiscalEvent imutável. Permite reconstituir o histórico completo.
 *
 * Sem updated_at: eventos são imutáveis por design.
 */
final class FiscalEvent extends Model
{
    use HasUuid;

    public const UPDATED_AT = null;

    protected $table      = 'fiscal_events';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'fiscal_document_id',
        'event_type',
        'payload',
        'response',
        'provider',
    ];

    protected function casts(): array
    {
        return [
            'event_type'  => FiscalEventTypeEnum::class,
            'payload'     => 'array',
            'response'    => 'array',
            'provider'    => FiscalProviderEnum::class,
            'created_at'  => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function document(): BelongsTo
    {
        return $this->belongsTo(FiscalDocument::class, 'fiscal_document_id', 'uuid');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForDocument(Builder $query, string $documentId): Builder
    {
        return $query->where('fiscal_document_id', $documentId)->orderBy('created_at');
    }

    public function scopeOfType(Builder $query, FiscalEventTypeEnum $type): Builder
    {
        return $query->where('event_type', $type->value);
    }
}
