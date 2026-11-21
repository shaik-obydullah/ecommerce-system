<?php

declare(strict_types=1);

namespace App\Http\Storefront\Support;

use App\Core\Catalog\Data\ProductCard;
use App\Core\Catalog\Models\Product;
use App\Core\Pricing\Services\ProductPricing;
use App\Core\Support\Money\Currency;

/**
 * Maps catalog models to the view models used by Blade templates.
 */
final class StorefrontPresenter
{
    public function __construct(
        private readonly ProductPricing $pricing,
    ) {}

    public function card(Product $product, Currency $currency): ProductCard
    {
        return ProductCard::from($product, $this->pricing->for($product, $currency));
    }

    /**
     * @param  iterable<Product>  $products
     * @return list<ProductCard>
     */
    public function cards(iterable $products, Currency $currency): array
    {
        $cards = [];

        foreach ($products as $product) {
            $cards[] = $this->card($product, $currency);
        }

        return $cards;
    }
}
