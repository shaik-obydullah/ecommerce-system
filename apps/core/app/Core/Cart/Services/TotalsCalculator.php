<?php

declare(strict_types=1);

namespace App\Core\Cart\Services;

use App\Core\Cart\Data\CartLineTotal;
use App\Core\Cart\Data\CartTotals;
use App\Core\Cart\Models\Cart;
use App\Core\Cart\Models\CartItem;
use App\Core\Catalog\Models\Product;
use App\Core\Pricing\Services\ProductPricing;
use App\Core\Promotions\Services\DiscountCalculator;
use App\Core\Support\Money\Currency;
use App\Core\Support\Money\Money;

/**
 * The one totals pipeline used by both storefronts.
 *
 * Pipeline: line subtotal -> cart-level coupon (allocated proportionally) ->
 * shipping -> tax -> grand total. Shipping and tax become non-zero in Phase 3;
 * the shape is already shared so neither storefront has to change later.
 */
final class TotalsCalculator
{
    public function __construct(
        private readonly ProductPricing $pricing,
        private readonly DiscountCalculator $discounts,
    ) {}

    public function calculate(Cart $cart): CartTotals
    {
        $currency = Currency::fromCode($cart->currency);
        $zero = Money::zero($currency);

        $cart->loadMissing([
            'items.product' => fn ($query) => $query->withStockSummary()->with('category'),
            'discount.discount',
        ]);

        $rawLines = [];
        $subtotalAmount = 0;

        foreach ($cart->items as $item) {
            $product = $item->product;
            $price = $this->pricing->for($product, $currency);
            $available = $product->derivedAvailableQty();

            $reason = null;

            if (! $product->is_active) {
                $reason = 'This product is no longer available.';
            } elseif (! $price->inStock) {
                $reason = 'This product is out of stock.';
            } elseif (! $price->purchasable) {
                $reason = "This product is not available in {$currency->code()}.";
            } elseif ($item->quantity > $available) {
                $reason = "Only {$available} unit(s) are available.";
            }

            $unitPrice = $price->purchasable ? $price->price : null;
            $lineSubtotal = $unitPrice === null ? $zero : $unitPrice->multiply($item->quantity);

            if ($lineSubtotal->isPositive()) {
                $subtotalAmount += $lineSubtotal->amount();
            }

            $rawLines[] = [
                'item' => $item,
                'product' => $product,
                'unit_price' => $unitPrice,
                'subtotal' => $lineSubtotal,
                'available' => $available,
                'purchasable' => $reason === null,
                'reason' => $reason,
            ];
        }

        $subtotal = Money::of($subtotalAmount, $currency);

        $quote = null;
        $discountAmount = 0;

        if ($cart->discount !== null) {
            $quote = $this->discounts->quote(
                $cart->discount->discount,
                $subtotal,
                $cart->user_id,
            );

            if ($quote->valid) {
                $discountAmount = $quote->amount->amount();
            }
        }

        $allocations = $this->allocate($discountAmount, $rawLines);

        $lines = [];

        foreach ($rawLines as $index => $raw) {
            $allocated = Money::of($allocations[$index] ?? 0, $currency);

            /** @var CartItem $item */
            $item = $raw['item'];
            /** @var Product $product */
            $product = $raw['product'];
            /** @var Money $lineSubtotal */
            $lineSubtotal = $raw['subtotal'];

            $lines[] = new CartLineTotal(
                item: $item,
                product: $product,
                quantity: $item->quantity,
                unitPrice: $raw['unit_price'],
                subtotal: $lineSubtotal,
                discount: $allocated,
                total: $lineSubtotal->subtract($allocated),
                availableQuantity: $raw['available'],
                purchasable: $raw['purchasable'],
                unavailableReason: $raw['reason'],
            );
        }

        $discount = Money::of($discountAmount, $currency);
        $shipping = $zero;
        $tax = $zero;
        $grandTotal = $subtotal->subtract($discount)->add($shipping)->add($tax);

        $isPurchasable = $lines !== []
            && $quote?->valid !== false
            && ! in_array(false, array_column($lines, 'purchasable'), true);

        return new CartTotals(
            currency: $currency,
            lines: $lines,
            subtotal: $subtotal,
            discount: $discount,
            discountQuote: $quote,
            shipping: $shipping,
            tax: $tax,
            grandTotal: $grandTotal,
            isPurchasable: $isPurchasable,
        );
    }

    /**
     * Allocate a cart-level discount across priced lines in proportion to their
     * subtotals. The last line absorbs the rounding remainder so the allocated
     * total always equals the coupon amount exactly.
     *
     * @param  list<array{item: CartItem, product: Product, unit_price: Money|null, subtotal: Money, available: int, purchasable: bool, reason: string|null}>  $lines
     * @return array<int, int>
     */
    private function allocate(int $amount, array $lines): array
    {
        $allocations = array_fill(0, count($lines), 0);

        if ($amount <= 0) {
            return $allocations;
        }

        $totalWeight = 0;

        foreach ($lines as $line) {
            if ($line['subtotal']->isPositive()) {
                $totalWeight += $line['subtotal']->amount();
            }
        }

        if ($totalWeight === 0) {
            return $allocations;
        }

        $remainingAmount = $amount;
        $remainingWeight = $totalWeight;
        $lastIndex = null;

        foreach ($lines as $index => $line) {
            $weight = $line['subtotal']->amount();

            if ($weight <= 0) {
                continue;
            }

            $lastIndex = $index;
        }

        foreach ($lines as $index => $line) {
            $weight = $line['subtotal']->amount();

            if ($weight <= 0) {
                continue;
            }

            if ($index === $lastIndex) {
                $allocations[$index] = $remainingAmount;

                break;
            }

            $share = (int) bcdiv(
                bcmul((string) $remainingAmount, (string) $weight, 0),
                (string) $remainingWeight,
                0,
            );

            $allocations[$index] = $share;
            $remainingAmount -= $share;
            $remainingWeight -= $weight;
        }

        return $allocations;
    }
}
