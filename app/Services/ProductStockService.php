<?php

namespace App\Services;

use AizPackages\CombinationGenerate\Services\CombinationService;
use App\Models\ProductStock;
use App\Utility\ProductUtility;
use Illuminate\Support\Facades\Log;

class ProductStockService
{
    public function store(array $data, $product)
    {
        //Log::info('Product Stock Request:', $data);
        $collection = collect($data);

        $options = ProductUtility::get_attribute_options($collection);
        
        //Generates the combinations of customer choice options
        $combinations = (new CombinationService())->generate_combination($options);
        
        $variant = '';
        if (count($combinations) > 0) {
            $product->variant_product = 1;
            $product->save();
            foreach ($combinations as $key => $combination) {
                $str = ProductUtility::get_combination_string($combination, $collection);
                $key_suffix = str_replace('.', '_', $str);
                $product_stock = new ProductStock();
                $product_stock->product_id = $product->id;
                $product_stock->variant = $str;
                // A draft can be autosaved mid-composition, before every variant combination has
                // a price/qty typed in yet - price and qty are NOT NULL columns, so a missing
                // form field (request() returns null, not the column default) used to fail the
                // whole save outright rather than just leaving that combination unpriced for now.
                $product_stock->price = request()['price_' . $key_suffix] ?? $collection['unit_price'] ?? 0;
                $product_stock->sku = request()['sku_' . $key_suffix];
                $product_stock->qty = request()['qty_' . $key_suffix] ?? 0;
                $product_stock->image = request()['img_' . $key_suffix];
                $product_stock->save();
            }
        } else {
            $product->variant_product = 0;
            $product->save();
            unset($collection['colors_active'], $collection['colors'], $collection['choice_no']);
            $qty = $collection['current_stock'];
            $price = $collection['unit_price'];
            unset($collection['current_stock']);

            $data = $collection->merge(compact('variant', 'qty', 'price'))->toArray();
            
            ProductStock::create($data);
        }
    }

    public function product_duplicate_store($product_stocks , $product_new)
    {
        foreach ($product_stocks as $key => $stock) {
            $product_stock              = new ProductStock;
            $product_stock->product_id  = $product_new->id;
            $product_stock->variant     = $stock->variant;
            $product_stock->price       = $stock->price;
            $product_stock->sku         = null;
            $product_stock->qty         = $stock->qty;
            $product_stock->save();
        }
    }
}
