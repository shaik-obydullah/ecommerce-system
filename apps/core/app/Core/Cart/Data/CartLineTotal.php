<?php

declare(strict_types=1);

namespace App\Core\Cart\Data;

use App\Core\Cart\Models\CartItem;
use App\Core\Catalog\Models\Product;
use App\Core\Support\Money\Money;

/**
 * The calculated totals for one cart line.
 *
 * `subtotal` is the current derived price multiplied by the requested
 * quantity. Unavailable lines carry an explanation instead of a purchasable
 * price.
 */
final readonly class CartLineTotal
{
    public function __construct(
        public CartItem $item,
        public Product $product,
        public int $quantity,
        public ?Money $unitPrice,
        public Money $subtotal,
        public Money $discount,
        public Money $total,
        public int $availableQuantity,
        public bool $purchasable,
        public ?string $unavailableReason,
    ) {}

    /**
     * @return array{
     *     id: string,
     *     product: array{id: string, name: string, slug: string},
     *     quantity: int,
     *     unit_price: array{amount: int, currency: string, formatted: string}|null,
     *     subtotal: array{amount: int, currency: string, formatted: string},
     *     discount: array{amount: int, currency: string, formatted: string},
     *     total: array{amount: int, currency: string, formatted: string},
     *     available_quantity: int,
     *     in_stock: bool,
     *     is_purchasable: bool,
     *     message: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->item->public_id,
            'product' => [
                'id' => $this->product->public_id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
            ],
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice?->toArray(),
            'subtotal' => $this->subtotal->toArray(),
            'discount' => $this->discount->toArray(),
            'total' => $this->total->toArray(),
            'available_quantity' => $this->availableQuantity,
            'in_stock' => $this->availableQuantity > 0,
            'is_purchasable' => $this->purchasable,
            'message' => $this->unavailableReason,
        ];
    }
}
