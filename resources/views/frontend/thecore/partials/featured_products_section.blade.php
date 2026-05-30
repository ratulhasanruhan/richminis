@php $featured_products = get_featured_products(); @endphp
@if (count($featured_products) > 0)
    {{-- Mobile: category-style product grid --}}
    <div class="d-md-none">
        @include('frontend.partials.listing_style_product_grid', [
            'products' => $featured_products,
            'grid_id' => 'featured-products-list',
            'mobile_limit' => 6,
        ])
    </div>

    {{-- Desktop: original carousel --}}
    <section class="mb-2 mb-md-3 mt-2 mt-md-3 d-none d-md-block">
        <div class="container">
            <div class="d-flex mb-2 mb-md-3 align-items-baseline justify-content-between">
                <div class="d-flex">
                    <a type="button" class="arrow-prev slide-arrow link-disable text-secondary mr-2" onclick="clickToSlide('slick-prev','section_featured')"><i class="las la-angle-left fs-20 fw-600"></i></a>
                    <a type="button" class="arrow-next slide-arrow text-secondary ml-2" onclick="clickToSlide('slick-next','section_featured')"><i class="las la-angle-right fs-20 fw-600"></i></a>
                </div>
            </div>
            <div class="px-sm-3">
                <div class="aiz-carousel sm-gutters-16 arrow-none" data-items="6" data-xl-items="5" data-lg-items="4" data-md-items="3" data-sm-items="2.5" data-xs-items="2.5" data-arrows='true' data-infinite='false'>
                    @foreach ($featured_products as $key => $product)
                        <div class="col border-right border-bottom has-transition hov-shadow-out z-1">
                            @include('frontend.product_box_for_listing_page', ['product' => $product])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
