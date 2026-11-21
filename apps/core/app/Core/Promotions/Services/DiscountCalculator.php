<?php

declare(strict_types=1);

namespace App\Core\Promotions\Services;

use App\Core\Promotions\Data\DiscountQuote;
use App\Core\Promotions\Models\Discount;
use App\Core\Promotions\Models\DiscountUsage;
use App\Core\Support\Money\Money;
use Illuminate\Support\Str;

/**
 * Validates coupons and calculates their cart-level discount.
 *
 * All amounts are integer minor units. Percentage coupons use basis points so
 * the calculation never touches floating-point arithmetic.
 */
final class DiscountCalculator
{
    public function quote(Discount $discount, Money $subtotal, ?int $userId = null): DiscountQuote
    {
        $code = Str::upper($discount->code);
        $zero = Money::zero($subtotal->currency());

        if (! $discount->is_active) {
            return DiscountQuote::invalid($code, $zero, 'This coupon is not active.');
        }

        if (! $discount->isWithinWindow()) {
            return DiscountQuote::invalid($code, $zero, 'This coupon is not available right now.');
        }

        if (! $discount->isAvailableInCurrency($subtotal->currency())) {
            return DiscountQuote::invalid(
                $code,
                $zero,
                "This coupon is only valid in {$discount->currency}.",
            );
        }

        if ($discount->min_subtotal_amount !== null && $subtotal->amount() < $discount->min_subtotal_amount) {
            $minimum = Money::of($discount->min_subtotal_amount, $subtotal->currency());

            return DiscountQuote::invalid(
                $code,
                $zero,
                "Spend at least {$minimum->format()} to use this coupon.",
            );
        }

        if ($discount->hasReachedUsageLimit()) {
            return DiscountQuote::invalid($code, $zero, 'This coupon has reached its usage limit.');
        }

        if ($userId !== null && $this->userHasReachedLimit($discount, $userId)) {
            return DiscountQuote::invalid($code, $zero, 'You have already used this coupon.');
        }

        $amount = $discount->type->isPercentage()
            ? $this->percentageAmount($subtotal->amount(), $discount->value)
            : $discount->value;

        $amount = max(0, min($amount, $subtotal->amount()));

        if ($amount === 0) {
            return DiscountQuote::invalid($code, $zero, 'This coupon has no value for this cart.');
        }

        return new DiscountQuote($code, Money::of($amount, $subtotal->currency()), true);
    }

    /**
     * Round half-up to the nearest minor unit.
     */
    private function percentageAmount(int $subtotalAmount, int $basisPoints): int
    {
        $numerator = bcmul((string) $subtotalAmount, (string) $basisPoints, 0);
        $rounded = bcadd($numerator, '5000', 0);

        return (int) bcdiv($rounded, '10000', 0);
    }

    private function userHasReachedLimit(Discount $discount, int $userId): bool
    {
        if ($discount->per_user_limit === null) {
            return false;
        }

        $used = DiscountUsage::query()
            ->where('discount_id', $discount->getKey())
            ->where('user_id', $userId)
            ->count();

        return $used >= $discount->per_user_limit;
    }
}
