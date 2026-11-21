@extends('storefront.layouts.app')

@section('title', $storeName)
@section('meta_description', 'Browse '.$storeName.' — one clear price per product, derived from available stock.')

@section('content')
    <section class="hero">
        <div class="hero__copy">
            <p class="eyebrow">Catalog</p>
            <h1>One price. Every product.</h1>
            <p class="hero__lead">
                We show a single current price per product, derived from the stock lots that
                are actually available. No lot pickers, no hidden tiers.
            </p>
            <div class="hero__actions">
                <a class="button" href="{{ route('storefront.search') }}">Browse all products</a>
                @if (! empty($navCategories))
                    <a class="button button--secondary" href="{{ route('storefront.categories.show', ['category' => $navCategories[0]['slug']]) }}">
                        Start with {{ $navCategories[0]['name'] }}
                    </a>
                @endif
            </div>
        </div>
        <div class="hero__panel" aria-hidden="true">
            <div class="hero__panel-row">
                <span class="hero__dot"></span>
                <span>Derived price</span>
            </div>
            <div class="hero__panel-price">
                @if ($featuredProducts !== [])
                    {{ $featuredProducts[0]->price->price?->format() ?? 'Unavailable' }}
                @else
                    —
                @endif
            </div>
            <p class="hero__panel-note">
                Highest in-stock sale price, converted to {{ $currentCurrency->code() }}.
            </p>
        </div>
    </section>

    @if ($navCategories !== [])
        <section class="section">
            <div class="section__heading">
                <h2>Shop by category</h2>
            </div>
            <div class="category-grid">
                @foreach ($navCategories as $category)
                    <a class="category-card" href="{{ route('storefront.categories.show', ['category' => $category['slug']]) }}">
                        <span class="category-card__name">{{ $category['name'] }}</span>
                        <span class="category-card__count">
                            {{ $category['product_count'] }} {{ $category['product_count'] === 1 ? 'product' : 'products' }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($featuredProducts !== [])
        <section class="section">
            <div class="section__heading">
                <h2>Featured</h2>
                <a href="{{ route('storefront.search') }}">View all</a>
            </div>
            <div class="product-grid">
                @foreach ($featuredProducts as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    <section class="section">
        <div class="section__heading">
            <h2>New arrivals</h2>
            <a href="{{ route('storefront.search') }}">View all</a>
        </div>

        @if ($latestProducts === [])
            <div class="empty-state">
                <h3>No products yet</h3>
                <p>The catalog is empty. Add categories and stock lots in the admin panel.</p>
                <a class="button" href="/admin">Open admin</a>
            </div>
        @else
            <div class="product-grid">
                @foreach ($latestProducts as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @endforeach
            </div>
        @endif
    </section>
@endsection
