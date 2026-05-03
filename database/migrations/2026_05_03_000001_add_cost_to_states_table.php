<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('states') && ! Schema::hasColumn('states', 'cost')) {
            Schema::table('states', function (Blueprint $table) {
                $table->double('cost', 20, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('states') && Schema::hasColumn('states', 'cost')) {
            Schema::table('states', function (Blueprint $table) {
                $table->dropColumn('cost');
            });
        }
    }
};
