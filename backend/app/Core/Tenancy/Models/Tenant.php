<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Models;

use App\Core\Tenancy\Events\TenantCreated;
use App\Shared\Enums\PlanEnum;
use App\Shared\Traits\HasUuid;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }

    protected $table = 'tenants';

    protected $primaryKey = 'uuid';

    protected $fillable = [
        'code',
        'trade_name',
        'legal_name',
        'document',
        'email',
        'phone',
        // legacy fields kept for backward compat
        'name',
        'slug',
        'plan',
        'is_active',
        'settings',
        'trial_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active'     => 'boolean',
            'settings'      => 'array',
            'trial_ends_at' => 'datetime',
            'plan'          => PlanEnum::class,
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $tenant): void {
            TenantCreated::dispatch($tenant);
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(\App\Core\Auth\Models\User::class, 'tenant_id', 'uuid');
    }
}
