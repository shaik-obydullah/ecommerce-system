<?php

declare(strict_types=1);

namespace App\Http\Support;

use App\Core\Cart\Services\CartManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Attaches cart-owner cookies to a response.
 *
 * Cookies are attached explicitly so they work identically on web redirects and
 * API JSON responses (the API middleware group does not run the cookie queue).
 */
trait AttachesCartCookie
{
    protected function withCartCookie(Response $response, CartManager $carts, Request $request): void
    {
        $token = $carts->issuedToken();

        if ($token !== null) {
            $response->headers->setCookie($carts->makeCookie($token, $request));
        }
    }

    protected function forgetCartCookie(Response $response, CartManager $carts): void
    {
        $response->headers->setCookie($carts->forgetCookie());
    }
}
