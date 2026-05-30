@if (count($newest_products) > 0)
    {{-- Mobile: category-style product grid --}}
    <div class="d-md-none">
        @include('frontend.partials.listing_style_product_grid', [
            'products' => $newest_products,
            'grid_id' => 'newest-products-list',
            'mobile_limit' => 6,
        ])
    </div>

    {{-- Desktop: original layout --}}
    <section class="py-0 d-none d-md-block">
        <div class="container">
            <style>
                /* Desktop: 5 products per row */
                @media (min-width: 992px) {
                    #newest-products-list-desktop > .newest-product-col {
                        flex: 0 0 20%;
                        max-width: 20%;
                    }
                }

                /* NEW IN: space around product image inside the card */
                #newest-products-list-desktop .aiz-card-box > .position-relative.img-fit.overflow-hidden {
                    padding: clamp(8px, 1.6vw, 14px);
                    box-sizing: border-box;
                }
            </style>
            <div class="row px-3" id="newest-products-list-desktop">
                @foreach ($newest_products as $new_product)
                    <div class="col-md-3 col-lg-3 col-sm-4 col-6 d-flex product-card justify-content-center mx-auto newest-product-col">
                        <div class="border-right border-bottom has-transition hov-shadow-out z-1">
                            @include('frontend.product_box_for_listing_page', ['product' => $new_product])
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
