<?php

declare(strict_types=1);

namespace App\Core\Cart\Services;

use App\Core\Cart\Exceptions\CartConflictException;
use App\Core\Cart\Models\Cart;
use App\Core\Support\Money\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Resolves and lifecycle-manages the current cart.
 *
 * Guests are identified by an opaque httpOnly cookie. Only a SHA-256 hash of
 * the token is stored in the database, so a database leak cannot be replayed
 * as a cart session.
 */
final class CartManager
{
    public const COOKIE = 'cart_token';

    private ?string $issuedToken = null;

    public function current(Request $request): ?Cart
    {
        $user = $request->user();

        if ($user !== null) {
            return Cart::query()
                ->where('user_id', $user->getAuthIdentifier())
                ->first();
        }

        $cart = $this->guestCart($request);

        return $cart;
    }

    public function guestCart(Request $request): ?Cart
    {
        $token = $request->cookie(self::COOKIE);

        if (! is_string($token) || $token === '') {
            return null;
        }

        return Cart::query()
            ->where('token_hash', $this->hashToken($token))
            ->first();
    }

    /**
     * Return the current cart in the requested currency, creating one when
     * needed. A non-empty cart keeps its locked transaction currency.
     */
    public function resolve(Request $request, Currency $currency): Cart
    {
        $cart = $this->current($request);

        if ($cart === null) {
            return $this->create($request, $currency);
        }

        if ($cart->currency === $currency->code()) {
            return $cart;
        }

        if ($cart->items()->exists()) {
            throw CartConflictException::currencyLocked(
                Currency::fromCode($cart->currency),
                $currency,
            );
        }

        $cart->update(['currency' => $currency->code()]);
        $cart->bumpVersion();

        return $cart->refresh();
    }

    /**
     * Explicitly abandon the current cart and start a fresh one. Used when a
     * shopper switches currency or asks to start over.
     */
    public function startNew(Request $request, Currency $currency): Cart
    {
        $this->discard($request);

        return $this->create($request, $currency);
    }

    public function discard(Request $request): void
    {
        $this->current($request)?->delete();
        $this->issuedToken = null;
    }

    public function issuedToken(): ?string
    {
        return $this->issuedToken;
    }

    public function makeCookie(string $token, Request $request): Cookie
    {
        $minutes = (int) config('ecommerce.cart.cookie_lifetime_minutes', 43_200);

        return cookie(
            name: self::COOKIE,
            value: $token,
            minutes: $minutes,
            path: '/',
            domain: null,
            secure: $request->isSecure(),
            httpOnly: true,
            raw: false,
            sameSite: 'lax',
        );
    }

    public function forgetCookie(): Cookie
    {
        return cookie()->forget(self::COOKIE);
    }

    private function create(Request $request, Currency $currency): Cart
    {
        $user = $request->user();

        if ($user !== null) {
            $cart = Cart::query()->create([
                'user_id' => $user->getAuthIdentifier(),
                'token_hash' => null,
                'currency' => $currency->code(),
                'version' => 1,
            ]);

            $this->issuedToken = null;

            return $cart;
        }

        $token = Str::random(64);

        $cart = Cart::query()->create([
            'user_id' => null,
            'token_hash' => $this->hashToken($token),
            'currency' => $currency->code(),
            'version' => 1,
        ]);

        $this->issuedToken = $token;

        return $cart;
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
