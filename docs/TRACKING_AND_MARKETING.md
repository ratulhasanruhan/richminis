# Tracking & Marketing — Audit and Proposed Services

Findings from a code audit done 2026-08-25, before any of this was implemented. Kept here so
future work starts from what's actually true in the code, not from memory of this conversation.

## What already exists

### Facebook Conversions API (server-side) — working, but with real gaps

`app/Utility/FacebookCapiUtility.php` sends `ViewContent`, `AddToCart`, `InitiateCheckout`,
and `Purchase` events to Meta's Graph API, called from:
- `HomeController.php` (ViewContent, product page)
- `CartController.php` (AddToCart)
- `CheckoutController.php` (InitiateCheckout, Purchase)

It correctly SHA256-hashes email/phone/name/address, forwards `_fbp`/`_fbc` cookies, and
uses a shared `event_id` between the client pixel and the server call for deduplication —
this part is genuinely well-built, not a stub.

Problems:
- **Every call is synchronous, inline, in the request thread.** No queue exists anywhere in
  this app (`QUEUE_CONNECTION=sync` in `.env`, no `app/Jobs` directory, nothing implements
  `ShouldQueue`). A slow Meta API response adds latency directly to checkout.
- **Failures are silent** — wrapped in try/catch, logged to `storage/logs`, nobody looks at it.
  No visibility into whether tracking is actually working.
- **Secrets stored in plaintext `.env`**, written via string-replace from the admin form
  (`BusinessSettingsController::facebook_pixel_update` → `overWriteEnvFile()`).
- No server-side `PageView` event.

### Google Analytics / GTM — client-side only

- `gtag.js` fires client-side (`resources/views/frontend/layouts/app.blade.php`), gated by
  an admin toggle (`get_setting('google_analytics')`) reading a `TRACKING_ID` env var.
- **GTM container `GTM-WTW4TNN5` is hardcoded** directly in the layout template (not the same
  setting as above), loads unconditionally on every page, and has no admin control at all.
  Need to confirm with the client whether this is even their container.
- **No GA4 Measurement Protocol (server-side) implementation exists anywhere.** Zero code.
- GTM dataLayer e-commerce events only cover `add_to_cart` — no `purchase`, `begin_checkout`,
  or `view_item` pushed anywhere.

### No other ad pixels wired in

No TikTok, Snapchat, Pinterest, etc. Only a free-text "header script" admin field exists as
a manual escape hatch.

### Confirmed gaps found in the wider marketing audit

- **No UTM/campaign capture** — nothing in the codebase reads `utm_source`/`utm_campaign`
  from the URL or attaches it to an order. No way to answer "which ad drove this sale" from
  the store's own data.
- **No product structured data (schema.org/JSON-LD)** on product pages — no rich snippets
  in Google search, no feed into free Google Shopping listings via structured data.
- **No abandoned-cart capture or remarketing trigger.**
- **No cookie consent banner anywhere on the site** — a real risk to ad accounts, not just
  a legal checkbox, since Meta/Google increasingly require a consent signal before firing
  pixels/CAPI in regulated regions.
- **Affiliate system already built but check whether it's actually turned on/promoted**
  (`AffiliateConfig`, `AffiliateLog`, `AffiliateUser` models — full commission tracking exists).
- **Club points/loyalty system already built** (`ClubPoint`, `ClubPointDetail`) — same question.
- **Newsletter capture exists** (`Subscriber` model) but no automation found wired to it.

### Data available for server-side events (confirmed, no new schema needed)

Order value, line items, and customer email/phone/address are all reachable server-side
already — via `shipping_address` JSON on the order, or the `User`/`Address` relations. This
is exactly what the existing Facebook `Purchase` call already extracts, so a GA4 equivalent
can reuse the same approach.

## What "server-side tracking" means (for reference)

Browser-based tracking (the Meta Pixel, `gtag.js`) is increasingly blocked by Safari/Firefox's
built-in tracking prevention, ad blockers, and iOS App Tracking Transparency — so a real share
of actual sales never gets reported back to the ad platforms, making reported ROAS look worse
than it is. Server-side tracking means the store's own server sends the same event directly to
Meta/Google's API using data it already has, bypassing the browser entirely. The more advanced
version of this — a **server-side GTM container** — sits between the site and every ad platform,
so the site sends one event and the container fans it out to Meta, GA4, TikTok, etc.

## Proposed services

1. **Server-Side Conversion Tracking (Meta + GA4)** — fix/complete Facebook CAPI, build GA4
   Measurement Protocol from scratch (reusing the CAPI utility's pattern), move both onto a
   queue instead of blocking the request.
2. **Tracking Health & Attribution Dashboard** — admin page showing whether tracking is
   actually firing (last successful event, failure count), plus UTM/campaign source per order.
   Not a GA4/Ads-Manager clone — those already do reporting better; this shows what they can't.
3. **GTM Cleanup & Setup** — resolve the unmanaged hardcoded container, wire it properly into
   admin settings.
4. **Cookie Consent & Compliance** — consent banner gating tracking scripts.
5. **Campaign Attribution Capture (UTM Tracking)** — capture and store UTM source/campaign
   per order.
6. **Product Rich Snippets (Schema Markup)** — structured data on product pages.
7. **Abandoned Cart Remarketing** — capture + trigger recovery (email + ad audience).
8. **Affiliate & Loyalty Program Activation** — turn on and promote the existing but
   apparently unused affiliate and club-points systems.
9. **Newsletter Automation** — connect the existing subscriber list to real email automation.

Nothing above has been implemented yet — this is the audit and proposal only.
