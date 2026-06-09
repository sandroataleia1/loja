<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Modules\Finance\Enums\FinancialCategoryTypeEnum;
use App\Shared\Models\BaseModel;
use Database\Factories\FinancialCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FinancialCategory extends BaseModel
{
    /** @use HasFactory<FinancialCategoryFactory> */
    use HasFactory;

    protected $table = 'financial_categories';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'is_active',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'type'      => FinancialCategoryTypeEnum::class,
            'is_active' => 'boolean',
        ]);
    }

    protected static function newFactory(): FinancialCategoryFactory
    {
        return FinancialCategoryFactory::new();
    }

    public function entries(): HasMany
    {
        return $this->hasMany(FinancialEntry::class, 'category_id', 'uuid');
    }
}
