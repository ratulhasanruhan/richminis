<?php

namespace App\Utility;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\BusinessSetting;

class FacebookCapiUtility
{
    /**
     * Send conversion event to Facebook Conversions API (CAPI).
     *
     * @param string $eventName Event name (e.g., ViewContent, AddToCart, InitiateCheckout, Purchase)
     * @param array $customData Custom data parameters
     * @param string|null $eventId Unique event ID for browser deduplication
     * @return string
     */
    public static function sendEvent($eventName, $customData = [], $eventId = null)
    {
        $pixelId = env('FACEBOOK_PIXEL_ID');
        $accessToken = env('FACEBOOK_CAPI_ACCESS_TOKEN');
        $testCode = env('FACEBOOK_CAPI_TEST_EVENT_CODE');

        // Only send if pixel id and access token are configured and pixel is enabled
        if (!$pixelId || !$accessToken || get_setting('facebook_pixel') != 1) {
            return $eventId;
        }

        // Generate event ID if not provided
        if (!$eventId) {
            $eventId = $eventName . '_' . uniqid();
        }

        // Gather user data
        $userData = [
            'client_ip_address' => request()->ip(),
            'client_user_agent' => request()->userAgent(),
        ];

        $email = null;
        $phone = null;

        if (auth()->check()) {
            $email = auth()->user()->email;
            $phone = auth()->user()->phone;
        }

        // Retrieve passed parameters for custom override
        if (isset($customData['_user_email'])) {
            $email = $customData['_user_email'];
            unset($customData['_user_email']);
        }
        if (isset($customData['_user_phone'])) {
            $phone = $customData['_user_phone'];
            unset($customData['_user_phone']);
        }

        if ($email) {
            $userData['em'] = [hash('sha256', strtolower(trim($email)))];
        }
        if ($phone) {
            $cleanPhone = preg_replace('/\D/', '', $phone);
            if ($cleanPhone) {
                $userData['ph'] = [hash('sha256', $cleanPhone)];
            }
        }

        // Fetch _fbp and _fbc cookies
        if (request()->hasCookie('_fbp')) {
            $userData['fbp'] = request()->cookie('_fbp');
        }
        if (request()->hasCookie('_fbc')) {
            $userData['fbc'] = request()->cookie('_fbc');
        }

        // Prepare Facebook Event payload
        $event = [
            'event_name' => $eventName,
            'event_time' => time(),
            'event_id' => $eventId,
            'event_source_url' => request()->fullUrl(),
            'action_source' => 'website',
            'user_data' => $userData,
        ];

        if (!empty($customData)) {
            $event['custom_data'] = $customData;
        }

        $payload = [
            'data' => [$event]
        ];

        if (!empty($testCode)) {
            $payload['test_event_code'] = $testCode;
        }

        try {
            // Post payload to Facebook Graph API
            Http::timeout(3)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://graph.facebook.com/v19.0/{$pixelId}/events?access_token={$accessToken}", $payload);
        } catch (\Exception $e) {
            Log::error("Facebook CAPI request failed: " . $e->getMessage());
        }

        return $eventId;
    }
}
