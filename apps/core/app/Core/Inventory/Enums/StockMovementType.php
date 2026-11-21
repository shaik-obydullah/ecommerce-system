<?php

declare(strict_types=1);

namespace App\Core\Inventory\Enums;

enum StockMovementType: string
{
    case Purchase = 'purchase';
    case Adjustment = 'adjustment';
    case PriceChange = 'price_change';
    case Sale = 'sale';
    case Return = 'return';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Purchase',
            self::Adjustment => 'Adjustment',
            self::PriceChange => 'Price change',
            self::Sale => 'Sale',
            self::Return => 'Return',
        };
    }
}
