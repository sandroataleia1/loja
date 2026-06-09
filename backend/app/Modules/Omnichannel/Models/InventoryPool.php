<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Models;

use App\Modules\Omnichannel\Enums\InventoryPoolTypeEnum;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;

final class InventoryPool extends BaseModel
{
    protected $table = 'inventory_pools';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'metadata',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'type'     => InventoryPoolTypeEnum::class,
            'metadata' => 'array',
        ]);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeOfType(Builder $query, InventoryPoolTypeEnum $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeShipFromStoreEligible(Builder $query): Builder
    {
        return $query->where('type', InventoryPoolTypeEnum::Store);
    }
}
