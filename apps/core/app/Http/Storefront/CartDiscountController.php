<?php

declare(strict_types=1);

namespace App\Http\Storefront;

use App\Core\Cart\Actions\ApplyDiscountToCart;
use App\Core\Cart\Actions\RemoveDiscountFromCart;
use App\Core\Cart\Models\Cart;
use App\Core\Cart\Services\CartManager;
use App\Http\Controllers\Controller;
use App\Http\Storefront\Requests\StoreCartDiscountRequest;
use App\Http\Support\AttachesCartCookie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CartDiscountController extends Controller
{
    use AttachesCartCookie;

    public function store(
        StoreCartDiscountRequest $request,
        CartManager $carts,
        ApplyDiscountToCart $applyDiscount,
    ): RedirectResponse {
        $cart = $carts->current($request);

        abort_unless($cart instanceof Cart, 404);

        $applyDiscount->handle($cart, $request->code());

        return $this->back($request, $carts, 'Coupon applied.');
    }

    public function destroy(
        Request $request,
        string $code,
        CartManager $carts,
        RemoveDiscountFromCart $removeDiscount,
    ): RedirectResponse {
        $cart = $carts->current($request);

        abort_unless($cart instanceof Cart, 404);

        $removeDiscount->handle($cart, $code);

        return $this->back($request, $carts, 'Coupon removed.');
    }

    private function back(Request $request, CartManager $carts, string $message): RedirectResponse
    {
        $redirect = redirect()
            ->route('storefront.cart.show')
            ->with('status', $message);

        $this->withCartCookie($redirect, $carts, $request);

        return $redirect;
    }
}
