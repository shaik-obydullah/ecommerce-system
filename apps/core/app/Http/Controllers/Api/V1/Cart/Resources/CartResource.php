<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Cart\Resources;

use App\Core\Cart\Data\CartTotals;
use App\Core\Cart\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public cart representation. Totals are always server-calculated and never
 * include stock lots, buy prices, COGS, or profit.
 *
 * @mixin Cart
 */
final class CartResource extends JsonResource
{
    public function __construct(
        Cart $resource,
        private readonly CartTotals $totals,
    ) {
        parent::__construct($resource);
    }

    public static function for(Cart $cart, CartTotals $totals): self
    {
        return new self($cart, $totals);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Cart $cart */
        $cart = $this->resource;

        return array_merge([
            'id' => $cart->public_id,
            'version' => $cart->version,
            'updated_at' => $cart->updated_at?->toIso8601String(),
        ], $this->totals->toArray());
    }
}
