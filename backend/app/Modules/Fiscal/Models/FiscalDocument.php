<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Models;

use App\Modules\Fiscal\Enums\FiscalDocumentStatusEnum;
use App\Modules\Fiscal\Enums\FiscalDocumentTypeEnum;
use App\Modules\Fiscal\Enums\FiscalProviderEnum;
use App\Modules\Inventory\Models\Store;
use App\Modules\Sales\Models\Sale;
use App\Shared\Traits\Auditable;
use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Database\Factories\FiscalDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Documento fiscal eletrônico — imutável pós-autorização.
 *
 * Não usa SoftDeletes: documentos fiscais são registros legais.
 * Cancelamentos transitam para status = CANCELLED via CancelFiscalDocumentAction.
 */
final class FiscalDocument extends Model
{
    /** @use HasFactory<FiscalDocumentFactory> */
    use HasFactory;
    use Auditable;
    use BelongsToTenant;
    use HasUuid;

    protected $table      = 'fiscal_documents';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'sale_id',
        'type',
        'series',
        'number',
        'status',
        'provider',
        'access_key',
        'protocol',
        'xml_path',
        'pdf_path',
        'issued_at',
        'cancelled_at',
        'contingency_mode',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'type'             => FiscalDocumentTypeEnum::class,
            'status'           => FiscalDocumentStatusEnum::class,
            'provider'         => FiscalProviderEnum::class,
            'series'           => 'integer',
            'number'           => 'integer',
            'contingency_mode' => 'boolean',
            'issued_at'        => 'datetime',
            'cancelled_at'     => 'datetime',
            'created_at'       => 'datetime',
            'updated_at'       => 'datetime',
        ];
    }

    protected static function newFactory(): FiscalDocumentFactory
    {
        return FiscalDocumentFactory::new();
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public function isAuthorized(): bool
    {
        return $this->status === FiscalDocumentStatusEnum::Authorized;
    }

    public function isPending(): bool
    {
        return $this->status === FiscalDocumentStatusEnum::Pending;
    }

    public function isDraft(): bool
    {
        return $this->status === FiscalDocumentStatusEnum::Draft;
    }

    public function isInContingency(): bool
    {
        return $this->status === FiscalDocumentStatusEnum::Contingency;
    }

    public function hasXml(): bool
    {
        return $this->xml_path !== null;
    }

    public function canProcess(): bool
    {
        return $this->status->isProcessable();
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'uuid');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'uuid');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FiscalDocumentItem::class, 'fiscal_document_id', 'uuid');
    }

    public function events(): HasMany
    {
        return $this->hasMany(FiscalEvent::class, 'fiscal_document_id', 'uuid')
            ->orderBy('created_at');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(FiscalResponse::class, 'fiscal_document_id', 'uuid')
            ->latest('created_at');
    }

    public function latestResponse(): ?FiscalResponse
    {
        return $this->responses()->first();
    }
}
