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
        $firstName = null;
        $lastName = null;
        $city = null;
        $country = null;
        $state = null;
        $zip = null;

        if (auth()->check()) {
            $user = auth()->user();
            $email = $user->email;
            $phone = $user->phone;
            $parts = explode(' ', trim($user->name));
            $firstName = $parts[0] ?? null;
            $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : null;

            $default_address = $user->addresses()->where('set_default', 1)->first() ?? $user->addresses()->first();
            if ($default_address) {
                $city = $default_address->city->name ?? null;
                $state = $default_address->state->name ?? null;
                $zip = $default_address->postal_code ?? null;
                if ($default_address->country) {
                    $country = $default_address->country->code ?? null;
                }
            }
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
        if (isset($customData['_user_first_name'])) {
            $firstName = $customData['_user_first_name'];
            unset($customData['_user_first_name']);
        }
        if (isset($customData['_user_last_name'])) {
            $lastName = $customData['_user_last_name'];
            unset($customData['_user_last_name']);
        }
        if (isset($customData['_user_city'])) {
            $city = $customData['_user_city'];
            unset($customData['_user_city']);
        }
        if (isset($customData['_user_country'])) {
            $country = $customData['_user_country'];
            unset($customData['_user_country']);
        }
        if (isset($customData['_user_state'])) {
            $state = $customData['_user_state'];
            unset($customData['_user_state']);
        }
        if (isset($customData['_user_zip'])) {
            $zip = $customData['_user_zip'];
            unset($customData['_user_zip']);
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
        if ($firstName) {
            $userData['fn'] = [hash('sha256', strtolower(trim($firstName)))];
        }
        if ($lastName) {
            $userData['ln'] = [hash('sha256', strtolower(trim($lastName)))];
        }
        if ($city) {
            $userData['ct'] = [hash('sha256', str_replace(' ', '', strtolower(trim($city))))];
        }
        if ($country) {
            $userData['country'] = [hash('sha256', strtolower(trim($country)))];
        }
        if ($state) {
            $userData['st'] = [hash('sha256', str_replace(' ', '', strtolower(trim($state))))];
        }
        if ($zip) {
            $userData['zp'] = [hash('sha256', str_replace(' ', '', strtolower(trim($zip))))];
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
