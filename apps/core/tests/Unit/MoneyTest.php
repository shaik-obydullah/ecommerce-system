<?php

declare(strict_types=1);

use App\Core\Support\Money\Money;

it('adds and subtracts money in the same currency', function (): void {
    $ten = Money::of(1000, 'USD');
    $five = Money::of(500, 'USD');

    expect($ten->add($five)->amount())->toBe(1500)
        ->and($ten->subtract($five)->amount())->toBe(500)
        ->and($ten->multiply(3)->amount())->toBe(3000);
});

it('rejects arithmetic across currencies', function (): void {
    Money::of(1000, 'USD')->add(Money::of(1000, 'EUR'));
})->throws(InvalidArgumentException::class);

it('converts decimal strings into minor units', function (): void {
    expect(Money::fromDecimal('19.99', 'USD')->amount())->toBe(1999)
        ->and(Money::fromDecimal('19.995', 'USD')->amount())->toBe(2000)
        ->and(Money::fromDecimal('-0.01', 'USD')->amount())->toBe(-1)
        ->and(Money::fromDecimal('100', 'JPY')->amount())->toBe(100)
        ->and(Money::fromDecimal('1.2345', 'KWD')->amount())->toBe(1235);
});

it('formats amounts without floating point arithmetic', function (): void {
    expect(Money::of(123456789, 'USD')->format())->toBe('$1,234,567.89')
        ->and(Money::of(-1234, 'USD')->format())->toBe('-$12.34')
        ->and(Money::of(1234, 'JPY')->format())->toBe('¥1,234')
        ->and(Money::of(1234, 'KWD')->format())->toBe('1.234 KD');
});

it('allocates money without losing minor units', function (): void {
    $parts = Money::of(1000, 'USD')->allocate(3);

    expect(array_map(static fn (Money $money): int => $money->amount(), $parts))
        ->toBe([334, 333, 333])
        ->and(array_sum(array_map(static fn (Money $money): int => $money->amount(), $parts)))
        ->toBe(1000);
});

it('compares money of the same currency', function (): void {
    $a = Money::of(100, 'USD');
    $b = Money::of(200, 'USD');

    expect($a->compareTo($b))->toBe(-1)
        ->and($b->compareTo($a))->toBe(1)
        ->and($a->compareTo(Money::of(100, 'USD')))->toBe(0)
        ->and($a->equals(Money::of(100, 'USD')))->toBeTrue()
        ->and($a->isZero())->toBeFalse()
        ->and(Money::zero('USD')->isZero())->toBeTrue();
});
