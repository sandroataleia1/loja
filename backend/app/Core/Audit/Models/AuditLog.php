<?php

declare(strict_types=1);

namespace App\Core\Audit\Models;

use App\Core\Audit\Enums\AuditActionEnum;
use App\Core\Audit\Enums\AuditEntityTypeEnum;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class AuditLog extends Model
{
    use HasUuid;

    public const UPDATED_AT = null;

    protected $table      = 'audit_logs';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'user_id',
        'device_id',
        'correlation_id',
        'entity_type',
        'entity_uuid',
        'action',
        'old_values',
        'new_values',
        'metadata',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'entity_type' => AuditEntityTypeEnum::class,
            'action'      => AuditActionEnum::class,
            'old_values'  => 'array',
            'new_values'  => 'array',
            'metadata'    => 'array',
        ];
    }

    public static function forEntity(AuditEntityTypeEnum $type, string $uuid): Builder
    {
        return static::query()
            ->where('entity_type', $type)
            ->where('entity_uuid', $uuid)
            ->orderByDesc('created_at');
    }

    public static function forUser(string $userId, ?string $tenantId = null): Builder
    {
        return static::query()
            ->where('user_id', $userId)
            ->when($tenantId, fn (Builder $q) => $q->where('tenant_id', $tenantId))
            ->orderByDesc('created_at');
    }

    public static function forAction(AuditActionEnum $action, ?string $tenantId = null): Builder
    {
        return static::query()
            ->where('action', $action)
            ->when($tenantId, fn (Builder $q) => $q->where('tenant_id', $tenantId))
            ->orderByDesc('created_at');
    }
}
