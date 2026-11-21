<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Cart\Models\Cart;
use App\Core\Support\Money\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cart>
 */
final class CartFactory extends Factory
{
    protected $model = Cart::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'token_hash' => hash('sha256', Str::random(64)),
            'currency' => Currency::base()->code(),
            'version' => 1,
        ];
    }

    public function forUser(User $user): self
    {
        return $this->state(fn (): array => [
            'user_id' => $user->getKey(),
            'token_hash' => null,
        ]);
    }

    public function withCurrency(string $currency): self
    {
        return $this->state(fn (): array => ['currency' => $currency]);
    }
}
