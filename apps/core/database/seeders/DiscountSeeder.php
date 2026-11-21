<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Promotions\Enums\DiscountType;
use App\Core\Promotions\Models\Discount;
use App\Core\Support\Money\Currency;
use Illuminate\Database\Seeder;

final class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        $base = Currency::base();

        $coupons = [
            [
                'code' => 'WELCOME10',
                'name' => 'Welcome 10% off',
                'type' => DiscountType::Percentage,
                'value' => 1_000,
                'currency' => null,
                'min_subtotal_amount' => null,
                'usage_limit' => null,
                'per_user_limit' => 1,
            ],
            [
                'code' => 'SAVE20',
                'name' => '20% off orders over $100',
                'type' => DiscountType::Percentage,
                'value' => 2_000,
                'currency' => null,
                'min_subtotal_amount' => 10_000,
                'usage_limit' => null,
                'per_user_limit' => null,
            ],
            [
                'code' => 'TAKE5',
                'name' => '$5 off',
                'type' => DiscountType::Fixed,
                'value' => 500,
                'currency' => $base->code(),
                'min_subtotal_amount' => 2_500,
                'usage_limit' => 100,
                'per_user_limit' => 1,
            ],
        ];

        foreach ($coupons as $coupon) {
            Discount::query()->updateOrCreate(
                ['code' => $coupon['code']],
                array_merge($coupon, [
                    'is_active' => true,
                    'starts_at' => null,
                    'ends_at' => null,
                    'usage_count' => 0,
                ]),
            );
        }
    }
}
