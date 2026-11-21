<?php

declare(strict_types=1);

namespace App\Core\Catalog\Data;

use App\Core\Catalog\Models\Product;
use App\Core\Pricing\Data\ProductPrice;

/**
 * View model for a product tile in the Blade storefront.
 */
final readonly class ProductCard
{
    public function __construct(
        public string $publicId,
        public string $name,
        public string $slug,
        public ?string $categoryName,
        public ProductPrice $price,
        public string $url,
    ) {}

    public static function from(Product $product, ProductPrice $price): self
    {
        return new self(
            publicId: (string) $product->public_id,
            name: $product->name,
            slug: $product->slug,
            categoryName: $product->relationLoaded('category') ? $product->category?->name : null,
            price: $price,
            url: route('storefront.products.show', ['product' => $product->slug]),
        );
    }
}
