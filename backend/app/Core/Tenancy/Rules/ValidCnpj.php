<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida CNPJ com algoritmo completo de dígitos verificadores.
 *
 * Aceita os formatos:
 *   - Com máscara: XX.XXX.XXX/XXXX-XX
 *   - Sem máscara: XXXXXXXXXXXXXX (14 dígitos)
 *
 * Rejeita CNPJs com todos os dígitos iguais (ex: 00000000000000),
 * que passariam no cálculo matemático mas são inválidos.
 */
final class ValidCnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('O campo :attribute deve ser uma string.');

            return;
        }

        $cnpj = preg_replace('/\D/', '', $value);

        if (strlen($cnpj) !== 14) {
            $fail('O campo :attribute deve conter um CNPJ válido com 14 dígitos.');

            return;
        }

        // Rejeita sequências com todos dígitos iguais
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            $fail('O campo :attribute não é um CNPJ válido.');

            return;
        }

        if (! $this->validateDigits($cnpj)) {
            $fail('O campo :attribute não é um CNPJ válido (dígitos verificadores incorretos).');
        }
    }

    private function validateDigits(string $cnpj): bool
    {
        return $this->calculateDigit($cnpj, 12) === (int) $cnpj[12]
            && $this->calculateDigit($cnpj, 13) === (int) $cnpj[13];
    }

    private function calculateDigit(string $cnpj, int $position): int
    {
        $weights = $position === 12
            ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]
            : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $sum = 0;
        foreach ($weights as $index => $weight) {
            $sum += (int) $cnpj[$index] * $weight;
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
