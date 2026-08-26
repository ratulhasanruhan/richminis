{{--
    Gates GTM/gtag/Meta Pixel (all rendered further up in app.blade.php, behind a server-side
    check on this same cookie) in addition to showing this banner. Nothing else on the page
    depends on this cookie, so declining never breaks any site functionality - only ads/analytics.
--}}
@if (!request()->cookie('cookie_consent'))
    <div id="aiz-cookie-consent" class="position-fixed d-flex flex-wrap align-items-center justify-content-between p-20px"
        style="left:0; right:0; bottom:0; z-index:1080; background:#1a1a1a; color:#f5f5f5; gap:16px; box-shadow:0 -2px 12px rgba(0,0,0,.2);">
        <div style="flex:1 1 320px; font-size:14px; line-height:1.5;">
            {{ translate('We use cookies to keep the site running smoothly, remember your cart, and show you relevant ads on Facebook and Google. Choose whether we can use cookies for analytics and advertising - the site works either way.') }}
            <a href="{{ route('privacypolicy') }}" target="_blank" class="text-white" style="text-decoration:underline;">{{ translate('Privacy Policy') }}</a>
        </div>
        <div class="d-flex flex-wrap" style="gap:10px;">
            <button type="button" id="aiz-cookie-decline" class="btn btn-sm" style="background:transparent;border:1px solid #f5f5f5;color:#f5f5f5;">
                {{ translate('Necessary Only') }}
            </button>
            <button type="button" id="aiz-cookie-accept" class="btn btn-sm btn-primary">
                {{ translate('Accept All') }}
            </button>
        </div>
    </div>

    <script>
        (function () {
            function setConsent(value) {
                // 1 year, site-wide, readable by PHP too (not httpOnly) so both the tracking
                // scripts below and FacebookCapiUtility's server-side check can see the same value.
                document.cookie = 'cookie_consent=' + value + ';path=/;max-age=31536000;SameSite=Lax';
            }

            document.getElementById('aiz-cookie-accept').addEventListener('click', function () {
                setConsent('accepted');
                // Reload rather than injecting the tracking scripts inline here: app.blade.php
                // already knows exactly what to render once it sees the cookie server-side, so
                // this avoids maintaining the GTM/gtag/Pixel init code in two places.
                window.location.reload();
            });

            document.getElementById('aiz-cookie-decline').addEventListener('click', function () {
                setConsent('declined');
                document.getElementById('aiz-cookie-consent').remove();
            });
        })();
    </script>
@endif
