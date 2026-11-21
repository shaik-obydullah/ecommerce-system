<?php

declare(strict_types=1);

namespace App\Core\Support\Money;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * An ISO 4217 currency with the formatting metadata needed by the storefront.
 *
 * Currencies are immutable and validated against config/ecommerce.php.
 */
final class Currency implements JsonSerializable, Stringable
{
    /** @var array<string, self> */
    private static array $instances = [];

    private function __construct(
        private readonly string $code,
        private readonly int $minorUnits,
        private readonly string $symbol,
        private readonly string $symbolPosition,
        private readonly string $decimalSeparator,
        private readonly string $thousandsSeparator,
    ) {}

    public static function fromCode(string $code): self
    {
        $code = strtoupper(trim($code));

        if (preg_match('/^[A-Z]{3}$/', $code) !== 1) {
            throw new InvalidArgumentException("Invalid ISO 4217 currency code [{$code}].");
        }

        if (isset(self::$instances[$code])) {
            return self::$instances[$code];
        }

        /** @var array<string, array{minor_units: int, symbol: string, symbol_position: string, decimal_separator: string, thousands_separator: string}> $currencies */
        $currencies = config('ecommerce.currencies', []);

        if (! isset($currencies[$code])) {
            throw new InvalidArgumentException("Unsupported currency [{$code}].");
        }

        $metadata = $currencies[$code];

        return self::$instances[$code] = new self(
            code: $code,
            minorUnits: (int) $metadata['minor_units'],
            symbol: (string) $metadata['symbol'],
            symbolPosition: (string) $metadata['symbol_position'],
            decimalSeparator: (string) $metadata['decimal_separator'],
            thousandsSeparator: (string) $metadata['thousands_separator'],
        );
    }

    public static function base(): self
    {
        return self::fromCode((string) config('ecommerce.base_currency', 'USD'));
    }

    public static function default(): self
    {
        return self::fromCode((string) config('ecommerce.default_currency', 'USD'));
    }

    /**
     * @return list<string>
     */
    public static function supportedCodes(): array
    {
        /** @var list<string> $codes */
        $codes = config('ecommerce.supported_currencies', []);

        return array_values(array_map(static fn (string $code): string => strtoupper($code), $codes));
    }

    public static function isSupported(string $code): bool
    {
        return in_array(strtoupper(trim($code)), self::supportedCodes(), true);
    }

    public function code(): string
    {
        return $this->code;
    }

    public function minorUnits(): int
    {
        return $this->minorUnits;
    }

    public function symbol(): string
    {
        return $this->symbol;
    }

    public function symbolPosition(): string
    {
        return $this->symbolPosition;
    }

    public function decimalSeparator(): string
    {
        return $this->decimalSeparator;
    }

    public function thousandsSeparator(): string
    {
        return $this->thousandsSeparator;
    }

    /**
     * Format an integer minor-unit amount using this currency's metadata.
     *
     * The whole part is formatted as an integer, never as a float, so large
     * amounts remain exact.
     */
    public function format(int $amount): string
    {
        $negative = $amount < 0;
        $absolute = $negative ? -$amount : $amount;
        $divisor = 10 ** $this->minorUnits;
        $whole = intdiv($absolute, $divisor);
        $fraction = $absolute % $divisor;

        $number = number_format($whole, 0, $this->decimalSeparator, $this->thousandsSeparator);

        if ($this->minorUnits > 0) {
            $number .= $this->decimalSeparator.str_pad((string) $fraction, $this->minorUnits, '0', STR_PAD_LEFT);
        }

        $sign = $negative ? '-' : '';

        return $this->symbolPosition === 'after'
            ? $sign.$number.' '.$this->symbol
            : $sign.$this->symbol.$number;
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }

    /**
     * @return array{code: string, minor_units: int, symbol: string}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'minor_units' => $this->minorUnits,
            'symbol' => $this->symbol,
        ];
    }

    /**
     * @return array{code: string, minor_units: int, symbol: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return $this->code;
    }

    /**
     * Clear the in-process cache. Intended for tests that mutate config.
     */
    public static function flush(): void
    {
        self::$instances = [];
    }
}
