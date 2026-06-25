<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CostCenter extends BaseModel
{
    use SoftDeletes;

    protected $table = 'cost_centers';

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'code',
        'name',
        'type',
        'is_active',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_active' => 'boolean',
        ]);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'parent_id', 'uuid');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CostCenter::class, 'parent_id', 'uuid');
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }
}
