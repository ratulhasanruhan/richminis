<?php

namespace App\Http\Middleware;

use Closure;

class SendServerSidePageView
{
    /**
     * PageView has only ever existed as a client-side fbq('track', 'PageView') call in this
     * codebase - there was never a server-side equivalent. That means it depends entirely on the
     * visitor's browser actually executing that script: an ad blocker, Safari's tracking
     * prevention, or anything else that stops the client-side Pixel from firing means PageView
     * (and only PageView - every other event already has a server-side CAPI counterpart) goes
     * completely unreported, even though the visit genuinely happened and the server saw it.
     *
     * Runs after the response, not before, so it only fires for real page loads - not redirects,
     * not error pages, not JSON/API responses, and not the XML catalog feed (also routed through
     * this same middleware group). Admin/seller/staff/delivery-boy areas run through the exact
     * same 'web' middleware group as the storefront in this app, so those are explicitly excluded
     * by user type rather than by URL, since routes/admin.php has no distinguishing URL prefix.
     */
    public function handle($request, Closure $next)
    {
        // Shared with the client-side fbq('track', 'PageView', ...) call in
        // frontend.layouts.app so the two get deduplicated as one event, not counted twice, on
        // whichever devices the browser-side Pixel *does* manage to fire on. Sharing this before
        // $next() runs (rather than after, alongside the actual send below) means it reaches the
        // view even though only admin/backend layouts never reference it, so it's harmless there.
        $eventId = 'page_view_' . uniqid();
        \Illuminate\Support\Facades\View::share('pageViewCapiEventId', $eventId);

        $response = $next($request);

        if ($this->isTrackablePageView($request, $response)) {
            try {
                \App\Utility\FacebookCapiUtility::sendEvent('PageView', [], $eventId);
            } catch (\Exception $e) {
                // Fail silently - a tracking failure should never affect the page itself.
            }
        }

        return $response;
    }

    private function isTrackablePageView($request, $response)
    {
        if (!$request->isMethod('GET') || $request->ajax() || $request->wantsJson()) {
            return false;
        }

        if ($response->getStatusCode() >= 300) {
            return false;
        }

        if (strpos((string) $response->headers->get('Content-Type'), 'text/html') !== 0) {
            return false;
        }

        if (auth()->check() && auth()->user()->user_type !== 'customer') {
            return false;
        }

        return true;
    }
}
