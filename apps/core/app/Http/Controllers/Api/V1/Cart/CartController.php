<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cart;

use App\Core\Cart\Models\Cart;
use App\Core\Cart\Services\CartManager;
use App\Core\Cart\Services\TotalsCalculator;
use App\Core\Cart\Support\EmptyCart;
use App\Core\Support\Money\Currency;
use App\Http\Controllers\Api\V1\Cart\Requests\ShowCartRequest;
use App\Http\Controllers\Api\V1\Cart\Requests\StoreCartRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CartController extends CartApiController
{
    public function show(
        ShowCartRequest $request,
        CartManager $carts,
        TotalsCalculator $totals,
    ): JsonResponse {
        $cart = $carts->current($request);
        $explicitCurrency = $this->validatedCurrency($request);

        if (! $cart instanceof Cart) {
            $currency = $this->resolveCurrency($request, $explicitCurrency);
            $cart = EmptyCart::make($currency);
        } elseif ($explicitCurrency !== null) {
            $cart = $carts->resolve($request, Currency::fromCode($explicitCurrency));
        }

        return $this->respond($cart, $totals->calculate($cart), $carts, $request);
    }

    public function store(
        StoreCartRequest $request,
        CartManager $carts,
        TotalsCalculator $totals,
    ): JsonResponse {
        $currency = $this->resolveCurrency($request, $this->validatedCurrency($request));

        $cart = $request->shouldReplace()
            ? $carts->startNew($request, $currency)
            : $carts->resolve($request, $currency);

        return $this->respond($cart, $totals->calculate($cart), $carts, $request);
    }

    public function destroy(Request $request, CartManager $carts): JsonResponse
    {
        $carts->discard($request);

        $response = response()->json(null, 204);
        $this->forgetCartCookie($response, $carts);

        return $response;
    }

    private function validatedCurrency(ShowCartRequest|StoreCartRequest $request): ?string
    {
        $currency = $request->validated('currency');

        return is_string($currency) ? $currency : null;
    }
}
