<?php

declare(strict_types=1);

namespace App\Shared\ValueObjects;

use InvalidArgumentException;

final readonly class Money
{
    public function __construct(
        private int $amount,
        private string $currency = 'BRL',
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Money amount cannot be negative.');
        }
    }

    public static function fromFloat(float $value, string $currency = 'BRL'): self
    {
        return new self((int) round($value * 100), $currency);
    }

    public static function fromCents(int $cents, string $currency = 'BRL'): self
    {
        return new self($cents, $currency);
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function toFloat(): float
    {
        return $this->amount / 100;
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        return new self((int) round($this->amount * $multiplier), $this->currency);
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function isGreaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount > $other->amount;
    }

    public function format(): string
    {
        return number_format($this->toFloat(), 2, ',', '.');
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException("Currency mismatch: {$this->currency} vs {$other->currency}");
        }
    }
}
