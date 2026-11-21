<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Support;

use App\Core\Support\Money\Currency;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Resolves the request currency for cart endpoints.
 *
 * Precedence: explicit body/query value, X-Currency header, configured default.
 * Invalid currencies are rejected — never silently replaced.
 */
trait ResolvesApiCurrency
{
    protected function resolveCurrency(Request $request, ?string $explicit = null): Currency
    {
        $candidate = $explicit ?? $request->query('currency') ?? $request->header('X-Currency');

        if ($candidate === null || $candidate === '') {
            return Currency::default();
        }

        if (! is_string($candidate) || ! Currency::isSupported($candidate)) {
            throw ValidationException::withMessages([
                'currency' => 'The requested currency is not supported.',
            ]);
        }

        return Currency::fromCode($candidate);
    }
}
