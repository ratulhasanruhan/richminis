<?php

namespace App\Http\Middleware;

use Closure;
use Session;

class CaptureUtmParameters
{
    /**
     * UTM params only appear on the ad-click landing page, not on every page after - so they're
     * captured into the session here (last-touch: a new utm_source overwrites the old one) and
     * read back out of the session whenever an order is actually placed, however many pages later
     * that happens to be.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->has('utm_source') || $request->has('gclid') || $request->has('fbclid')) {
            Session::put('utm_data', [
                'utm_source' => $request->query('utm_source'),
                'utm_medium' => $request->query('utm_medium'),
                'utm_campaign' => $request->query('utm_campaign'),
                'utm_term' => $request->query('utm_term'),
                'utm_content' => $request->query('utm_content'),
                // Google/Meta's own click IDs - useful even when a campaign strips utm_* tags,
                // and needed later to correlate an order back to a specific ad click.
                'gclid' => $request->query('gclid'),
                'fbclid' => $request->query('fbclid'),
                'landing_page' => $request->path(),
                'referrer' => $request->headers->get('referer'),
                'captured_at' => now()->toDateTimeString(),
            ]);
        }

        return $next($request);
    }
}
