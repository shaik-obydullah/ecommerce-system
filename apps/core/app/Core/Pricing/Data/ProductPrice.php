<?php

declare(strict_types=1);

namespace App\Core\Pricing\Data;

use App\Core\Support\Money\Money;

/**
 * The single customer-facing price for a product in a requested currency.
 *
 * A product can be in stock but still not purchasable when no FX rate exists
 * for the requested currency.
 */
final readonly class ProductPrice
{
    public function __construct(
        public ?Money $price,
        public bool $inStock,
        public bool $purchasable,
    ) {}

    public static function unavailable(): self
    {
        return new self(null, false, false);
    }

    /**
     * @return array{amount: int|null, currency: string|null, formatted: string|null, in_stock: bool, is_purchasable: bool}
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->price?->amount(),
            'currency' => $this->price?->currency()->code(),
            'formatted' => $this->price?->format(),
            'in_stock' => $this->inStock,
            'is_purchasable' => $this->purchasable,
        ];
    }
}
