<?php

declare(strict_types=1);

namespace App\Modules\Settings\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Defaults globais da plataforma para configurações de tenant.
 *
 * SEM tenant_id — global da plataforma.
 * Tenant pode sobrescrever (is_overridable = true).
 * Constants PHP existentes servem como fallback de emergência.
 */
final class PlatformSettingDefault extends Model
{
    use HasUuid;

    protected $table = 'platform_setting_defaults';

    protected $primaryKey = 'uuid';

    protected $fillable = [
        'section',
        'key',
        'value',
        'value_type',
        'description',
        'is_overridable',
    ];

    protected function casts(): array
    {
        return [
            'is_overridable' => 'boolean',
        ];
    }

    /** Retorna o valor já convertido para o tipo correto. */
    public function typedValue(): mixed
    {
        return match ($this->value_type) {
            'integer' => (int) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($this->value, true),
            default   => $this->value,
        };
    }
}
