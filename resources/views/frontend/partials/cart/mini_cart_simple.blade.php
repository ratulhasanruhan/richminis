@php
    $total = 0;
    $carts = get_user_cart();
    if (count($carts) > 0) {
        foreach ($carts as $cartItem) {
            $product = get_single_product($cartItem['product_id']);
            $total = $total + cart_product_price($cartItem, $product, false) * $cartItem['quantity'];
        }
    }
@endphp

<div class="rm-mini-cart">
    @if (isset($carts) && count($carts) > 0)
        <div class="rm-mini-cart__title">
            {{ translate('Cart Items') }}
        </div>

        <ul class="rm-mini-cart__list">
            @foreach ($carts->take(4) as $cartItem)
                @php $product = get_single_product($cartItem['product_id']); @endphp
                @if ($product != null)
                    <li class="rm-mini-cart__item">
                        <a href="{{ route('product', $product->slug) }}" class="rm-mini-cart__link text-reset">
                            <img
                                src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                data-src="{{ uploaded_asset($product->thumbnail_img) }}"
                                class="img-fit lazyload rm-mini-cart__img"
                                alt="{{ $product->getTranslation('name') }}"
                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';"
                            >
                            <span class="rm-mini-cart__meta">
                                <span class="rm-mini-cart__name" title="{{ $product->getTranslation('name') }}">
                                    {{ $product->getTranslation('name') }}
                                </span>
                                <span class="rm-mini-cart__price">
                                    {{ $cartItem['quantity'] }}x {{ cart_product_price($cartItem, $product) }}
                                </span>
                            </span>
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>

        <div class="rm-mini-cart__subtotal">
            <span class="text-secondary">{{ translate('Subtotal') }}</span>
            <span class="fw-700 text-dark">{{ single_price($total) }}</span>
        </div>

        <div class="rm-mini-cart__actions">
            <a href="{{ route('cart') }}" class="btn btn-light btn-sm btn-block fw-700">
                {{ translate('View cart') }}
            </a>
            <a href="{{ route('checkout') }}" class="btn btn-primary btn-sm btn-block fw-700 rm-mini-cart__checkout">
                {{ translate('Checkout') }}
            </a>
        </div>
    @else
        <div class="rm-mini-cart__empty">
            <div class="fw-700 mb-1">{{ translate('Your Cart is empty') }}</div>
            <a href="{{ route('search') }}" class="btn btn-light btn-sm btn-block fw-700">
                {{ translate('Continue shopping') }}
            </a>
        </div>
    @endif
</div>

