<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('combined_orders', function (Blueprint $table) {
            // One JSON blob (utm_source/medium/campaign/term/content, plus the landing page and
            // referrer) rather than five separate columns - matches how this table already stores
            // shipping_address, and keeps this open to new attribution fields later without a
            // migration each time.
            $table->text('utm_data')->nullable()->after('shipping_address');
        });
    }

    public function down()
    {
        Schema::table('combined_orders', function (Blueprint $table) {
            $table->dropColumn('utm_data');
        });
    }
};
