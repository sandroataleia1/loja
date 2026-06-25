<?php

declare(strict_types=1);

namespace App\Modules\Settings\Services;

use App\Core\Auth\Models\User;
use App\Modules\Settings\Models\ConfigHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Registra alterações de configuração no histórico imutável.
 *
 * Compara oldValues vs newValues e cria um ConfigHistory por chave alterada.
 * Falhas silenciosas — nunca pode interromper o caller.
 */
final class ConfigHistoryService
{
    /**
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     */
    public function record(
        string   $tenantId,
        string   $section,
        array    $oldValues,
        array    $newValues,
        ?User    $changedBy,
        Request  $request,
    ): void {
        try {
            $rows = [];
            $now  = now();

            foreach ($newValues as $key => $newVal) {
                $oldVal = $oldValues[$key] ?? null;

                // Ignora chaves sem alteração real
                if ($this->valuesAreEqual($oldVal, $newVal)) {
                    continue;
                }

                $rows[] = [
                    'uuid'       => \Illuminate\Support\Str::uuid()->toString(),
                    'tenant_id'  => $tenantId,
                    'section'    => $section,
                    'key'        => $key,
                    'old_value'  => $this->serialize($oldVal),
                    'new_value'  => $this->serialize($newVal),
                    'changed_by' => $changedBy?->uuid,
                    'changed_at' => $now,
                    'ip'         => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ];
            }

            if (! empty($rows)) {
                ConfigHistory::insert($rows);
            }
        } catch (Throwable $e) {
            Log::warning('ConfigHistoryService: failed to record history', [
                'tenant_id' => $tenantId,
                'section'   => $section,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    private function valuesAreEqual(mixed $a, mixed $b): bool
    {
        // Normaliza para JSON antes de comparar (lida com arrays e nulls)
        return json_encode($a) === json_encode($b);
    }

    private function serialize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_array($value) ? json_encode($value) : (string) $value;
    }
}
