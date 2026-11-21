@extends('storefront.layouts.app')

@section('title', $product->meta_title ?: $product->name.' · '.$storeName)
@section('meta_description', $product->meta_description ?: ($product->short_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 155)))
@section('canonical', route('storefront.products.show', ['product' => $product->slug]))

@if ($price->price !== null)
    @push('head')
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $product->name,
                'sku' => $product->sku,
                'description' => $product->short_description ?: $product->description,
                'category' => $product->category?->name,
                'offers' => [
                    '@type' => 'Offer',
                    'url' => route('storefront.products.show', ['product' => $product->slug]),
                    'price' => $price->price->toDecimalString(),
                    'priceCurrency' => $price->price->currency()->code(),
                    'availability' => $price->inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
        </script>
    @endpush
@endif

@section('content')
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('storefront.home') }}">Home</a>
        <span aria-hidden="true">/</span>
        @if ($product->category)
            <a href="{{ route('storefront.categories.show', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a>
            <span aria-hidden="true">/</span>
        @endif
        <span aria-current="page">{{ $product->name }}</span>
    </nav>

    <article class="product-detail">
        <div class="product-detail__media" aria-hidden="true">
            <span>{{ mb_substr($product->name, 0, 1) }}</span>
        </div>

        <div class="product-detail__info">
            @if ($product->category)
                <a class="product-detail__category" href="{{ route('storefront.categories.show', ['category' => $product->category->slug]) }}">
                    {{ $product->category->name }}
                </a>
            @endif

            <h1>{{ $product->name }}</h1>

            <p class="product-detail__price">
                @if ($price->purchasable)
                    {{ $price->price?->format() }}
                @elseif ($price->inStock)
                    <span class="muted">Unavailable in {{ $currentCurrency->code() }}</span>
                @else
                    <span class="muted">Out of stock</span>
                @endif
            </p>

            <p class="product-detail__availability">
                @if ($price->purchasable)
                    <span class="badge badge--in">In stock</span>
                @elseif (! $price->inStock)
                    <span class="badge badge--out">Out of stock</span>
                @else
                    <span class="badge badge--warn">No {{ $currentCurrency->code() }} price available</span>
                @endif
            </p>

            @if ($product->sku)
                <p class="muted small">SKU: {{ $product->sku }}</p>
            @endif

            @if ($product->short_description)
                <p class="product-detail__summary">{{ $product->short_description }}</p>
            @endif

            @if ($product->description)
                <div class="prose">{!! nl2br(e($product->description)) !!}</div>
            @endif

            <div class="product-detail__actions">
                @if ($price->purchasable)
                    <form class="add-to-cart" method="POST" action="{{ route('storefront.cart.items.store') }}">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->public_id }}">
                        <label class="sr-only" for="quantity">Quantity</label>
                        <input
                            class="qty-input"
                            id="quantity"
                            type="number"
                            name="quantity"
                            value="1"
                            min="1"
                            max="{{ $product->derivedAvailableQty() }}"
                            inputmode="numeric"
                        >
                        <button class="button" type="submit">Add to cart</button>
                    </form>
                @endif

                <a class="button button--secondary" href="{{ route('storefront.search') }}">Continue browsing</a>
                @if ($product->category)
                    <a class="button button--secondary" href="{{ route('storefront.categories.show', ['category' => $product->category->slug]) }}">
                        More in {{ $product->category->name }}
                    </a>
                @endif
            </div>

            <p class="muted small">
                One price per product, derived from the highest in-stock sale price and shown in
                {{ $currentCurrency->code() }}. Stock lots and purchase costs stay in the admin.
            </p>
        </div>
    </article>

    @if ($relatedProducts !== [])
        <section class="section">
            <div class="section__heading">
                <h2>Related products</h2>
            </div>
            <div class="product-grid">
                @foreach ($relatedProducts as $related)
                    @include('storefront.partials.product-card', ['product' => $related])
                @endforeach
            </div>
        </section>
    @endif
@endsection
