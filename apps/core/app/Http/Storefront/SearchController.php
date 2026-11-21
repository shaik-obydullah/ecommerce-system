<?php

declare(strict_types=1);

namespace App\Http\Storefront;

use App\Core\Catalog\Data\ProductCard;
use App\Core\Catalog\Models\Product;
use App\Core\Catalog\Services\CatalogQuery;
use App\Core\Pricing\Services\CurrencyContext;
use App\Http\Controllers\Controller;
use App\Http\Storefront\Support\StorefrontPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class SearchController extends Controller
{
    public function __invoke(
        Request $request,
        CatalogQuery $catalog,
        CurrencyContext $currencyContext,
        StorefrontPresenter $presenter,
    ): View {
        $term = trim((string) $request->query('q', ''));
        $currency = $currencyContext->resolve($request);

        $products = $catalog
            ->products(['q' => $term])
            ->through(
                fn (Product $product): ProductCard => $presenter->card($product, $currency),
            );

        return view('storefront.catalog.search', [
            'term' => $term,
            'products' => $products,
        ]);
    }
}
