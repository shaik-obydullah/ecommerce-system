<?php

declare(strict_types=1);

namespace App\Core\Promotions\Enums;

use InvalidArgumentException;

/**
 * The supported coupon shapes for the MVP.
 *
 * Percentage values are stored in basis points (1000 = 10%). Fixed values are
 * stored as integer minor units in the coupon's currency.
 */
enum DiscountType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Percentage',
            self::Fixed => 'Fixed amount',
        };
    }

    public function isPercentage(): bool
    {
        return $this === self::Percentage;
    }

    public function isFixed(): bool
    {
        return $this === self::Fixed;
    }

    /**
     * Convert a human-entered percentage (for example "10" or "12.5") into
     * basis points without floating-point arithmetic.
     */
    public static function percentageToBasisPoints(string $percentage): int
    {
        if (preg_match('/^\d+(\.\d{1,2})?$/', trim($percentage)) !== 1) {
            throw new InvalidArgumentException("Invalid percentage [{$percentage}].");
        }

        $scaled = bcmul(trim($percentage), '100', 0);

        return (int) $scaled;
    }
}
