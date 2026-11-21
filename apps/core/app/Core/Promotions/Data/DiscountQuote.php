<?php

declare(strict_types=1);

namespace App\Core\Promotions\Data;

use App\Core\Support\Money\Money;

/**
 * The result of validating and pricing a coupon against a cart subtotal.
 */
final readonly class DiscountQuote
{
    public function __construct(
        public string $code,
        public Money $amount,
        public bool $valid,
        public ?string $message = null,
    ) {}

    public static function invalid(string $code, Money $zero, string $message): self
    {
        return new self($code, $zero, false, $message);
    }

    /**
     * @return array{code: string, amount: array{amount: int, currency: string, formatted: string}, valid: bool, message: string|null}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'amount' => $this->amount->toArray(),
            'valid' => $this->valid,
            'message' => $this->message,
        ];
    }
}
