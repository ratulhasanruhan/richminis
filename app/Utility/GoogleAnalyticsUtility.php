<?php

namespace App\Utility;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleAnalyticsUtility
{
    /**
     * Send an event to GA4 via the Measurement Protocol (server-side).
     *
     * Mirrors FacebookCapiUtility::sendEvent's contract deliberately: callers pass the same
     * Facebook-style `contents` array (id/quantity/item_price) they already build for the CAPI
     * call, and this method reshapes it into GA4's items format - so call sites add one line,
     * not a second parallel set of ecommerce-building logic.
     *
     * @param string $eventName GA4 recommended event name (view_item, add_to_cart, begin_checkout, purchase)
     * @param array $params value, currency, contents (Facebook-shaped), transaction_id (purchase only)
     * @return void
     */
    public static function sendEvent($eventName, $params = [])
    {
        $measurementId = env('TRACKING_ID');
        $apiSecret = env('GA4_MEASUREMENT_PROTOCOL_API_SECRET');

        if (!$measurementId || !$apiSecret || get_setting('google_analytics') != 1) {
            return;
        }

        // Same cookie the frontend consent banner sets/checks (frontend.partials.cookie_consent)
        // - server-side events shouldn't fire for a visitor who hasn't accepted tracking, same as
        // the client-side gtag.js already doesn't load for them (and so never sets _ga either).
        if (request()->cookie('cookie_consent') !== 'accepted') {
            return;
        }

        $clientId = self::resolveClientId();

        $items = [];
        foreach (($params['contents'] ?? []) as $item) {
            $items[] = [
                'item_id' => (string) ($item['id'] ?? ''),
                'quantity' => (int) ($item['quantity'] ?? 1),
                'price' => (float) ($item['item_price'] ?? 0),
            ];
        }

        $eventParams = [
            'currency' => $params['currency'] ?? 'USD',
            'value' => (float) ($params['value'] ?? 0),
            'items' => $items,
            // GA4 drops events with no engagement signal at all from standard reporting; this is
            // the documented minimal safe value when there's no real client-side engagement time
            // to report (we're sending this from the server, not the visitor's browser).
            'engagement_time_msec' => 1,
        ];

        if (!empty($params['transaction_id'])) {
            $eventParams['transaction_id'] = (string) $params['transaction_id'];
        }

        $payload = [
            'client_id' => $clientId,
            'events' => [
                [
                    'name' => $eventName,
                    'params' => $eventParams,
                ],
            ],
        ];

        try {
            // GA4's spec wants measurement_id/api_secret as query params on the URL, and the
            // event data as the JSON body - two different places, not one call.
            Http::timeout(3)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(
                    'https://www.google-analytics.com/mp/collect?measurement_id=' . urlencode($measurementId) . '&api_secret=' . urlencode($apiSecret),
                    $payload
                );
        } catch (\Exception $e) {
            Log::error('GA4 Measurement Protocol request failed: ' . $e->getMessage());
        }
    }

    /**
     * GA4's own client-side gtag.js sets a first-party _ga cookie shaped like
     * "GA1.1.<id-part-1>.<id-part-2>" - the client_id Measurement Protocol wants is just those
     * last two dot-separated segments. Falls back to a random id (still records the event, just
     * without stitching to that visitor's other sessions) when the cookie isn't there yet - e.g.
     * the very first request after consent is accepted, before gtag.js has had a chance to run.
     */
    private static function resolveClientId()
    {
        $ga = request()->cookie('_ga');

        if ($ga) {
            $parts = explode('.', $ga);
            if (count($parts) >= 4) {
                return $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
            }
        }

        return (string) \Illuminate\Support\Str::uuid();
    }
}
