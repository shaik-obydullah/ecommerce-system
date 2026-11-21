<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Cart\Actions\MergeGuestCart as MergeGuestCartAction;
use App\Core\Cart\Services\CartManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Merges a guest cart onto an authenticated customer on their first
 * authenticated request (including the redirect after login).
 */
final class MergeGuestCart
{
    public function __construct(
        private readonly CartManager $carts,
        private readonly MergeGuestCartAction $merge,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $guest = $this->carts->guestCart($request);

        if ($guest === null) {
            return $next($request);
        }

        $this->merge->handle($guest, $user);

        return $next($request)->withCookie($this->carts->forgetCookie());
    }
}
