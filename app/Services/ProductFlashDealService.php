<?php

namespace App\Services;

use App\Models\FlashDeal;
use App\Models\FlashDealProduct;
use App\Models\Product;

class ProductFlashDealService
{
    public function store(array $data, Product $product)
    {
        $collection = collect($data);

        if ($collection->get('flash_deal_id')) {
            $flash_deal_product = FlashDealProduct::firstOrNew([
                'flash_deal_id' => $collection->get('flash_deal_id'),
                'product_id' => $product->id]
                );
            $flash_deal_product->flash_deal_id = $collection->get('flash_deal_id');
            $flash_deal_product->product_id = $product->id;
            $flash_deal_product->save();

            $flash_deal = FlashDeal::findOrFail($collection->get('flash_deal_id'));

            // flash_discount writes products.discount, the very column the product form's own
            // Discount field feeds. This runs after that value has been saved, so when no flash
            // discount is supplied keep what the product already has rather than resetting it —
            // the deal then only contributes its schedule.
            $flash_discount = $collection->get('flash_discount');
            $flash_discount_type = $collection->get('flash_discount_type');

            if ($flash_discount !== null && $flash_discount !== '') {
                $product->discount = $flash_discount;
            }
            if ($flash_discount_type !== null && $flash_discount_type !== '') {
                $product->discount_type = $flash_discount_type;
            }
            // Normalised to null so a deal with no schedule leaves the discount open ended rather
            // than stamping a 0 that reads as "ended in 1970".
            $product->discount_start_date = $flash_deal->start_date ?: null;
            $product->discount_end_date   = $flash_deal->end_date ?: null;
            $product->save();
        }

    }

}
