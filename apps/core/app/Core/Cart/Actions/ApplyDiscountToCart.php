<?php

declare(strict_types=1);

namespace App\Core\Cart\Actions;

use App\Core\Cart\Models\Cart;
use App\Core\Cart\Services\TotalsCalculator;
use App\Core\Promotions\Models\Discount;
use App\Core\Promotions\Services\DiscountCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Attaches one coupon to a cart. The coupon is validated against the current
 * subtotal and the cart's locked currency before it is stored.
 */
final class ApplyDiscountToCart
{
    public function __construct(
        private readonly TotalsCalculator $totals,
        private readonly DiscountCalculator $discounts,
    ) {}

    public function handle(Cart $cart, string $code): Cart
    {
        $code = Str::upper(trim($code));

        $discount = Discount::query()->where('code', $code)->first();

        if (! $discount instanceof Discount) {
            throw ValidationException::withMessages([
                'code' => 'This coupon code is not valid.',
            ]);
        }

        $subtotal = $this->totals->calculate($cart)->subtotal;
        $quote = $this->discounts->quote($discount, $subtotal, $cart->user_id);

        if (! $quote->valid) {
            throw ValidationException::withMessages([
                'code' => $quote->message ?? 'This coupon cannot be applied.',
            ]);
        }

        DB::transaction(function () use ($cart, $discount, $code): void {
            $cart->discount()->updateOrCreate([], [
                'discount_id' => $discount->getKey(),
                'code' => $code,
            ]);

            $cart->bumpVersion();
        });

        return $cart->refresh()->load('discount.discount');
    }
}
