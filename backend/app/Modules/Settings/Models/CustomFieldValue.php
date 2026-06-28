<?php

declare(strict_types=1);

namespace App\Modules\Settings\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Valor de um campo customizado para uma entidade específica.
 *
 * O valor é armazenado na coluna correta conforme `field_type` da definição.
 */
final class CustomFieldValue extends BaseModel
{
    protected $table = 'custom_field_values';

    protected $fillable = [
        'tenant_id',
        'field_definition_id',
        'entity_type',
        'entity_id',
        'value_text',
        'value_number',
        'value_date',
        'value_boolean',
        'value_json',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'value_number'  => 'float',
            'value_date'    => 'date',
            'value_boolean' => 'boolean',
            'value_json'    => 'array',
        ]);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(CustomFieldDefinition::class, 'field_definition_id', 'uuid');
    }

    /** Retorna o valor tipado conforme field_type da definição. */
    public function getValue(): mixed
    {
        return match ($this->definition?->field_type) {
            'number'               => $this->value_number,
            'date'                 => $this->value_date,
            'boolean'              => $this->value_boolean,
            'multiselect'          => $this->value_json,
            'select'               => $this->value_text,
            default                => $this->value_text,
        };
    }

    /** Persiste o valor na coluna tipada correta. */
    public function setValue(mixed $value): void
    {
        $type = $this->definition?->field_type ?? 'text';

        $this->value_text    = null;
        $this->value_number  = null;
        $this->value_date    = null;
        $this->value_boolean = null;
        $this->value_json    = null;

        match ($type) {
            'number'     => $this->value_number  = $value !== null ? (float) $value : null,
            'date'       => $this->value_date     = $value,
            'boolean'    => $this->value_boolean  = $value !== null ? (bool) $value : null,
            'multiselect' => $this->value_json    = is_array($value) ? $value : null,
            default      => $this->value_text     = $value !== null ? (string) $value : null,
        };
    }
}
