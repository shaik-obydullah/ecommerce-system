<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cart;

use App\Core\Cart\Actions\ApplyDiscountToCart;
use App\Core\Cart\Actions\RemoveDiscountFromCart;
use App\Core\Cart\Models\Cart;
use App\Core\Cart\Services\CartManager;
use App\Core\Cart\Services\TotalsCalculator;
use App\Http\Controllers\Api\V1\Cart\Requests\StoreCartDiscountRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CartDiscountController extends CartApiController
{
    public function store(
        StoreCartDiscountRequest $request,
        CartManager $carts,
        TotalsCalculator $totals,
        ApplyDiscountToCart $applyDiscount,
    ): JsonResponse {
        $cart = $this->requireCart($request, $carts);

        $cart = $applyDiscount->handle($cart, $request->code());

        return $this->respond($cart, $totals->calculate($cart), $carts, $request);
    }

    public function destroy(
        Request $request,
        string $code,
        CartManager $carts,
        TotalsCalculator $totals,
        RemoveDiscountFromCart $removeDiscount,
    ): JsonResponse {
        $cart = $this->requireCart($request, $carts);

        $cart = $removeDiscount->handle($cart, $code);

        return $this->respond($cart, $totals->calculate($cart), $carts, $request);
    }

    private function requireCart(Request $request, CartManager $carts): Cart
    {
        $cart = $carts->current($request);

        abort_unless($cart instanceof Cart, 404);

        return $cart;
    }
}
