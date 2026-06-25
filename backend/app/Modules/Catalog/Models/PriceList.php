<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Enums\PriceListTypeEnum;
use App\Shared\Models\BaseModel;
use Database\Factories\PriceListFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PriceList extends BaseModel
{
    /** @use HasFactory<PriceListFactory> */
    use HasFactory;

    protected $table = 'price_lists';

    protected static function newFactory(): PriceListFactory
    {
        return PriceListFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'type',
        'currency',
        'max_discount_percent',
        'is_default',
        'is_active',
        'valid_from',
        'valid_to',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'type'                 => PriceListTypeEnum::class,
            'max_discount_percent' => 'decimal:2',
            'is_default'           => 'boolean',
            'is_active'            => 'boolean',
            'valid_from'           => 'date',
            'valid_to'             => 'date',
        ]);
    }

    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'price_list_id', 'uuid');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function scopeCurrentlyValid(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()->toDateString());
        })->where(function (Builder $q): void {
            $q->whereNull('valid_to')->orWhere('valid_to', '>=', now()->toDateString());
        });
    }
}
