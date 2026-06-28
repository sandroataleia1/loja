<?php

declare(strict_types=1);

namespace App\Modules\Settings\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Define um campo customizável por entidade de negócio.
 *
 * Entidades: customer, supplier, product, order, quote.
 * Tipos: text, number, date, boolean, select, multiselect, textarea.
 */
final class CustomFieldDefinition extends BaseModel
{
    use SoftDeletes;

    protected $table = 'custom_field_definitions';

    protected $fillable = [
        'tenant_id',
        'entity_type',
        'label',
        'field_key',
        'field_type',
        'options',
        'is_required',
        'is_searchable',
        'is_visible_in_list',
        'is_active',
        'sort_order',
        'placeholder',
        'help_text',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'options'            => 'array',
            'is_required'        => 'boolean',
            'is_searchable'      => 'boolean',
            'is_visible_in_list' => 'boolean',
            'is_active'          => 'boolean',
            'sort_order'         => 'integer',
        ]);
    }

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class, 'field_definition_id', 'uuid');
    }

    public static function fieldTypes(): array
    {
        return ['text', 'number', 'date', 'boolean', 'select', 'multiselect', 'textarea'];
    }

    public static function entityTypes(): array
    {
        return ['customer', 'supplier', 'product', 'order', 'quote'];
    }

    /** Faz cast do valor bruto para o tipo correto conforme field_type */
    public function castValue(mixed $raw): mixed
    {
        return match ($this->field_type) {
            'number'              => $raw !== null ? (float) $raw : null,
            'boolean'             => $raw !== null ? (bool) $raw : null,
            'date'                => $raw,
            'select', 'multiselect' => $raw,
            default               => (string) ($raw ?? ''),
        };
    }
}
