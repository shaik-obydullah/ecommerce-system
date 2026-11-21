@extends('storefront.layouts.app')

@section('title', $category->meta_title ?: $category->name.' · '.$storeName)
@section('meta_description', $category->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $category->description), 155))
@section('canonical', route('storefront.categories.show', ['category' => $category->slug]))

@section('content')
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('storefront.home') }}">Home</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">{{ $category->name }}</span>
    </nav>

    <header class="page-header">
        <p class="eyebrow">Category</p>
        <h1>{{ $category->name }}</h1>
        @if ($category->description)
            <p class="page-header__lead">{{ $category->description }}</p>
        @endif
    </header>

    @if ($products->isEmpty())
        <div class="empty-state">
            <h3>Nothing here yet</h3>
            <p>This category has no active products. Check back soon or browse the full catalog.</p>
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
