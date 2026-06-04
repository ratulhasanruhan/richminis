<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddUniqueIndexToOrdersCode extends Migration
{
    public function up()
    {
        // Change mediumtext → varchar(20) so a standard unique index can be added.
        // The new format is 10 chars (YYMMDDNNNN); varchar(20) comfortably fits
        // both old-format codes (up to ~18 chars) and new ones.
        DB::statement('ALTER TABLE `orders` MODIFY COLUMN `code` varchar(20) DEFAULT NULL');

        // Drop the index first in case a previous migration attempt left it behind.
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropUnique('orders_code_unique');
            });
        } catch (\Throwable $e) {
            // Index did not exist — that's fine, continue.
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->unique('code', 'orders_code_unique');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_code_unique');
        });

        DB::statement('ALTER TABLE `orders` MODIFY COLUMN `code` mediumtext DEFAULT NULL');
    }
}
