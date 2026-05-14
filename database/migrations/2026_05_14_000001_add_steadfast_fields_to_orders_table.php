<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        $missingColumns = [];

        if (!Schema::hasColumn('orders', 'shipping_method')) {
            $missingColumns[] = 'shipping_method';
        }

        if (!Schema::hasColumn('orders', 'steadfast_consignment_id')) {
            $missingColumns[] = 'steadfast_consignment_id';
        }

        if (!Schema::hasColumn('orders', 'steadfast_tracking_code')) {
            $missingColumns[] = 'steadfast_tracking_code';
        }

        if (!Schema::hasColumn('orders', 'steadfast_status')) {
            $missingColumns[] = 'steadfast_status';
        }

        if ($missingColumns === []) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) use ($missingColumns) {
            if (in_array('shipping_method', $missingColumns, true)) {
                $table->string('shipping_method')->nullable();
            }

            if (in_array('steadfast_consignment_id', $missingColumns, true)) {
                $table->string('steadfast_consignment_id', 100)->nullable();
            }

            if (in_array('steadfast_tracking_code', $missingColumns, true)) {
                $table->string('steadfast_tracking_code', 100)->nullable();
            }

            if (in_array('steadfast_status', $missingColumns, true)) {
                $table->string('steadfast_status', 100)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        $dropColumns = [];

        foreach ([
            'steadfast_status',
            'steadfast_tracking_code',
            'steadfast_consignment_id',
            'shipping_method',
        ] as $column) {
            if (Schema::hasColumn('orders', $column)) {
                $dropColumns[] = $column;
            }
        }

        if ($dropColumns === []) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) use ($dropColumns) {
            $table->dropColumn($dropColumns);
        });
    }
};

