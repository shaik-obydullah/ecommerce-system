<?php

declare(strict_types=1);

namespace App\Core\Cart\Actions;

use App\Core\Cart\Models\Cart;
use App\Core\Cart\Models\CartItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Removes a product line from the cart.
 */
final class RemoveCartItem
{
    public function handle(Cart $cart, CartItem $item): void
    {
        DB::transaction(function () use ($cart, $item): void {
            if ($item->cart_id !== $cart->getKey()) {
                throw ValidationException::withMessages([
                    'item' => 'This item does not belong to your cart.',
                ]);
            }

            $item->delete();

            $cart->bumpVersion();
        });
    }
}
