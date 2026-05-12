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

        if (!$otpRow || empty($otpRow->type)) {
            Log::warning('sms_otp_no_provider_enabled');
            throw new \RuntimeException('No OTP/SMS provider is enabled in admin OTP configuration.');
        }

        $otp = $otpRow->type;
        $otp_class = __NAMESPACE__ . '\\OTP\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $otp)));

        if (!class_exists($otp_class)) {
            Log::warning('sms_otp_provider_class_missing', ['type' => $otp, 'class' => $otp_class]);
            throw new \RuntimeException('SMS provider implementation is missing: ' . $otp_class);
        }

        return (new $otp_class)->send($to, $from, $text, $template_id);
    }
}
