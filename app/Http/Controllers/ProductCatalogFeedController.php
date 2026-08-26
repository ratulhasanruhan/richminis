<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;

class ProductCatalogFeedController extends Controller
{
    /**
     * Product feed for Meta Commerce Manager (Dynamic Ads / retargeting) and Google Merchant
     * Center - both read the same RSS 2.0 + g: namespace format, so one feed serves both rather
     * than maintaining two.
     *
     * g:id is deliberately the product's own database id, because that's the exact same value
     * already sent as content_ids/item id in the Meta CAPI events (ViewContent/AddToCart/Purchase
     * in HomeController, CartController, CheckoutController). A dynamic retargeting ad can only
     * show "the product this visitor looked at" if the catalog's id and the tracked event's id
     * are the same string - that matching is the whole point of this feed existing.
     */
    public function index()
    {
        $xml = Cache::remember('facebook_catalog_feed_xml', now()->addHours(6), function () {
            return $this->buildFeed();
        });

        return Response::make($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    private function buildFeed()
    {
        $currency = get_system_default_currency()->code;

        // Wholesale products price per quantity tier, not a single retail price, so they don't
        // map onto a feed format built around one g:price per item - excluded rather than shown
        // with a misleading number. Digital goods aren't something Dynamic Product Ads are built
        // for, so those are left out too.
        $products = Product::with(['main_category', 'thumbnail', 'brand', 'stocks'])
            ->where('published', 1)
            ->where('approved', 1)
            ->where('auction_product', 0)
            ->where('digital', 0)
            ->where('wholesale_product', 0)
            ->get();

        $items = '';

        foreach ($products as $product) {
            $items .= $this->buildItem($product, $currency);
        }

        $site_name = htmlspecialchars(get_setting('website_name') ?: config('app.name'), ENT_QUOTES, 'UTF-8');
        $site_url = htmlspecialchars(url('/'), ENT_QUOTES, 'UTF-8');

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
<channel>
<title>{$site_name} Product Feed</title>
<link>{$site_url}</link>
<description>Product catalog for Meta Commerce Manager and Google Merchant Center</description>
{$items}</channel>
</rss>
XML;
    }

    private function buildItem($product, $currency)
    {
        $qty = $product->stocks->sum('qty');
        $availability = ($qty >= 1 && $qty >= $product->min_qty) ? 'in stock' : 'out of stock';

        // home_base_price()/home_discounted_price() with $formatted=false still return a
        // "lowest - highest" string, not a number, even when both ends are equal - only the
        // formatted-for-display branch collapses that case. The lowest value is always first,
        // so that's the "starting at" price this single-price-per-item feed format needs.
        $price = (float) explode(' - ', home_base_price($product, false))[0];
        $sale_price = (float) explode(' - ', home_discounted_price($product, false))[0];
        $has_discount = product_discount_applicable($product) && $sale_price < $price;

        $image = $product->thumbnail ? uploaded_asset($product->thumbnail_img) : static_asset('assets/img/placeholder.jpg');
        $brand = $product->brand->name ?? (get_setting('website_name') ?: config('app.name'));
        $has_identifiers = !empty($product->brand);

        $fields = [
            'g:id' => (string) $product->id,
            'g:title' => $product->getTranslation('name'),
            'g:description' => \Illuminate\Support\Str::limit(strip_tags($product->getTranslation('description') ?? ''), 4900, ''),
            'g:link' => route('product', $product->slug),
            'g:image_link' => $image,
            'g:condition' => 'new',
            'g:availability' => $availability,
            'g:price' => number_format($price, 2, '.', '') . ' ' . $currency,
            'g:brand' => $brand,
        ];

        if ($has_discount) {
            $fields['g:sale_price'] = number_format($sale_price, 2, '.', '') . ' ' . $currency;
        }

        if ($product->main_category) {
            $fields['g:product_type'] = $product->main_category->getTranslation('name') ?? $product->main_category->name;
        }

        // Meta/Google penalize items with no brand/GTIN/MPN unless this is explicitly declared -
        // this store's products don't carry GTIN/MPN data, so declare it rather than risk
        // disapproval for "missing identifiers" on every item.
        if (!$has_identifiers) {
            $fields['g:identifier_exists'] = 'no';
        }

        $xml = "<item>\n";
        foreach ($fields as $tag => $value) {
            $xml .= '<' . $tag . '>' . $this->cdata($value) . '</' . $tag . ">\n";
        }
        $xml .= "</item>\n";

        return $xml;
    }

    private function cdata($value)
    {
        return '<![CDATA[' . str_replace(']]>', ']]&gt;', (string) $value) . ']]>';
    }
}
