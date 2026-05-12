<?php

namespace App\Services;

use App\Models\OtpConfiguration;
use Illuminate\Support\Facades\Log;

class SendSmsService
{
    public function sendSMS($to, $from, $text, $template_id)
    {
        $otpRow = OtpConfiguration::query()
            ->where(function ($q) {
                $q->where('value', 1)->orWhere('value', '1');
            })
            ->first();

        $otp = ($otpRow && !empty($otpRow->type)) ? $otpRow->type : null;

        // No provider toggled in admin → use MIM SMS by default (see App\Services\OTP\Mimsms).
        // Override with SMS_DEFAULT_PROVIDER in .env if needed.
        if (!$otp) {
            $otp = env('SMS_DEFAULT_PROVIDER', 'mimsms');
            Log::info('sms_otp_using_default_provider', ['type' => $otp]);
        }
        $otp_class = __NAMESPACE__ . '\\OTP\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $otp)));

        if (!class_exists($otp_class)) {
            Log::warning('sms_otp_provider_class_missing', ['type' => $otp, 'class' => $otp_class]);
            throw new \RuntimeException('SMS provider implementation is missing: ' . $otp_class);
        }

        return (new $otp_class)->send($to, $from, $text, $template_id);
    }
}
