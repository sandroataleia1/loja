<?php

declare(strict_types=1);

namespace App\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ValidCpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('CPF inválido.');

            return;
        }

        $cpf = preg_replace('/\D/', '', $value);

        if (strlen($cpf) !== 11) {
            $fail('CPF inválido.');

            return;
        }

        // Rejeita sequências com todos os dígitos iguais (ex: 111.111.111-11)
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            $fail('CPF inválido.');

            return;
        }

        if (! $this->validateDigits($cpf)) {
            $fail('CPF inválido.');
        }
    }

    private function validateDigits(string $cpf): bool
    {
        return $this->calculateDigit($cpf, 9) === (int) $cpf[9]
            && $this->calculateDigit($cpf, 10) === (int) $cpf[10];
    }

    private function calculateDigit(string $cpf, int $position): int
    {
        $sum = 0;
        for ($i = 0; $i < $position; $i++) {
            $sum += (int) $cpf[$i] * ($position + 1 - $i);
        }

        $remainder = ($sum * 10) % 11;

        return $remainder === 10 ? 0 : $remainder;
    }
}
