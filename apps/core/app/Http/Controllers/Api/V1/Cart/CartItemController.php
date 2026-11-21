<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cart;

use App\Core\Cart\Actions\AddItemToCart;
use App\Core\Cart\Actions\RemoveCartItem;
use App\Core\Cart\Actions\UpdateCartItem;
use App\Core\Cart\Models\Cart;
use App\Core\Cart\Models\CartItem;
use App\Core\Cart\Services\CartManager;
use App\Core\Cart\Services\TotalsCalculator;
use App\Core\Catalog\Models\Product;
use App\Http\Controllers\Api\V1\Cart\Requests\StoreCartItemRequest;
use App\Http\Controllers\Api\V1\Cart\Requests\UpdateCartItemRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CartItemController extends CartApiController
{
    public function store(
        StoreCartItemRequest $request,
        CartManager $carts,
        TotalsCalculator $totals,
        AddItemToCart $addItem,
    ): JsonResponse {
        $product = Product::query()
            ->where('public_id', $request->productId())
            ->firstOrFail();

        $cart = $carts->current($request)
            ?? $carts->resolve($request, $this->resolveCurrency($request));

        $addItem->handle($cart, $product, $request->quantity());

        return $this->respond($cart->refresh(), $totals->calculate($cart), $carts, $request);
    }

    public function update(
        UpdateCartItemRequest $request,
        string $item,
        CartManager $carts,
        TotalsCalculator $totals,
        UpdateCartItem $updateItem,
    ): JsonResponse {
        $cart = $this->requireCart($request, $carts);
        $cartItem = $this->requireItem($cart, $item);

        $updateItem->handle($cart, $cartItem, $request->quantity(), $request->version());

        return $this->respond($cart->refresh(), $totals->calculate($cart), $carts, $request);
    }

    public function destroy(
        Request $request,
        string $item,
        CartManager $carts,
        TotalsCalculator $totals,
        RemoveCartItem $removeItem,
    ): JsonResponse {
        $cart = $this->requireCart($request, $carts);
        $cartItem = $this->requireItem($cart, $item);

        $removeItem->handle($cart, $cartItem);

        return $this->respond($cart->refresh(), $totals->calculate($cart), $carts, $request);
    }

    private function requireCart(Request $request, CartManager $carts): Cart
    {
        $cart = $carts->current($request);

        abort_unless($cart instanceof Cart, 404);

        return $cart;
    }

    private function requireItem(Cart $cart, string $publicId): CartItem
    {
        $item = CartItem::query()
            ->where('public_id', $publicId)
            ->firstOrFail();

        abort_unless($item->cart_id === $cart->getKey(), 404);

        return $item;
    }
}
