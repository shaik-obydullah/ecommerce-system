<?php

declare(strict_types=1);

namespace App\Http\Storefront;

use App\Core\Cart\Actions\AddItemToCart;
use App\Core\Cart\Actions\RemoveCartItem;
use App\Core\Cart\Actions\UpdateCartItem;
use App\Core\Cart\Exceptions\CartConflictException;
use App\Core\Cart\Models\Cart;
use App\Core\Cart\Models\CartItem;
use App\Core\Cart\Services\CartManager;
use App\Core\Catalog\Models\Product;
use App\Core\Pricing\Services\CurrencyContext;
use App\Http\Controllers\Controller;
use App\Http\Storefront\Requests\StoreCartItemRequest;
use App\Http\Storefront\Requests\UpdateCartItemRequest;
use App\Http\Support\AttachesCartCookie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CartItemController extends Controller
{
    use AttachesCartCookie;

    public function store(
        StoreCartItemRequest $request,
        CurrencyContext $currencyContext,
        CartManager $carts,
        AddItemToCart $addItem,
    ): RedirectResponse {
        $product = Product::query()
            ->where('public_id', $request->productId())
            ->firstOrFail();

        $currency = $currencyContext->resolve($request);

        try {
            $cart = $carts->resolve($request, $currency);
        } catch (CartConflictException $exception) {
            return $this->back($request, $carts, $exception->getMessage(), error: true);
        }

        $addItem->handle($cart, $product, $request->quantity());

        return $this->back($request, $carts, "{$product->name} was added to your cart.");
    }

    public function update(
        UpdateCartItemRequest $request,
        CartManager $carts,
        CartItem $item,
        UpdateCartItem $updateItem,
    ): RedirectResponse {
        $cart = $carts->current($request);

        abort_unless($cart instanceof Cart, 404);
        abort_unless($item->cart_id === $cart->getKey(), 404);

        try {
            $updateItem->handle($cart, $item, $request->quantity(), $request->version());
        } catch (CartConflictException $exception) {
            return $this->back($request, $carts, $exception->getMessage(), error: true);
        }

        return $this->back($request, $carts, 'Cart updated.');
    }

    public function destroy(Request $request, CartManager $carts, CartItem $item, RemoveCartItem $removeItem): RedirectResponse
    {
        $cart = $carts->current($request);

        abort_unless($cart instanceof Cart, 404);
        abort_unless($item->cart_id === $cart->getKey(), 404);

        $removeItem->handle($cart, $item);

        return $this->back($request, $carts, 'Item removed from your cart.');
    }

    private function back(Request $request, CartManager $carts, string $message, bool $error = false): RedirectResponse
    {
        $redirect = redirect()
            ->back(fallback: route('storefront.cart.show'))
            ->with($error ? 'error' : 'status', $message);

        $this->withCartCookie($redirect, $carts, $request);

        return $redirect;
    }
}
