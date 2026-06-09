<?php

declare(strict_types=1);

use App\Shared\ValueObjects\Money;

it('creates money from float', function (): void {
    $money = Money::fromFloat(59.90);

    expect($money->amount())->toBe(5990)
        ->and($money->toFloat())->toBe(59.9);
});

it('creates money from cents', function (): void {
    $money = Money::fromCents(4990);

    expect($money->toFloat())->toBe(49.9);
});

it('adds two money values', function (): void {
    $a = Money::fromCents(5990);
    $b = Money::fromCents(4990);

    expect($a->add($b)->amount())->toBe(10980);
});

it('subtracts money values', function (): void {
    $a = Money::fromCents(10000);
    $b = Money::fromCents(3000);

    expect($a->subtract($b)->amount())->toBe(7000);
});

it('multiplies money by factor', function (): void {
    $money = Money::fromCents(1000);

    expect($money->multiply(1.5)->amount())->toBe(1500);
});

it('throws on currency mismatch', function (): void {
    $brl = Money::fromCents(1000, 'BRL');
    $usd = Money::fromCents(1000, 'USD');

    $brl->add($usd);
})->throws(\InvalidArgumentException::class);

it('throws on negative amount', function (): void {
    new Money(-1);
})->throws(\InvalidArgumentException::class);
