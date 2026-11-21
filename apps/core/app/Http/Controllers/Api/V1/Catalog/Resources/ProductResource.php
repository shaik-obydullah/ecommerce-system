<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalog\Resources;

use App\Core\Catalog\Models\Product;
use App\Core\Pricing\Services\ProductPricing;
use App\Core\Support\Money\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public product representation.
 *
 * Deliberately excludes stock rows, buy prices, COGS, and profit. The single
 * `price` is the highest in-stock sale price converted to the request currency.
 *
 * @mixin Product
 */
final class ProductResource extends JsonResource
{
    public function __construct(
        Product $resource,
        private readonly Currency $currency,
        private readonly ProductPricing $pricing,
    ) {
        parent::__construct($resource);
    }

    public static function for(Product $product, Currency $currency, ProductPricing $pricing): self
    {
        return new self($product, $currency, $pricing);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Product $product */
        $product = $this->resource;
        $price = $this->pricing->for($product, $this->currency);

        return [
            'id' => $product->public_id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'category' => $this->whenLoaded('category', fn (): ?array => $product->category === null ? null : [
                'id' => $product->category->public_id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ]),
            'price' => $price->price?->toArray(),
            'in_stock' => $price->inStock,
            'is_purchasable' => $price->purchasable,
            'created_at' => $product->created_at?->toIso8601String(),
        ];
    }
}
