<?php

declare(strict_types=1);

namespace App\Http\Storefront;

use App\Core\Catalog\Models\Product;
use App\Core\Pricing\Services\CurrencyContext;
use App\Http\Controllers\Controller;
use App\Http\Storefront\Support\StorefrontPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class HomeController extends Controller
{
    public function __invoke(
        Request $request,
        CurrencyContext $currencyContext,
        StorefrontPresenter $presenter,
    ): View {
        $currency = $currencyContext->resolve($request);

        $featured = Product::query()
            ->active()
            ->featured()
            ->withStockSummary()
            ->with('category')
            ->latest('created_at')
            ->limit(8)
            ->get();

        $latest = Product::query()
            ->active()
            ->withStockSummary()
            ->with('category')
            ->latest('created_at')
            ->limit(8)
            ->get();

        return view('storefront.home', [
            'featuredProducts' => $presenter->cards($featured, $currency),
            'latestProducts' => $presenter->cards($latest, $currency),
        ]);
    }
}
