{{-- Same grid + card layout as category / search product listing --}}
@if (count($products) > 0)
<section class="py-0">
    <div class="px-3">
        @if (!empty($grid_id) && !empty($mobile_limit))
            <style>
                @media (max-width: 575px) {
                    #{{ $grid_id }} > div:nth-child(n+{{ (int) $mobile_limit + 1 }}) { display: none !important; }
                }
            </style>
        @endif
        <div
            class="row gutters-16 row-cols-xxl-4 row-cols-xl-3 row-cols-lg-4 row-cols-md-3 row-cols-2 border-top border-left"
            @if (!empty($grid_id)) id="{{ $grid_id }}" @endif
        >
            @foreach ($products as $product)
                <div class="col border-right border-bottom has-transition hov-shadow-out z-1">
                    @include('frontend.product_box_for_listing_page', ['product' => $product])
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
