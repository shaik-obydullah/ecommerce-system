@php
    /** @var \App\Core\Catalog\Data\ProductCard $product */
@endphp

<article class="product-card">
    <a class="product-card__link" href="{{ $product->url }}">
        <div class="product-card__media" aria-hidden="true">
            <span>{{ mb_substr($product->name, 0, 1) }}</span>
        </div>

        <div class="product-card__body">
            @if ($product->categoryName)
                <p class="product-card__category">{{ $product->categoryName }}</p>
            @endif

            <h3 class="product-card__name">{{ $product->name }}</h3>

            <p class="product-card__price">
                @if ($product->price->purchasable)
                    {{ $product->price->price?->format() }}
                @elseif ($product->price->inStock)
                    <span class="muted">Unavailable in {{ $currentCurrency->code() }}</span>
                @else
                    <span class="muted">Out of stock</span>
                @endif
            </p>

            @if ($product->price->purchasable)
                <span class="badge badge--in">In stock</span>
            @elseif (! $product->price->inStock)
                <span class="badge badge--out">Out of stock</span>
            @else
                <span class="badge badge--warn">Currency unavailable</span>
            @endif
        </div>
    </a>
</article>
