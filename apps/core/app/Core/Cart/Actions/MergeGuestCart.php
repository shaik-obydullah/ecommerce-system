<?php

declare(strict_types=1);

namespace App\Core\Cart\Actions;

use App\Core\Cart\Models\Cart;
use App\Core\Cart\Models\CartItem;
use App\Core\Catalog\Models\Product;
use App\Core\Support\Money\Currency;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Moves a guest cart onto an authenticated customer.
 *
 * If the customer already has a cart, quantities are merged up to available
 * stock and the guest cart is deleted. The customer cart keeps its locked
 * currency; prices are recalculated from stock lots after the merge.
 */
final class MergeGuestCart
{
    public function handle(Cart $guest, User $user): ?Cart
    {
        if ($guest->user_id !== null) {
            return null;
        }

        return DB::transaction(function () use ($guest, $user): ?Cart {
            /** @var Cart $guest */
            $guest = Cart::query()->lockForUpdate()->findOrFail($guest->getKey());

            if ($guest->user_id !== null) {
                return null;
            }

            /** @var Cart|null $userCart */
            $userCart = Cart::query()
                ->where('user_id', $user->getAuthIdentifier())
                ->lockForUpdate()
                ->first();

            if (! $userCart instanceof Cart) {
                $guest->update([
                    'user_id' => $user->getAuthIdentifier(),
                    'token_hash' => null,
                ]);
                $guest->bumpVersion();

                return $guest->refresh();
            }

            $guest->load(['items', 'discount.discount']);
            $currency = Currency::fromCode($userCart->currency);

            foreach ($guest->items as $guestItem) {
                $product = Product::query()
                    ->withStockSummary()
                    ->find($guestItem->product_id);

                if (! $product instanceof Product) {
                    continue;
                }

                $existing = $userCart->items()
                    ->where('product_id', $product->getKey())
                    ->first();

                $target = min(
                    $product->derivedAvailableQty(),
                    ($existing instanceof CartItem ? $existing->quantity : 0) + $guestItem->quantity,
                );

                if ($target < 1) {
                    continue;
                }

                $userCart->items()->updateOrCreate(
                    ['product_id' => $product->getKey()],
                    ['quantity' => $target],
                );
            }

            if ($guest->discount !== null && ! $userCart->discount()->exists()) {
                $discount = $guest->discount->discount;

                if ($discount->isAvailableInCurrency($currency)) {
                    $userCart->discount()->create([
                        'discount_id' => $discount->getKey(),
                        'code' => $discount->code,
                    ]);
                }
            }

            $userCart->bumpVersion();
            $guest->delete();

            return $userCart->refresh();
        });
    }
}
