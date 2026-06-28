<?php

declare(strict_types=1);

namespace App\Shared\Traits;

use App\Modules\Settings\Models\CustomFieldDefinition;
use App\Modules\Settings\Models\CustomFieldValue;

/**
 * Adiciona suporte a campos customizáveis em qualquer modelo Eloquent.
 *
 * O modelo deve definir: $customFieldEntityType (string).
 *
 * Uso:
 *   class Customer extends BaseModel {
 *       use HasCustomFields;
 *       protected string $customFieldEntityType = 'customer';
 *   }
 */
trait HasCustomFields
{
    public function customFieldValues(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CustomFieldValue::class, 'entity_id', 'uuid')
            ->where('entity_type', $this->getCustomFieldEntityType())
            ->with('definition');
    }

    /**
     * Retorna todos os campos com seus valores.
     *
     * @return array<string, mixed> field_key => valor tipado
     */
    public function getCustomFieldsArray(): array
    {
        return $this->customFieldValues
            ->mapWithKeys(fn (CustomFieldValue $v) => [
                $v->definition?->field_key ?? $v->field_definition_id => $v->getValue(),
            ])
            ->toArray();
    }

    public function getCustomField(string $key): mixed
    {
        $value = $this->customFieldValues
            ->first(fn (CustomFieldValue $v) => $v->definition?->field_key === $key);

        return $value?->getValue();
    }

    public function setCustomField(string $key, mixed $value): void
    {
        $tenantId   = $this->tenant_id;
        $entityType = $this->getCustomFieldEntityType();

        $definition = CustomFieldDefinition::where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('field_key', $key)
            ->where('is_active', true)
            ->first();

        if ($definition === null) {
            return;
        }

        $cfv = CustomFieldValue::firstOrNew([
            'field_definition_id' => $definition->uuid,
            'entity_id'           => $this->uuid,
        ]);

        $cfv->tenant_id   = $tenantId;
        $cfv->entity_type = $entityType;
        $cfv->setValue($value);
        $cfv->save();
    }

    /**
     * Salva um conjunto de campos de uma vez.
     *
     * @param array<string, mixed> $values field_key => valor
     */
    public function saveCustomFields(array $values): void
    {
        $tenantId   = $this->tenant_id;
        $entityType = $this->getCustomFieldEntityType();

        $definitions = CustomFieldDefinition::where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->get()
            ->keyBy('field_key');

        foreach ($values as $key => $value) {
            $definition = $definitions->get($key);

            if ($definition === null) {
                continue;
            }

            $cfv = CustomFieldValue::firstOrNew([
                'field_definition_id' => $definition->uuid,
                'entity_id'           => $this->uuid,
            ]);

            $cfv->tenant_id   = $tenantId;
            $cfv->entity_type = $entityType;
            $cfv->setValue($value);
            $cfv->save();
        }
    }

    public function getCustomFieldEntityType(): string
    {
        return $this->customFieldEntityType ?? strtolower(class_basename(static::class));
    }
}
