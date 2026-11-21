<form class="currency-form" method="get" action="{{ url()->current() }}">
    @foreach (request()->except(['currency', 'page']) as $key => $value)
        @if (is_scalar($value))
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    <label class="sr-only" for="currency">Display currency</label>
    <select id="currency" name="currency" onchange="this.form.submit()">
        @foreach ($supportedCurrencies as $currency)
            <option value="{{ $currency->code() }}" @selected($currency->equals($currentCurrency))>
                {{ $currency->code() }} · {{ $currency->symbol() }}
            </option>
        @endforeach
    </select>
    <noscript><button type="submit">Update</button></noscript>
</form>
