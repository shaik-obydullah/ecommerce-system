<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Pricing\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExchangeRate>
 */
final class ExchangeRateFactory extends Factory
{
    protected $model = ExchangeRate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'base_currency' => 'USD',
            'quote_currency' => 'EUR',
            'rate' => '0.9200000000',
            'source' => 'factory',
            'fetched_at' => now(),
        ];
    }
}
