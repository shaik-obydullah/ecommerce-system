<?php

declare(strict_types=1);

namespace App\Http\Storefront;

use App\Core\Catalog\Models\Product;
use App\Core\Pricing\Services\CurrencyContext;
use App\Core\Pricing\Services\ProductPricing;
use App\Http\Controllers\Controller;
use App\Http\Storefront\Support\StorefrontPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class ProductController extends Controller
{
    public function __invoke(
        Request $request,
        Product $product,
        ProductPricing $pricing,
        CurrencyContext $currencyContext,
        StorefrontPresenter $presenter,
    ): View {
        abort_unless($product->is_active, 404);

        $product = Product::query()
            ->withStockSummary()
            ->with('category')
            ->findOrFail($product->getKey());

        $currency = $currencyContext->resolve($request);
        $price = $pricing->for($product, $currency);

        $related = Product::query()
            ->active()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->getKey())
            ->withStockSummary()
            ->with('category')
            ->orderByDesc('created_at')
            ->limit(4)
            ->get();

        return view('storefront.catalog.product', [
            'product' => $product,
            'price' => $price,
            'relatedProducts' => $presenter->cards($related, $currency),
        ]);
    }
}
