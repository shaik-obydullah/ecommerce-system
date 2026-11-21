<?php

declare(strict_types=1);

namespace App\Core\Cart\Services;

use App\Core\Catalog\Models\Product;
use App\Core\Pricing\Services\ProductPricing;
use App\Core\Support\Money\Currency;
use Illuminate\Validation\ValidationException;

/**
 * The single place where a cart line is checked against catalog and stock
 * state. Cart mutations fail loudly instead of silently capping quantities.
 */
final class CartAvailability
{
    public function __construct(
        private readonly ProductPricing $pricing,
    ) {}

    public function assertAddable(
        Product $product,
        int $quantity,
        Currency $currency,
        int $alreadyInCart = 0,
    ): void {
        if (! $product->is_active) {
            throw ValidationException::withMessages([
                'product_id' => 'This product is not available.',
            ]);
        }

        $price = $this->pricing->for($product, $currency);

        if (! $price->purchasable) {
            throw ValidationException::withMessages([
                'product_id' => $price->inStock
                    ? "This product is not available in {$currency->code()}."
                    : 'This product is out of stock.',
            ]);
        }

        $available = $product->derivedAvailableQty();
        $maximum = max(0, $available - $alreadyInCart);

        if ($quantity > $maximum) {
            $message = $maximum === 0
                ? 'Your cart already contains the available quantity.'
                : "Only {$maximum} unit(s) are available.";

            throw ValidationException::withMessages([
                'quantity' => $message,
            ]);
        }
    }
}
