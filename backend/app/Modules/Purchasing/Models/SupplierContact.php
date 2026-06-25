<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SupplierContact extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'supplier_contacts';

    protected $fillable = [
        'supplier_id',
        'type',
        'value',
        'label',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'uuid');
    }
}
