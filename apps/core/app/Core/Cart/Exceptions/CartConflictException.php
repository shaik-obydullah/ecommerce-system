<?php

declare(strict_types=1);

namespace App\Core\Cart\Exceptions;

use App\Core\Support\Money\Currency;
use RuntimeException;

/**
 * A cart mutation that conflicts with the cart's current state.
 *
 * Rendered as an RFC 9457 problem response with HTTP 409 by the API exception
 * handler.
 */
final class CartConflictException extends RuntimeException
{
    private function __construct(
        string $message,
        private readonly string $problemType,
    ) {
        parent::__construct($message);
    }

    public static function currencyLocked(Currency $cartCurrency, Currency $requested): self
    {
        return new self(
            "This cart is locked to {$cartCurrency->code()}. Start a new cart to shop in {$requested->code()}.",
            'https://ecommerce.local/problems/cart-currency-locked',
        );
    }

    public static function versionConflict(): self
    {
        return new self(
            'This cart was updated by another request. Refresh and try again.',
            'https://ecommerce.local/problems/cart-version-conflict',
        );
    }

    public function problemType(): string
    {
        return $this->problemType;
    }
}
