<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\NotificationSetting;
use Dipokhalder\EnvEditor\EnvEditor;
use Smartisan\Settings\Facades\Settings;


class NotificationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $envService = new EnvEditor();
        Settings::group('notification')->set([
            'notification_fcm_public_vapid_key'    => $envService->getValue('DEMO') ? 'BEJUlPCfKVEuTJOcT4yR53ndElyQo8LJGYaM_GzyjMXgpdvu2bN0eASgrqC18oKhGGE5I0dERO1_UqCJ-sHHETE' : '',
            'notification_fcm_api_key'             => $envService->getValue('DEMO') ? 'AIzaSyBGzuLCCMSABotWASYTSzYK3fAiQ39w5R8' : '',
            'notification_fcm_auth_domain'         => $envService->getValue('DEMO') ? 'shopperz-fe4ea.firebaseapp.com' : '',
            'notification_fcm_project_id'          => $envService->getValue('DEMO') ? 'shopperz-fe4ea' : '',
            'notification_fcm_storage_bucket'      => $envService->getValue('DEMO') ? 'shopperz-fe4ea.appspot.com' : '',
            'notification_fcm_messaging_sender_id' => $envService->getValue('DEMO') ? '308737311204' : '',
            'notification_fcm_app_id'              => $envService->getValue('DEMO') ? '1:308737311204:web:b7079c17fa6bf8d31bc7f1' : '',
            'notification_fcm_measurement_id'      => $envService->getValue('DEMO') ? 'G-T1CSXVXREN' : '',
            'notification_fcm_json_file'           => '',
        ]);

        if ($envService->getValue('DEMO') && file_exists(public_path('/file/service-account-file.json'))) {
            $setting = NotificationSetting::where('key', 'notification_fcm_json_file')->first();
            $setting->addMedia(public_path('/file/service-account-file.json'))->preservingOriginal()->usingFileName('service-account-file.json')->toMediaCollection('notification-file');
        }
    }
}
