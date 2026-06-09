<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Models;

use App\Modules\Fiscal\Enums\TaxRegimeEnum;
use App\Shared\Models\BaseModel;
use Database\Factories\TaxProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class TaxProfile extends BaseModel
{
    /** @use HasFactory<TaxProfileFactory> */
    use HasFactory;

    protected $table = 'tax_profiles';

    protected $fillable = [
        'tenant_id',
        'name',
        'regime',
        'metadata',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'regime'   => TaxRegimeEnum::class,
            'metadata' => 'array',
        ]);
    }

    protected static function newFactory(): TaxProfileFactory
    {
        return TaxProfileFactory::new();
    }
}
