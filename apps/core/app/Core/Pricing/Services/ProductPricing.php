<?php

declare(strict_types=1);

namespace App\Core\Pricing\Services;

use App\Core\Catalog\Models\Product;
use App\Core\Pricing\Data\ProductPrice;
use App\Core\Support\Money\Currency;
use App\Core\Support\Money\Money;

/**
 * Derives the one customer-facing price for a product.
 */
final class ProductPricing
{
    public function __construct(
        private readonly ExchangeRates $exchangeRates,
    ) {}

    public function for(Product $product, Currency $currency): ProductPrice
    {
        $baseAmount = $product->derivedSalePriceAmount();

        if ($baseAmount === null) {
            return ProductPrice::unavailable();
        }

        $price = $this->exchangeRates->convert(
            Money::of($baseAmount, Currency::base()),
            $currency,
        );

        return new ProductPrice(
            price: $price,
            inStock: true,
            purchasable: $price !== null,
        );
    }

    public function basePrice(Product $product): ?Money
    {
        $amount = $product->derivedSalePriceAmount();

        return $amount === null ? null : Money::of($amount, Currency::base());
    }
}
