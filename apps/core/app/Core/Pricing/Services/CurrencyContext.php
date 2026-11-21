<?php

declare(strict_types=1);

namespace App\Core\Pricing\Services;

use App\Core\Support\Money\Currency;
use Illuminate\Http\Request;

/**
 * Resolves the storefront's display currency from the query string or session.
 *
 * Invalid values fall back to the configured default for the Blade storefront.
 * API requests validate the currency explicitly instead of using this class.
 */
final class CurrencyContext
{
    public function resolve(Request $request): Currency
    {
        $requested = $request->query('currency');

        if (is_string($requested) && Currency::isSupported($requested)) {
            $currency = Currency::fromCode($requested);

            if ($request->hasSession()) {
                $request->session()->put('currency', $currency->code());
            }

            return $currency;
        }

        $stored = $request->hasSession() ? $request->session()->get('currency') : null;

        if (is_string($stored) && Currency::isSupported($stored)) {
            return Currency::fromCode($stored);
        }

        return Currency::default();
    }
}
