<?php

declare(strict_types=1);

namespace App\Core\Cart\Actions;

use App\Core\Cart\Models\Cart;
use App\Core\Cart\Models\CartItem;
use App\Core\Cart\Services\CartAvailability;
use App\Core\Catalog\Models\Product;
use App\Core\Support\Money\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Adds a product to a cart, or increases its quantity.
 *
 * The quantity is validated against total available stock (existing line
 * quantity included). Cart-stage races are acceptable because checkout
 * revalidates and locks stock again.
 */
final class AddItemToCart
{
    public function __construct(
        private readonly CartAvailability $availability,
    ) {}

    public function handle(Cart $cart, Product $product, int $quantity): CartItem
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Choose at least one unit.',
            ]);
        }

        return DB::transaction(function () use ($cart, $product, $quantity): CartItem {
            $product = Product::query()
                ->withStockSummary()
                ->lockForUpdate()
                ->findOrFail($product->getKey());

            /** @var CartItem|null $existing */
            $existing = $cart->items()
                ->where('product_id', $product->getKey())
                ->lockForUpdate()
                ->first();

            $alreadyInCart = $existing instanceof CartItem ? $existing->quantity : 0;

            $this->availability->assertAddable(
                $product,
                $quantity,
                Currency::fromCode($cart->currency),
                $alreadyInCart,
            );

            if ($existing instanceof CartItem) {
                $existing->update(['quantity' => $alreadyInCart + $quantity]);
                $item = $existing;
            } else {
                $item = $cart->items()->create([
                    'product_id' => $product->getKey(),
                    'quantity' => $quantity,
                ]);
            }

            $cart->bumpVersion();

            return $item->refresh();
        });
    }
}
