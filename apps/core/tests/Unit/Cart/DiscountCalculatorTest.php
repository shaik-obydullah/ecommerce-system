<?php

declare(strict_types=1);

use App\Core\Promotions\Data\DiscountQuote;
use App\Core\Promotions\Models\Discount;
use App\Core\Promotions\Models\DiscountUsage;
use App\Core\Promotions\Services\DiscountCalculator;
use App\Core\Support\Money\Money;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function quoteCoupon(Discount $discount, int $subtotalAmount = 10_000, ?int $userId = null): DiscountQuote
{
    return app(DiscountCalculator::class)->quote(
        $discount,
        Money::of($subtotalAmount, 'USD'),
        $userId,
    );
}

it('calculates percentage coupons in basis points with half-up rounding', function (): void {
    $coupon = Discount::factory()->percentage(1_250)->make();

    $quote = quoteCoupon($coupon, 1_999);

    expect($quote->valid)->toBeTrue()
        ->and($quote->amount->amount())->toBe(250)
        ->and($quote->code)->toBe($coupon->code);
});

it('prices a fixed coupon in minor units', function (): void {
    $coupon = Discount::factory()->fixed(500, 'USD')->make();

    $quote = quoteCoupon($coupon);

    expect($quote->valid)->toBeTrue()
        ->and($quote->amount->amount())->toBe(500)
        ->and($quote->amount->currency()->code())->toBe('USD');
});

it('caps a fixed coupon at the cart subtotal', function (): void {
    $coupon = Discount::factory()->fixed(5_000, 'USD')->make();

    $quote = quoteCoupon($coupon, 1_200);

    expect($quote->valid)->toBeTrue()
        ->and($quote->amount->amount())->toBe(1_200);
});

it('rejects an inactive coupon', function (): void {
    $coupon = Discount::factory()->inactive()->make();

    $quote = quoteCoupon($coupon);

    expect($quote->valid)->toBeFalse()
        ->and($quote->amount->isZero())->toBeTrue()
        ->and($quote->message)->toContain('not active');
});

it('rejects an expired coupon', function (): void {
    $coupon = Discount::factory()->expired()->make();

    $quote = quoteCoupon($coupon);

    expect($quote->valid)->toBeFalse()
        ->and($quote->message)->toContain('not available');
});

it('rejects a fixed coupon in the wrong currency', function (): void {
    $coupon = Discount::factory()->fixed(500, 'EUR')->make();

    $quote = quoteCoupon($coupon);

    expect($quote->valid)->toBeFalse()
        ->and($quote->message)->toContain('only valid in EUR');
});

it('rejects a coupon below the minimum subtotal', function (): void {
    $coupon = Discount::factory()
        ->percentage(1_000)
        ->make(['min_subtotal_amount' => 20_000]);

    $quote = quoteCoupon($coupon, 10_000);

    expect($quote->valid)->toBeFalse()
        ->and($quote->message)->toContain('Spend at least');
});

it('rejects a coupon that has reached its usage limit', function (): void {
    $coupon = Discount::factory()->make([
        'usage_limit' => 5,
        'usage_count' => 5,
    ]);

    $quote = quoteCoupon($coupon);

    expect($quote->valid)->toBeFalse()
        ->and($quote->message)->toContain('usage limit');
});

it('rejects a coupon the customer has already used', function (): void {
    $user = User::factory()->create();
    $coupon = Discount::factory()->create(['per_user_limit' => 1]);

    DiscountUsage::query()->create([
        'discount_id' => $coupon->getKey(),
        'user_id' => $user->getKey(),
        'amount' => 100,
        'currency' => 'USD',
        'used_at' => now(),
    ]);

    $quote = quoteCoupon($coupon, 10_000, (int) $user->getKey());

    expect($quote->valid)->toBeFalse()
        ->and($quote->message)->toContain('already used');
});
