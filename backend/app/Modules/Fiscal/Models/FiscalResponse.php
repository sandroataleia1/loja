<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Models;

use App\Modules\Fiscal\Enums\FiscalProviderEnum;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Resposta raw do provedor fiscal por tentativa de transmissão.
 *
 * Preserva o payload completo para diagnóstico e auditoria.
 * Cada tentativa (incluindo retries) gera um FiscalResponse separado.
 *
 * Append-only: sem updated_at.
 */
final class FiscalResponse extends Model
{
    use HasUuid;

    public const UPDATED_AT = null;

    protected $table      = 'fiscal_responses';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'fiscal_document_id',
        'provider',
        'status_code',
        'message',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'provider'     => FiscalProviderEnum::class,
            'status_code'  => 'integer',
            'raw_response' => 'array',
            'created_at'   => 'datetime',
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
        return $query->where('fiscal_document_id', $documentId)->latest('created_at');
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public function isSuccess(): bool
    {
        return $this->status_code !== null && $this->status_code < 400;
    }
}
