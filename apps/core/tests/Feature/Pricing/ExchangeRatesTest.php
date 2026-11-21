<?php

declare(strict_types=1);

use App\Core\Pricing\Models\ExchangeRate;
use App\Core\Pricing\Services\ExchangeRates;
use App\Core\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the latest direct rate for a currency pair', function (): void {
    ExchangeRate::factory()->create([
        'base_currency' => 'USD',
        'quote_currency' => 'EUR',
        'rate' => '0.9000000000',
        'fetched_at' => now()->subHour(),
    ]);

    ExchangeRate::factory()->create([
        'base_currency' => 'USD',
        'quote_currency' => 'EUR',
        'rate' => '0.9200000000',
        'fetched_at' => now(),
    ]);

    expect(app(ExchangeRates::class)->rate('usd', 'eur'))->toBe('0.9200000000');
});

it('uses the inverse pair when no direct rate exists', function (): void {
    ExchangeRate::factory()->create([
        'base_currency' => 'EUR',
        'quote_currency' => 'USD',
        'rate' => '0.8000000000',
    ]);

    expect(app(ExchangeRates::class)->rate('USD', 'EUR'))->toBe('1.250000000000');
});

it('returns an identity rate for the same currency', function (): void {
    expect(app(ExchangeRates::class)->rate('EUR', 'EUR'))->toBe('1');
});

it('returns null when neither direction of a pair exists', function (): void {
    expect(app(ExchangeRates::class)->rate('USD', 'GBP'))->toBeNull();
});

it('converts money using exact decimal arithmetic', function (): void {
    ExchangeRate::factory()->create([
        'base_currency' => 'USD',
        'quote_currency' => 'EUR',
        'rate' => '0.9200000000',
    ]);

    $converted = app(ExchangeRates::class)->convert(Money::of(10_000, 'USD'), 'EUR');

    expect($converted)->not->toBeNull()
        ->and($converted?->amount())->toBe(9_200)
        ->and($converted?->currency()->code())->toBe('EUR');
});

it('rounds converted amounts to the target currency minor units', function (): void {
    ExchangeRate::factory()->create([
        'base_currency' => 'USD',
        'quote_currency' => 'GBP',
        'rate' => '0.3330000000',
    ]);

    // $19.99 × 0.333 = £6.65667, which rounds to 666 minor units.
    $converted = app(ExchangeRates::class)->convert(Money::of(1_999, 'USD'), 'GBP');

    expect($converted?->amount())->toBe(666);
});

it('converts to zero-decimal currencies without losing precision', function (): void {
    ExchangeRate::factory()->create([
        'base_currency' => 'USD',
        'quote_currency' => 'JPY',
        'rate' => '150.0000000000',
    ]);

    $converted = app(ExchangeRates::class)->convert(Money::of(10_000, 'USD'), 'JPY');

    expect($converted?->amount())->toBe(15_000)
        ->and($converted?->currency()->code())->toBe('JPY');
});

it('returns the original money when converting to the same currency', function (): void {
    $money = Money::of(1_234, 'USD');

    expect(app(ExchangeRates::class)->convert($money, 'USD'))->toBe($money);
});

it('invalidates the cached rate when a new rate is saved', function (): void {
    ExchangeRate::factory()->create([
        'base_currency' => 'USD',
        'quote_currency' => 'EUR',
        'rate' => '0.9000000000',
        'fetched_at' => now()->subHour(),
    ]);

    $rates = app(ExchangeRates::class);

    expect($rates->rate('USD', 'EUR'))->toBe('0.9000000000');

    ExchangeRate::factory()->create([
        'base_currency' => 'USD',
        'quote_currency' => 'EUR',
        'rate' => '0.9500000000',
        'fetched_at' => now(),
    ]);

    expect($rates->rate('USD', 'EUR'))->toBe('0.9500000000');
});
