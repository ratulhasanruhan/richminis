<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('otp_configurations')) {
            return;
        }

        Schema::create('otp_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable()->index();
            $table->string('value')->nullable()->default('0');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_configurations');
    }
};

