<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cart;

use App\Core\Cart\Data\CartTotals;
use App\Core\Cart\Models\Cart;
use App\Core\Cart\Services\CartManager;
use App\Core\Cart\Services\TotalsCalculator;
use App\Http\Controllers\Api\V1\Cart\Resources\CartResource;
use App\Http\Controllers\Api\V1\Support\ResolvesApiCurrency;
use App\Http\Controllers\Controller;
use App\Http\Support\AttachesCartCookie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class CartApiController extends Controller
{
    use AttachesCartCookie;
    use ResolvesApiCurrency;

    /**
     * Resolve the current cart, creating one in the request currency when the
     * client has not created a cart yet.
     */
    protected function cartFor(
        Request $request,
        CartManager $carts,
        TotalsCalculator $totals,
    ): JsonResponse {
        $cart = $carts->current($request)
            ?? $carts->resolve($request, $this->resolveCurrency($request));

        return $this->respond($cart, $totals->calculate($cart), $carts, $request);
    }

    protected function respond(
        Cart $cart,
        CartTotals $totals,
        CartManager $carts,
        Request $request,
    ): JsonResponse {
        $response = CartResource::for($cart, $totals)->response($request);
        $response->setStatusCode(200);

        $this->withCartCookie($response, $carts, $request);

        return $response;
    }
}
