<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida o formato do código NCM.
 *
 * Aceita dois formatos:
 *   - Com pontos:   XXXX.XX.XX  (ex: 3209.10.00)
 *   - Sem pontos:   XXXXXXXX    (ex: 32091000)
 *
 * Normaliza internamente para o formato com pontos antes de comparar.
 */
final class ValidNcmFormat implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('O campo :attribute deve ser uma string.');

            return;
        }

        $clean = str_replace('.', '', $value);

        if (! preg_match('/^\d{8}$/', $clean)) {
            $fail('O campo :attribute deve estar no formato NCM válido (XXXX.XX.XX ou XXXXXXXX).');
        }
    }
}
