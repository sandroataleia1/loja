<?php

declare(strict_types=1);

namespace App\Modules\Sales\Models;

use App\Core\Auth\Models\User;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SaleCommission extends Model
{
    use HasUuid;

    protected $table      = 'sale_commissions';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'sale_id',
        'user_id',
        'percentage',
        'amount_cents',
    ];

    protected function casts(): array
    {
        return [
            'percentage'   => 'float',
            'amount_cents' => 'integer',
            'created_at'   => 'datetime',
            'updated_at'   => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'uuid');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'uuid');
    }
}
