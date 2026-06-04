@php $lang = get_system_language()->code; @endphp

<style>
    .rm-hero {
        margin: 0;
        padding: 0;
        line-height: 0;
        width: 100%;
        overflow: hidden;
        position: relative;
        background: #0a0a0a;
    }
    .rm-hero .slick-list,
    .rm-hero .slick-track {
        overflow: hidden;
    }
    .rm-hero .carousel-box > a {
        display: block;
        line-height: 0;
    }
    .rm-hero img {
        width: 100%;
        height: 760px;
        object-fit: cover;
        object-position: center center;
        display: block;
    }
    /*
      Tablet & mobile: fixed-aspect frame (landscape-friendly).
      Wide slider assets were cropped badly with portrait aspect-ratio; this matches typical 1920×960-type banners.
    */
    @media (max-width: 991px) {
        .rm-hero .carousel-box > a {
            position: relative;
            overflow: hidden;
            background: #0a0a0a;
        }
        .rm-hero .carousel-box > a::before {
            content: "";
            display: block;
            width: 100%;
            padding-bottom: 52%;
        }
        .rm-hero img {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100% !important;
            object-fit: cover;
            object-position: center center;
        }
    }
    @media (max-width: 575px) {
        /* Slightly taller on phones (~16:9 → comfortable hero band without portrait crop) */
        .rm-hero .carousel-box > a::before {
            padding-bottom: 62%;
        }
        .rm-hero img {
            object-position: center 42%;
        }
        .rm-hero .slick-dots {
            bottom: 14px !important;
            left: 0;
            right: 0;
            padding: 0 16px;
        }
        .rm-hero .slick-dots li button:before {
            font-size: 9px;
            opacity: 0.55;
        }
        .rm-hero .slick-dots li.slick-active button:before {
            opacity: 0.95;
        }
    }

    .rm-space { height: 28px; }
    @media (max-width: 575px) { .rm-space { height: 18px; } }

    /* Marquee colors based on UI reference */
    .rm-marquee { overflow: hidden; background: #1A1208; margin: 0; }
    .rm-marquee__track { display: inline-flex; white-space: nowrap; will-change: transform; animation: rm-marquee 24s linear infinite; }
    .rm-marquee__item { padding: 14px 24px; letter-spacing: .28em; text-transform: uppercase; font-size: 12px; color: #C4A35A; opacity: .95; }
    @keyframes rm-marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }

    .rm-section-title { display:flex; align-items:center; justify-content:space-between; margin: 32px 0 14px; }
    .rm-section-title h3 { margin:0; font-size: 14px; letter-spacing: .25em; text-transform: uppercase; font-weight: 700; }
    .rm-section-title a { font-size: 12px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
    .rm-title-strip {
        background: #000;
        color: #fff;
        text-align: center;
        padding: 16px 10px;
        letter-spacing: .32em;
        text-transform: uppercase;
        font-weight: 700;
        font-size: 13px;
        margin: 0 0 18px;
    }
    .rm-see-more-bottom {
        display: inline-block;
        margin-top: 18px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .14em;
        text-transform: uppercase;
        border: 1px solid #000;
        padding: 12px 18px;
        color: #000 !important;
        background: transparent;
        transition: background .15s ease, color .15s ease, transform .15s ease, border-color .15s ease;
    }
    .rm-see-more-bottom:hover,
    .rm-see-more-bottom:focus {
        background: #000;
        color: #fff !important;
        transform: translateY(-1px);
    }
    .rm-see-more-bottom:active { transform: translateY(0); }

    .rm-cat-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0; }
    @media (max-width: 991px) { .rm-cat-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 575px) { .rm-cat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0; } }

    .rm-cat-card { display: block; color: inherit; }
    /* Make category blocks feel "full width" */
    .rm-cat-img { width: 100%; height: clamp(220px, 24vw, 420px); object-fit: cover; display:block; background:#f4f4f4; }
    @media (max-width: 991px) { .rm-cat-img { height: clamp(180px, 28vw, 340px); } }
    @media (max-width: 575px) { .rm-cat-img { height: clamp(160px, 48vw, 280px); } }

    .rm-cat-label {
        background: #000;
        color: #fff;
        padding: 14px 10px;
        text-align: center;
        letter-spacing: .28em;
        text-transform: uppercase;
        font-size: 12px;
        font-weight: 600;
        line-height: 1;
    }

    .rm-editorial { margin: 0 !important; padding: 0 !important; line-height: 0; }
    .rm-editorial .container-fluid { margin: 0 !important; padding: 0 !important; }
    .rm-editorial .aiz-carousel,
    .rm-editorial .carousel-box,
    .rm-editorial .slick-slider,
    .rm-editorial .slick-list,
    .rm-editorial .slick-track,
    .rm-editorial .slick-slide {
        margin: 0 !important;
        padding: 0 !important;
    }
    .rm-editorial img {
        width: 100%;
        height: 760px;
        object-fit: cover;
        object-position: center center;
        border-radius: 0;
        display: block;
    }
    @media (max-width: 991px) {
        .rm-editorial img {
            height: min(70vw, 580px);
            min-height: 280px;
            object-position: center top;
        }
    }
    @media (max-width: 575px) {
        .rm-editorial img {
            height: auto;
            aspect-ratio: 16 / 10;
            max-height: min(52vh, 420px);
            min-height: 200px;
            object-fit: cover;
            object-position: center 22%;
        }
    }

    .rm-brandline {
        margin-top: 44px;
        font-weight: 800;
        letter-spacing: .25em;
        text-transform: uppercase;
        font-size: 14px;
        opacity: .85;
    }
    @media (max-width: 575px) { .rm-brandline { font-size: 12px; } }

    .rm-statement { padding: 56px 0; background: #fff; border-top: 1px solid #f2f2f2; border-bottom: 1px solid #f2f2f2; }
    .rm-statement .kicker { letter-spacing: .28em; text-transform: uppercase; font-size: 12px; opacity: .7; }
    .rm-statement h2 { font-size: 28px; line-height: 1.25; margin: 12px 0 0; }
    @media (max-width: 575px) { .rm-statement h2 { font-size: 22px; } }

    .rm-video video { width: 100%; max-height: 520px; object-fit: cover; display:block; }
</style>

<!-- HERO (full-width image slider) -->
<div class="rm-hero">
    @if (get_setting('home_slider_images', null, $lang) != null)
        @php
            $decoded_slider_images = json_decode(get_setting('home_slider_images', null, $lang), true);
            $sliders = get_slider_images($decoded_slider_images);
            $decoded_slider_mobile_images = json_decode(get_setting('home_slider_images_for_mobile', null, $lang) ?? '[]', true);
            $sliders_mobile = get_slider_images($decoded_slider_mobile_images);
            $home_slider_links = get_setting('home_slider_links', null, $lang);
        @endphp
        <div class="aiz-carousel dots-inside-bottom" data-autoplay="true" data-infinite="true" data-fade="true" data-autoplay-speed="3500">
            @foreach ($sliders as $key => $slider)
                <div class="carousel-box">
                    <a href="{{ isset(json_decode($home_slider_links, true)[$key]) ? json_decode($home_slider_links, true)[$key] : '' }}">
                        @php
                            $desktop_src = $slider ? my_asset($slider->file_name) : static_asset('assets/img/placeholder.jpg');
                            $mobile_slider = $sliders_mobile[$key] ?? null;
                            $mobile_src = $mobile_slider ? my_asset($mobile_slider->file_name) : $desktop_src;
                        @endphp
                        <picture>
                            <source media="(max-width: 767px)" srcset="{{ $mobile_src }}">
                            <img
                                src="{{ $desktop_src }}"
                                alt="{{ env('APP_NAME') }} promo"
                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';"
                            >
                        </picture>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- MARQUEE STRIP -->
<div class="rm-marquee">
    <div class="rm-marquee__track">
        @php
            $marquee = get_setting('home_marquee_text') ?: 'DHAKA · BANGLADESH · EST. 2025 · FOR THE CHOSEN ONLY · LUXURY BABY BRAND · PREMIUM SHOES';
        @endphp
        <div class="rm-marquee__item">{{ $marquee }}</div>
        <div class="rm-marquee__item">{{ $marquee }}</div>
        <div class="rm-marquee__item">{{ $marquee }}</div>
        <div class="rm-marquee__item">{{ $marquee }}</div>
    </div>
</div>

<!-- CATEGORY SECTION -->
<section class="py-0">
    <div class="container-fluid px-0">
        @php
            $homeCategories = collect($hot_categories ?? [])->take(4);
            if ($homeCategories->isEmpty()) {
                $homeCategories = \App\Models\Category::where('parent_id', 0)
                    ->orderBy('order_level', 'desc')
                    ->take(4)
                    ->get();
            }
        @endphp

        <div class="rm-cat-grid">
            @foreach ($homeCategories as $category)
                @php $category_name = $category->getTranslation('name'); @endphp
                <a class="rm-cat-card" href="{{ route('products.category', $category->slug) }}" title="{{ $category_name }}">
                    <img
                        class="rm-cat-img"
                        src="{{ isset($category->banner) ? uploaded_asset($category->banner) : (isset($category->cover_image) ? uploaded_asset($category->cover_image) : static_asset('assets/img/placeholder.jpg')) }}"
                        alt="{{ $category_name }}"
                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';"
                    >
                    <div class="rm-cat-label">{{ $category_name }}</div>
                </a>
            @endforeach
        </div>

        <div class="container">
            <div class="text-center rm-brandline">
                {{ env('APP_NAME') }} · Not your basic baby brand
            </div>
        </div>
    </div>
</section>

<div class="rm-space"></div>

<!-- EDITORIAL BANNER / SLIDER -->
@php
    $homeBanner1Images = get_setting('home_banner1_images', null, $lang);
    $homeBanner1MobileImages = get_setting('home_banner1_images_for_mobile', null, $lang);
@endphp
@if ($homeBanner1Images != null)
    <section class="rm-editorial py-0">
        <div class="container-fluid px-0">
            @php
                $banner_1_imags = json_decode($homeBanner1Images, true) ?? [];
                $banner_1_mobile_images = $homeBanner1MobileImages ? (json_decode($homeBanner1MobileImages, true) ?? []) : [];
                $home_banner1_links = get_setting('home_banner1_links', null, $lang);
            @endphp
            <div class="aiz-carousel arrow-inactive-none arrow-dark arrow-x-15" data-items="1" data-arrows="true" data-dots="false" data-autoplay="true" data-infinite="true">
                @foreach ($banner_1_imags as $key => $value)
                    @php
                        $desktop_src = uploaded_asset($value);
                        $mobile_value = $banner_1_mobile_images[$key] ?? null;
                        $mobile_src = $mobile_value ? uploaded_asset($mobile_value) : $desktop_src;
                    @endphp
                    <div class="carousel-box overflow-hidden">
                        <a href="{{ isset(json_decode($home_banner1_links, true)[$key]) ? json_decode($home_banner1_links, true)[$key] : '' }}" class="d-block text-reset">
                            <picture>
                                <source media="(max-width: 767px)" srcset="{{ $mobile_src }}">
                                <img
                                    src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                    data-src="{{ $desktop_src }}"
                                    class="lazyload has-transition"
                                    alt="{{ env('APP_NAME') }} editorial"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}'">
                            </picture>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

<!-- NEW IN -->
<section class="pt-0 pb-2">
    <div class="container-fluid px-0">
        <div class="rm-title-strip">NEW IN</div>
        <div class="container">
            <div id="section_newest"></div>
            <div class="text-center">
                <a class="text-reset animate-underline-primary rm-see-more-bottom" href="{{ route('search', ['sort_by' => 'newest']) }}">See more</a>
            </div>
        </div>
    </div>
</section>

<div class="rm-space"></div>

<!-- BANNER LEVEL 2 (home banner 2) -->
@php $homeBanner2Images = get_setting('home_banner2_images', null, $lang); @endphp
@if ($homeBanner2Images != null)
    <section class="rm-editorial py-0">
        <div class="container-fluid px-0">
            @php
                $banner_2_images = json_decode($homeBanner2Images, true) ?? [];
                $home_banner2_links = get_setting('home_banner2_links', null, $lang);
            @endphp
            <div class="aiz-carousel arrow-inactive-none arrow-dark arrow-x-15" data-items="1" data-arrows="true" data-dots="false" data-autoplay="true" data-infinite="true">
                @foreach ($banner_2_images as $key => $value)
                    <div class="carousel-box overflow-hidden">
                        <a href="{{ isset(json_decode($home_banner2_links, true)[$key]) ? json_decode($home_banner2_links, true)[$key] : '' }}" class="d-block text-reset">
                            <img
                                src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                data-src="{{ uploaded_asset($value) }}"
                                class="lazyload has-transition"
                                alt="{{ env('APP_NAME') }} banner"
                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';"
                            >
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

<!-- FULL-WIDTH VIDEO BANNER (optional: set a public mp4 URL in setting `home_video_mp4_url`) -->
@php $homeVideoMp4 = get_setting('home_video_mp4_url'); @endphp
@if (!empty($homeVideoMp4))
    <section class="rm-video pt-0 pb-4">
        <video autoplay muted loop playsinline>
            <source src="{{ $homeVideoMp4 }}" type="video/mp4">
        </video>
    </section>
@endif

<section class="pt-0 pb-2">
    <div class="container-fluid px-0">
        <div class="rm-title-strip">RARE PICKS</div>
        <div class="container">
            <div id="section_featured"></div>
            <div class="text-center">
                <a class="text-reset animate-underline-primary rm-see-more-bottom" href="{{ route('featured-products') }}">See more</a>
            </div>
        </div>
    </div>
</section>

<div class="rm-space"></div>

{{-- Brand statement removed per request --}}
