<?php

declare(strict_types=1);

namespace App\Core\Cart\Data;

use App\Core\Promotions\Data\DiscountQuote;
use App\Core\Support\Money\Currency;
use App\Core\Support\Money\Money;

/**
 * The complete, server-calculated cart breakdown shared by the Blade
 * storefront and the API.
 */
final readonly class CartTotals
{
    /**
     * @param  list<CartLineTotal>  $lines
     */
    public function __construct(
        public Currency $currency,
        public array $lines,
        public Money $subtotal,
        public Money $discount,
        public ?DiscountQuote $discountQuote,
        public Money $shipping,
        public Money $tax,
        public Money $grandTotal,
        public bool $isPurchasable,
    ) {}

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    public function itemCount(): int
    {
        return (int) array_sum(array_map(
            static fn (CartLineTotal $line): int => $line->quantity,
            $this->lines,
        ));
    }

    /**
     * @return array{
     *     currency: string,
     *     item_count: int,
     *     items: list<array{
     *         id: string,
     *         product: array{id: string, name: string, slug: string},
     *         quantity: int,
     *         unit_price: array{amount: int, currency: string, formatted: string}|null,
     *         subtotal: array{amount: int, currency: string, formatted: string},
     *         discount: array{amount: int, currency: string, formatted: string},
     *         total: array{amount: int, currency: string, formatted: string},
     *         available_quantity: int,
     *         in_stock: bool,
     *         is_purchasable: bool,
     *         message: string|null
     *     }>,
     *     discount: array{code: string, amount: array{amount: int, currency: string, formatted: string}, valid: bool, message: string|null}|null,
     *     totals: array{
     *         subtotal: array{amount: int, currency: string, formatted: string},
     *         discount_total: array{amount: int, currency: string, formatted: string},
     *         shipping_total: array{amount: int, currency: string, formatted: string},
     *         tax_total: array{amount: int, currency: string, formatted: string},
     *         grand_total: array{amount: int, currency: string, formatted: string}
     *     },
     *     is_purchasable: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'currency' => $this->currency->code(),
            'item_count' => $this->itemCount(),
            'items' => array_map(
                static fn (CartLineTotal $line): array => $line->toArray(),
                $this->lines,
            ),
            'discount' => $this->discountQuote?->toArray(),
            'totals' => [
                'subtotal' => $this->subtotal->toArray(),
                'discount_total' => $this->discount->toArray(),
                'shipping_total' => $this->shipping->toArray(),
                'tax_total' => $this->tax->toArray(),
                'grand_total' => $this->grandTotal->toArray(),
            ],
            'is_purchasable' => $this->isPurchasable,
        ];
    }
}
