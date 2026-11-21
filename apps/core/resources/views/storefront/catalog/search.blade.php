@extends('storefront.layouts.app')

@section('title', ($term !== '' ? 'Search: '.$term : 'All products').' · '.$storeName)
@section('meta_description', $term !== '' ? 'Search results for '.$term.' at '.$storeName.'.' : 'Browse every product at '.$storeName.'.')

@section('content')
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('storefront.home') }}">Home</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">{{ $term !== '' ? 'Search' : 'All products' }}</span>
    </nav>

    <header class="page-header">
        <p class="eyebrow">{{ $term !== '' ? 'Search results' : 'Catalog' }}</p>
        <h1>{{ $term !== '' ? 'Results for “'.$term.'”' : 'All products' }}</h1>
        <p class="page-header__lead">
            {{ $products->total() }} {{ $products->total() === 1 ? 'product' : 'products' }}
            @if ($term !== '')
                matching your search
            @endif
            · prices in {{ $currentCurrency->code() }}
        </p>
    </header>

    @if ($products->isEmpty())
        <div class="empty-state">
            <h3>No matches</h3>
            <p>Try a different word, or browse the full catalog.</p>
            <a class="button" href="{{ route('storefront.search') }}">Browse all products</a>
        </div>
    @else
        <div class="product-grid">
            @foreach ($products as $product)
                @include('storefront.partials.product-card', ['product' => $product])
            @endforeach
        </div>

        {{ $products->links('storefront.partials.pagination') }}
    @endif
@endsection
