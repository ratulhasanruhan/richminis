@if (count($newest_products) > 0)
<section class="py-0">
    <div class="container">
        <style>
            /* Home 6: show ~6 NEW IN items on mobile */
            @media (max-width: 575px) {
                #newest-products-list > div:nth-child(n+7) { display: none !important; }
            }

            /* Desktop: 5 products per row */
            @media (min-width: 992px) {
                #newest-products-list > .newest-product-col {
                    flex: 0 0 20%;
                    max-width: 20%;
                }
            }
        </style>
        <div class="row px-3" id="newest-products-list">
            @foreach ($newest_products as $index => $new_product)
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
