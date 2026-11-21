@extends('storefront.layouts.app')

@section('title', 'Your cart · '.$storeName)
@section('meta_description', 'Review the items in your cart before checkout.')

@section('content')
    <header class="page-header page-header--split">
        <div>
            <p class="eyebrow">Cart</p>
            <h1>Your cart</h1>
            <p class="page-header__lead">
                Prices are recalculated from available stock every time you view the cart.
                Stock lots and purchase costs stay in the admin.
            </p>
        </div>

        @if ($totals->itemCount() > 0)
            <form method="POST" action="{{ route('storefront.cart.destroy') }}">
                @csrf
                @method('DELETE')
                <button class="button button--secondary" type="submit">Clear cart</button>
            </form>
        @endif
    </header>

    @if ($cart->exists && $displayCurrency->code() !== $totals->currency->code())
        <aside class="notice" aria-label="Currency notice">
            <div>
                <strong>This cart is priced in {{ $totals->currency->code() }}.</strong>
                <p>
                    Currency is locked when a cart is created. Start a new cart to shop in
                    {{ $displayCurrency->code() }}; your current items will not be copied.
                </p>
            </div>
            <form method="POST" action="{{ route('storefront.cart.new') }}">
                @csrf
                <input type="hidden" name="currency" value="{{ $displayCurrency->code() }}">
                <button class="button" type="submit">Start a {{ $displayCurrency->code() }} cart</button>
            </form>
        </aside>
    @endif

    @if ($totals->isEmpty())
        <section class="empty-state">
            <h3>Your cart is empty</h3>
            <p>Add something from the catalog and it will appear here, priced in {{ $totals->currency->code() }}.</p>
            <a class="button" href="{{ route('storefront.search') }}">Browse products</a>
        </section>
    @else
        <div class="cart-layout">
            <section class="cart-lines" aria-label="Cart items">
                @foreach ($totals->lines as $line)
                    <article class="cart-line">
                        <div class="cart-line__media" aria-hidden="true">
                            {{ mb_substr($line->product->name, 0, 1) }}
                        </div>

                        <div class="cart-line__body">
                            <div class="cart-line__heading">
                                <div>
                                    <h2>
                                        <a href="{{ route('storefront.products.show', ['product' => $line->product->slug]) }}">
                                            {{ $line->product->name }}
                                        </a>
                                    </h2>
                                    <p class="muted small">
                                        @if ($line->unitPrice !== null)
                                            {{ $line->unitPrice->format() }} each
                                        @else
                                            Price unavailable
                                        @endif
                                    </p>
                                </div>

                                <p class="cart-line__total">{{ $line->total->format() }}</p>
                            </div>

                            @if ($line->unavailableReason !== null)
                                <p class="badge badge--warn" role="status">{{ $line->unavailableReason }}</p>
                            @elseif ($line->availableQuantity < 5)
                                <p class="muted small">Only {{ $line->availableQuantity }} left in stock.</p>
                            @endif

                            <div class="cart-line__actions">
                                <form
                                    class="cart-qty"
                                    method="POST"
                                    action="{{ route('storefront.cart.items.update', ['item' => $line->item->public_id]) }}"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="version" value="{{ $cart->version }}">
                                    <label for="quantity-{{ $line->item->public_id }}">Quantity</label>
                                    <input
                                        id="quantity-{{ $line->item->public_id }}"
                                        class="qty-input"
                                        type="number"
                                        name="quantity"
                                        value="{{ $line->quantity }}"
                                        min="1"
                                        max="{{ max(1, $line->availableQuantity) }}"
                                        inputmode="numeric"
                                    >
                                    <button class="button button--secondary" type="submit">Update</button>
                                </form>

                                <form
                                    method="POST"
                                    action="{{ route('storefront.cart.items.destroy', ['item' => $line->item->public_id]) }}"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button class="link-button" type="submit">Remove</button>
                                </form>
                            </div>

                            @if ($line->discount->isPositive())
                                <p class="muted small">Coupon discount on this line: −{{ $line->discount->format() }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </section>

            <aside class="cart-summary" aria-label="Order summary">
                <h2>Summary</h2>

                <dl class="totals">
                    <div>
                        <dt>Subtotal</dt>
                        <dd>{{ $totals->subtotal->format() }}</dd>
                    </div>

                    @if ($totals->discount->isPositive())
                        <div class="totals__discount">
                            <dt>Coupon</dt>
                            <dd>−{{ $totals->discount->format() }}</dd>
                        </div>
                    @endif

                    <div>
                        <dt>Shipping</dt>
                        <dd>{{ $totals->shipping->format() }}</dd>
                    </div>

                    <div>
                        <dt>Tax</dt>
                        <dd>{{ $totals->tax->format() }}</dd>
                    </div>

                    <div class="totals__grand">
                        <dt>Total</dt>
                        <dd>{{ $totals->grandTotal->format() }}</dd>
                    </div>
                </dl>

                @if ($totals->discountQuote !== null)
                    <div class="coupon coupon--applied">
                        <p>
                            Coupon <strong>{{ $totals->discountQuote->code }}</strong>
                            @if ($totals->discountQuote->valid)
                                applied: −{{ $totals->discountQuote->amount->format() }}
                            @else
                                could not be applied.
                            @endif
                        </p>

                        @if (! $totals->discountQuote->valid && $totals->discountQuote->message !== null)
                            <p class="muted small">{{ $totals->discountQuote->message }}</p>
                        @endif

                        <form
                            method="POST"
                            action="{{ route('storefront.cart.discounts.destroy', ['code' => $totals->discountQuote->code]) }}"
                        >
                            @csrf
                            @method('DELETE')
                            <button class="link-button" type="submit">Remove coupon</button>
                        </form>
                    </div>
                @else
                    <form class="coupon" method="POST" action="{{ route('storefront.cart.discounts.store') }}">
                        @csrf
                        <label for="coupon-code">Coupon code</label>
                        <div class="coupon__row">
                            <input id="coupon-code" type="text" name="code" value="{{ old('code') }}" autocomplete="off" placeholder="WELCOME10">
                            <button class="button button--secondary" type="submit">Apply</button>
                        </div>
                    </form>
                @endif

                @if (! $totals->isPurchasable)
                    <p class="badge badge--warn" role="status">
                        Fix the highlighted items before checkout.
                    </p>
                @endif

                <a class="button button--block" href="{{ route('storefront.search') }}">Continue shopping</a>
                <p class="muted small">Checkout arrives in the next phase. Your cart is saved for this browser.</p>
            </aside>
        </div>
    @endif
@endsection
