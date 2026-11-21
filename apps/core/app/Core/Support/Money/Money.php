<?php

declare(strict_types=1);

namespace App\Core\Support\Money;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * An immutable money value object: an integer amount in minor units plus an
 * ISO 4217 currency.
 *
 * Never store or calculate money as a float. Use fromDecimal() only at the
 * boundary where a human-entered decimal must be converted.
 */
final class Money implements JsonSerializable, Stringable
{
    public function __construct(
        private readonly int $amount,
        private readonly Currency $currency,
    ) {}

    public static function of(int $amount, Currency|string $currency): self
    {
        return new self($amount, self::resolveCurrency($currency));
    }

    public static function zero(Currency|string $currency): self
    {
        return self::of(0, $currency);
    }

    /**
     * Convert a decimal string such as "12.34" to minor units.
     *
     * Floats are accepted for convenience but converted through their shortest
     * decimal representation. Prefer passing a string.
     */
    public static function fromDecimal(string|int|float $amount, Currency|string $currency): self
    {
        $currency = self::resolveCurrency($currency);
        $decimal = is_float($amount) ? (string) $amount : (string) $amount;

        if (preg_match('/^-?\d+(\.\d+)?$/', $decimal) !== 1) {
            throw new InvalidArgumentException("Invalid decimal amount [{$decimal}].");
        }

        $scale = $currency->minorUnits();
        $scaled = bcmul($decimal, bcpow('10', (string) $scale, 0), $scale + 2);

        $rounded = bccomp($scaled, '0', $scale + 2) < 0
            ? bcsub($scaled, '0.5', 0)
            : bcadd($scaled, '0.5', 0);

        return new self((int) $rounded, $currency);
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(int $quantity): self
    {
        return new self($this->amount * $quantity, $this->currency);
    }

    public function negate(): self
    {
        return new self(-$this->amount, $this->currency);
    }

    public function abs(): self
    {
        return new self($this->amount < 0 ? -$this->amount : $this->amount, $this->currency);
    }

    /**
     * Split this amount into the given number of parts without losing a minor
     * unit. The remainder is distributed one unit at a time from the first part.
     *
     * @return list<self>
     */
    public function allocate(int $parts): array
    {
        if ($parts < 1) {
            throw new InvalidArgumentException('Cannot allocate money into fewer than one part.');
        }

        $base = intdiv($this->amount, $parts);
        $remainder = $this->amount % $parts;
        $step = $remainder <=> 0;

        $allocations = [];

        for ($index = 0; $index < $parts; $index++) {
            $allocations[] = new self(
                $base + ($index < abs($remainder) ? $step : 0),
                $this->currency,
            );
        }

        return $allocations;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    public function equals(self $other): bool
    {
        return $this->currency->equals($other->currency) && $this->amount === $other->amount;
    }

    /**
     * @return int -1, 0 or 1
     */
    public function compareTo(self $other): int
    {
        $this->assertSameCurrency($other);

        return $this->amount <=> $other->amount;
    }

    public function format(): string
    {
        return $this->currency->format($this->amount);
    }

    /**
     * Return the amount as a plain decimal string, without symbols or grouping.
     *
     * This is the safe representation for arithmetic with bcmath and for
     * converting a stored minor-unit amount back into a human-editable value.
     */
    public function toDecimalString(): string
    {
        $negative = $this->amount < 0;
        $absolute = $negative ? -$this->amount : $this->amount;
        $divisor = 10 ** $this->currency->minorUnits();
        $whole = intdiv($absolute, $divisor);
        $fraction = $absolute % $divisor;

        $decimal = (string) $whole;

        if ($this->currency->minorUnits() > 0) {
            $decimal .= '.'.str_pad((string) $fraction, $this->currency->minorUnits(), '0', STR_PAD_LEFT);
        }

        return ($negative ? '-' : '').$decimal;
    }

    /**
     * @return array{amount: int, currency: string, formatted: string}
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency->code(),
            'formatted' => $this->format(),
        ];
    }

    /**
     * @return array{amount: int, currency: string, formatted: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private function assertSameCurrency(self $other): void
    {
        if (! $this->currency->equals($other->currency)) {
            throw new InvalidArgumentException(
                "Cannot operate on different currencies [{$this->currency->code()}] and [{$other->currency->code()}].",
            );
        }
    }

    private static function resolveCurrency(Currency|string $currency): Currency
    {
        return $currency instanceof Currency ? $currency : Currency::fromCode($currency);
    }
}
