<?php

declare(strict_types=1);

namespace App\Core\Cart\Actions;

use App\Core\Cart\Models\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Detaches the cart's coupon. Removing an unknown coupon is a no-op so the
 * operation stays idempotent.
 */
final class RemoveDiscountFromCart
{
    public function handle(Cart $cart, string $code): Cart
    {
        $code = Str::upper(trim($code));

        DB::transaction(function () use ($cart, $code): void {
            $deleted = $cart->discount()
                ->where('code', $code)
                ->delete();

            if ($deleted > 0) {
                $cart->bumpVersion();
            }
        });

        return $cart->refresh()->load('discount.discount');
    }
}
