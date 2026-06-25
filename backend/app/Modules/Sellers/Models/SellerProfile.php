<?php

declare(strict_types=1);

namespace App\Modules\Sellers\Models;

use App\Core\Auth\Models\User;
use App\Modules\Sellers\Enums\SellerTypeEnum;
use App\Shared\Models\BaseModel;
use Database\Factories\SellerProfileFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SellerProfile extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected static function newFactory(): Factory
    {
        return SellerProfileFactory::new();
    }

    protected $table = 'seller_profiles';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'code',
        'nickname',
        'seller_type',
        'commission_rate',
        'monthly_target',
        'supervisor_id',
        'region',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'seller_type'     => SellerTypeEnum::class,
            'commission_rate' => 'decimal:2',
            'monthly_target'  => 'decimal:2',
            'is_active'       => 'boolean',
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'uuid');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supervisor_id', 'uuid');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'supervisor_id', 'uuid');
    }
}
