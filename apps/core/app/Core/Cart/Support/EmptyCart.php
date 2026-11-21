<?php

declare(strict_types=1);

namespace App\Core\Cart\Support;

use App\Core\Cart\Models\Cart;
use App\Core\Support\Money\Currency;
use Illuminate\Database\Eloquent\Collection;

/**
 * An unsaved, empty cart used to render totals before the shopper has added
 * anything. It is never persisted and carries no owner token.
 */
final class EmptyCart
{
    public static function make(Currency $currency): Cart
    {
        $cart = new Cart([
            'currency' => $currency->code(),
            'version' => 0,
        ]);

        $cart->setRelation('items', new Collection);
        $cart->setRelation('discount', null);

        return $cart;
    }
}
