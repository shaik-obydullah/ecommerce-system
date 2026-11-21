<?php

declare(strict_types=1);

namespace App\Http\Storefront;

use App\Core\Cart\Services\CartManager;
use App\Core\Cart\Services\TotalsCalculator;
use App\Core\Cart\Support\EmptyCart;
use App\Core\Pricing\Services\CurrencyContext;
use App\Core\Support\Money\Currency;
use App\Http\Controllers\Controller;
use App\Http\Support\AttachesCartCookie;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class CartController extends Controller
{
    use AttachesCartCookie;

    public function show(
        Request $request,
        CurrencyContext $currencyContext,
        CartManager $carts,
        TotalsCalculator $totals,
    ): View {
        $displayCurrency = $currencyContext->resolve($request);
        $cart = $carts->current($request) ?? EmptyCart::make($displayCurrency);

        return view('storefront.cart.show', [
            'cart' => $cart,
            'totals' => $totals->calculate($cart),
            'displayCurrency' => $displayCurrency,
        ]);
    }

    public function startNew(
        Request $request,
        CurrencyContext $currencyContext,
        CartManager $carts,
    ): RedirectResponse {
        $validated = $request->validate([
            'currency' => ['sometimes', 'string', 'size:3', Rule::in(Currency::supportedCodes())],
        ]);

        $requested = $validated['currency'] ?? null;
        $currency = is_string($requested)
            ? Currency::fromCode($requested)
            : $currencyContext->resolve($request);

        $carts->startNew($request, $currency);

        return $this->redirectToCart($request, $carts)
            ->with('status', "Started a new cart in {$currency->code()}.");
    }

    public function destroy(Request $request, CartManager $carts): RedirectResponse
    {
        $carts->discard($request);

        $response = redirect()->route('storefront.cart.show')->with('status', 'Cart cleared.');
        $this->forgetCartCookie($response, $carts);

        return $response;
    }

    private function redirectToCart(Request $request, CartManager $carts): RedirectResponse
    {
        $redirect = redirect()->route('storefront.cart.show');
        $this->withCartCookie($redirect, $carts, $request);

        return $redirect;
    }
}
