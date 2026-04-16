<?php

namespace Database\Seeders;

use App\Enums\Activity;
use Illuminate\Database\Seeder;
use Smartisan\Settings\Facades\Settings;

class WhatsappTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Settings::group('whatsapp')->set([
            "whatsapp_number"       => "1676667726",
            "whatsapp_status"       => Activity::ENABLE,
            "whatsapp_calling_code" => "+880"
        ]);
    }
}