<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CustomerAsset extends BaseModel
{
    use SoftDeletes;

    protected $table = 'customer_assets';

    public const TYPES = ['real_estate', 'vehicle', 'other'];

    protected $fillable = [
        'tenant_id', 'customer_id', 'asset_type', 'description',
        'address', 'estimated_value_cents', 'notes',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'estimated_value_cents' => 'integer',
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'uuid');
    }

    public static function totalAssetsValue(string $customerId): int
    {
        return (int) self::where('customer_id', $customerId)->sum('estimated_value_cents');
    }
}
