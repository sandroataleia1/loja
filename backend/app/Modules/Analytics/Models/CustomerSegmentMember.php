<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Models;

use App\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot explícita de membership: cliente pertence a segmento.
 * Chave primária composta (customer_segment_id, customer_id) — sem UUID.
 */
final class CustomerSegmentMember extends Model
{
    use BelongsToTenant;

    public $incrementing = false;
    public $timestamps   = false;

    protected $table      = 'customer_segment_members';
    protected $primaryKey = 'customer_segment_id';

    protected $fillable = [
        'customer_segment_id',
        'customer_id',
        'tenant_id',
        'added_at',
    ];

    protected function casts(): array
    {
        return [
            'added_at' => 'datetime',
        ];
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function segment(): BelongsTo
    {
        return $this->belongsTo(CustomerSegment::class, 'customer_segment_id', 'uuid');
    }
}
