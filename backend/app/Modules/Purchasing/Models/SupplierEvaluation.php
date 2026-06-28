<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Models;

use App\Core\Auth\Models\User;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SupplierEvaluation extends BaseModel
{
    use SoftDeletes;

    protected $table = 'supplier_evaluations';

    protected $fillable = [
        'tenant_id', 'supplier_id', 'reference_date',
        'delivery_score', 'quality_score', 'price_score', 'service_score', 'overall_score',
        'avg_delivery_days', 'on_time_delivery_rate', 'return_rate',
        'notes', 'evaluated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'reference_date'       => 'date',
            'overall_score'        => 'decimal:2',
            'on_time_delivery_rate' => 'decimal:2',
            'return_rate'          => 'decimal:2',
        ]);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $evaluation): void {
            $evaluation->overall_score = round(
                ($evaluation->delivery_score + $evaluation->quality_score
                    + $evaluation->price_score + $evaluation->service_score) / 4,
                2,
            );
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'uuid');
    }

    public function evaluatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by', 'uuid');
    }
}
