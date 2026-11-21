<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('meta_description', $storeName.' storefront')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <title>@yield('title', $storeName)</title>
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}">
    @stack('head')
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>

    <header class="site-header">
        <div class="site-header__top">
            <a class="brand" href="{{ route('storefront.home') }}">
                <span class="brand__mark" aria-hidden="true">{{ mb_substr($storeName, 0, 1) }}</span>
                <span class="brand__name">{{ $storeName }}</span>
            </a>

            <form class="search-form" method="get" action="{{ route('storefront.search') }}" role="search">
                <label class="sr-only" for="q">Search products</label>
                <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Search products…" autocomplete="off">
                <button type="submit">Search</button>
            </form>

            @include('storefront.partials.currency-switcher')

            <a class="cart-link" href="{{ route('storefront.cart.show') }}">
                Cart
                <span class="cart-link__count" aria-label="{{ $cartItemCount }} items in cart">{{ $cartItemCount }}</span>
            </a>
        </div>

        <nav class="site-nav" aria-label="Categories">
            <a class="site-nav__link" href="{{ route('storefront.home') }}">Home</a>

            @foreach ($navCategories as $category)
                <a class="site-nav__link" href="{{ route('storefront.categories.show', ['category' => $category['slug']]) }}">
                    {{ $category['name'] }}
                    <span class="site-nav__count">{{ $category['product_count'] }}</span>
                </a>

                @foreach ($category['children'] as $child)
                    <a class="site-nav__link site-nav__link--child" href="{{ route('storefront.categories.show', ['category' => $child['slug']]) }}">
                        {{ $child['name'] }}
                    </a>
                @endforeach
            @endforeach
        </nav>
    </header>

    @if (session('status'))
        <div class="flash flash--success" role="status">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="flash flash--error" role="alert">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="flash flash--error" role="alert">
            <ul class="flash__list">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <main id="main" class="site-main">
        @yield('content')
    </main>

    <footer class="site-footer">
        <p>{{ $storeName }} · Prices shown in {{ $currentCurrency->code() }}. One price per product, derived from available stock.</p>
        <nav aria-label="Footer links">
            <a href="{{ route('storefront.search') }}">All products</a>
            <a href="/api/v1/products">Catalog API</a>
            <a href="/admin">Admin</a>
        </nav>
    </footer>
</body>
</html>
