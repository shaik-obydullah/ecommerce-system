<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Cart\Services\CartManager;
use App\Core\Catalog\Services\CategoryTree;
use App\Core\Pricing\Services\CurrencyContext;
use App\Core\Support\Money\Currency;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shares the storefront's currency and navigation with every Blade view.
 */
final class ShareStorefrontContext
{
    public function __construct(
        private readonly CurrencyContext $currencyContext,
        private readonly CategoryTree $categoryTree,
        private readonly CartManager $carts,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $currency = $this->currencyContext->resolve($request);

        View::share('storeName', (string) config('ecommerce.store_name'));
        View::share('currentCurrency', $currency);
        View::share('supportedCurrencies', array_map(
            static fn (string $code): Currency => Currency::fromCode($code),
            Currency::supportedCodes(),
        ));
        View::share('navCategories', $this->categoryTree->activeTree());

        $cart = $this->carts->current($request);

        View::share('cartItemCount', $cart?->items()->sum('quantity') ?? 0);
        View::share('cartCurrency', $cart?->currency);

        return $next($request);
    }
}
