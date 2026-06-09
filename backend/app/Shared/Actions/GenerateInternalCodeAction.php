<?php

declare(strict_types=1);

namespace App\Shared\Actions;

use App\Core\Tenancy\Models\TenantSequence;
use App\Shared\Enums\SequenceEntityEnum;
use Illuminate\Support\Facades\DB;

/**
 * Gera códigos operacionais únicos por tenant com segurança contra condição de corrida.
 *
 * Usa lockForUpdate para garantir atomicidade em ambiente multi-processo.
 * O código gerado é NUNCA usado como FK — apenas como identificador humano.
 */
final readonly class GenerateInternalCodeAction
{
    /**
     * @param  string  $subEntityId  String vazia para entidades sem sub-escopo.
     *                               Para variantes, é o UUID do produto pai.
     * @param  string|null  $prefixOverride  Prefixo alternativo (variantes usam product.code + '-')
     */
    public function execute(
        string $tenantId,
        SequenceEntityEnum $entity,
        string $subEntityId = '',
        ?string $prefixOverride = null,
    ): string {
        return DB::transaction(function () use ($tenantId, $entity, $subEntityId, $prefixOverride): string {
            $sequence = TenantSequence::where('tenant_id', $tenantId)
                ->where('entity', $entity->value)
                ->where('sub_entity_id', $subEntityId)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                $sequence = TenantSequence::create([
                    'tenant_id'     => $tenantId,
                    'entity'        => $entity,
                    'sub_entity_id' => $subEntityId,
                    'current_value' => 0,
                ]);
            }

            $nextValue = $sequence->current_value + 1;
            $sequence->update(['current_value' => $nextValue]);

            $prefix  = $prefixOverride ?? $entity->prefix();
            $padding = $entity->padding();

            return $prefix . str_pad((string) $nextValue, $padding, '0', STR_PAD_LEFT);
        });
    }
}
