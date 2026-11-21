<?php

declare(strict_types=1);

use App\Core\Support\Money\Currency;

it('resolves configured currencies', function (): void {
    $usd = Currency::fromCode('usd');

    expect($usd->code())->toBe('USD')
        ->and($usd->minorUnits())->toBe(2)
        ->and($usd->symbol())->toBe('$')
        ->and($usd->format(1234))->toBe('$12.34');
});

it('supports zero and three decimal currencies', function (): void {
    expect(Currency::fromCode('JPY')->minorUnits())->toBe(0)
        ->and(Currency::fromCode('JPY')->format(1234))->toBe('¥1,234')
        ->and(Currency::fromCode('KWD')->minorUnits())->toBe(3)
        ->and(Currency::fromCode('KWD')->format(1234))->toBe('1.234 KD');
});

it('rejects invalid or unsupported currency codes', function (string $code): void {
    Currency::fromCode($code);
})->with(['US', 'USDD', 'ZZZ'])->throws(InvalidArgumentException::class);

it('exposes the supported currency list', function (): void {
    expect(Currency::supportedCodes())->toContain('USD', 'EUR', 'GBP')
        ->and(Currency::isSupported('usd'))->toBeTrue()
        ->and(Currency::isSupported('ZZZ'))->toBeFalse()
        ->and(Currency::base()->code())->toBe('USD');
});
