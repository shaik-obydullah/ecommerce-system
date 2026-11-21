<?php

declare(strict_types=1);

namespace App\Core\Cart\Actions;

use App\Core\Cart\Exceptions\CartConflictException;
use App\Core\Cart\Models\Cart;
use App\Core\Cart\Models\CartItem;
use App\Core\Cart\Services\CartAvailability;
use App\Core\Catalog\Models\Product;
use App\Core\Support\Money\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sets the quantity of a cart line.
 *
 * When the client supplies the cart version it must match the current version,
 * preventing lost updates from two open tabs.
 */
final class UpdateCartItem
{
    public function __construct(
        private readonly CartAvailability $availability,
    ) {}

    public function handle(Cart $cart, CartItem $item, int $quantity, ?int $expectedVersion = null): CartItem
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Choose at least one unit.',
            ]);
        }

        return DB::transaction(function () use ($cart, $item, $quantity, $expectedVersion): CartItem {
            /** @var Cart $lockedCart */
            $lockedCart = Cart::query()->lockForUpdate()->findOrFail($cart->getKey());

            if ($item->cart_id !== $lockedCart->getKey()) {
                throw ValidationException::withMessages([
                    'quantity' => 'This item does not belong to your cart.',
                ]);
            }

            if ($expectedVersion !== null && $expectedVersion !== $lockedCart->version) {
                throw CartConflictException::versionConflict();
            }

            /** @var CartItem $lockedItem */
            $lockedItem = CartItem::query()->lockForUpdate()->findOrFail($item->getKey());

            $product = Product::query()
                ->withStockSummary()
                ->findOrFail($lockedItem->product_id);

            $this->availability->assertAddable(
                $product,
                $quantity,
                Currency::fromCode($lockedCart->currency),
            );

            $lockedItem->update(['quantity' => $quantity]);
            $lockedCart->bumpVersion();
            $cart->setAttribute('version', $lockedCart->version);

            return $lockedItem->refresh();
        });
    }
}
