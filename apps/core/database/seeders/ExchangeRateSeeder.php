<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Pricing\Models\ExchangeRate;
use App\Core\Support\Money\Currency;
use Illuminate\Database\Seeder;

final class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        $base = Currency::base()->code();
        $fetchedAt = now()->startOfDay();

        $rates = [
            'EUR' => '0.9200000000',
            'GBP' => '0.7900000000',
            'CAD' => '1.3600000000',
            'AUD' => '1.5200000000',
            'INR' => '83.2000000000',
            'JPY' => '150.0000000000',
            'KWD' => '0.3100000000',
        ];

        foreach ($rates as $quote => $rate) {
            if ($quote === $base) {
                continue;
            }

            ExchangeRate::query()->updateOrCreate(
                [
                    'base_currency' => $base,
                    'quote_currency' => $quote,
                    'fetched_at' => $fetchedAt,
                ],
                [
                    'rate' => $rate,
                    'source' => 'seed',
                ],
            );
        }
    }
}
