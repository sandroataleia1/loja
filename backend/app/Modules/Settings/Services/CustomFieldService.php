<?php

declare(strict_types=1);

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Models\CustomFieldDefinition;
use App\Modules\Settings\Models\CustomFieldValue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Gerencia definições e valores de campos customizados por tenant.
 *
 * Cache: `custom_fields:{tenantId}:{entityType}` TTL 300s.
 */
final class CustomFieldService
{
    private const CACHE_TTL = 300;

    /**
     * Lista definições ativas para uma entidade.
     *
     * @return \Illuminate\Database\Eloquent\Collection<CustomFieldDefinition>
     */
    public function definitionsForEntity(string $tenantId, string $entityType)
    {
        return Cache::remember(
            "custom_fields:{$tenantId}:{$entityType}",
            self::CACHE_TTL,
            fn () => CustomFieldDefinition::where('tenant_id', $tenantId)
                ->where('entity_type', $entityType)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get(),
        );
    }

    public function createDefinition(string $tenantId, array $data): CustomFieldDefinition
    {
        $data['tenant_id'] = $tenantId;
        $data['field_key'] = $data['field_key'] ?? Str::slug($data['label'] ?? '', '_');

        $definition = CustomFieldDefinition::create($data);

        Cache::forget("custom_fields:{$tenantId}:{$data['entity_type']}");

        return $definition;
    }

    public function updateDefinition(CustomFieldDefinition $definition, array $data): CustomFieldDefinition
    {
        $definition->update($data);
        Cache::forget("custom_fields:{$definition->tenant_id}:{$definition->entity_type}");

        return $definition->fresh();
    }

    /**
     * Retorna valores de campos de uma entidade como array field_key => valor.
     *
     * @return array<string, mixed>
     */
    public function valuesForEntity(string $tenantId, string $entityType, string $entityId): array
    {
        $values = CustomFieldValue::where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->with('definition')
            ->get();

        return $values->mapWithKeys(fn (CustomFieldValue $v) => [
            $v->definition?->field_key ?? $v->field_definition_id => $v->getValue(),
        ])->toArray();
    }

    /**
     * Salva valores de campos customizados para uma entidade.
     *
     * @param array<string, mixed> $values field_key => valor
     */
    public function saveValues(string $tenantId, string $entityType, string $entityId, array $values): void
    {
        $definitions = CustomFieldDefinition::where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->get()
            ->keyBy('field_key');

        $errors = [];

        foreach ($values as $key => $value) {
            $definition = $definitions->get($key);

            if ($definition === null) {
                continue;
            }

            if ($definition->is_required && ($value === null || $value === '')) {
                $errors["custom_fields.{$key}"] = ["O campo \"{$definition->label}\" é obrigatório."];
                continue;
            }

            if ($definition->field_type === 'select' && $value !== null) {
                $options = $definition->options ?? [];
                if (! in_array($value, $options, true)) {
                    $errors["custom_fields.{$key}"] = ["Valor inválido para \"{$definition->label}\"."];
                    continue;
                }
            }

            $cfv = CustomFieldValue::firstOrNew([
                'field_definition_id' => $definition->uuid,
                'entity_id'           => $entityId,
            ]);

            $cfv->tenant_id    = $tenantId;
            $cfv->entity_type  = $entityType;
            $cfv->setValue($value);
            $cfv->save();
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
