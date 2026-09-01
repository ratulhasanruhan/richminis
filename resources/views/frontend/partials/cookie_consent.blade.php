{{--
    Gates GTM/gtag/Meta Pixel (all rendered further up in app.blade.php, behind a server-side
    check on this same cookie) in addition to showing this banner. Nothing else on the page
    depends on this cookie, so declining never breaks any site functionality - only ads/analytics.

    Click handling uses event delegation on document (not a direct getElementById + addEventListener
    on the buttons) so it doesn't depend on this exact markup still being on the page by the time
    the script runs, and can't be silently broken by some other fixed-position overlay elsewhere on
    the page intercepting the click before it reaches the button.
--}}
@if (!request()->cookie('cookie_consent'))
    <style>
        @media (max-width: 991px) {
            #aiz-cookie-consent {
                bottom: 75px !important;
            }
        }
    </style>
    <div id="aiz-cookie-consent"
        class="position-fixed d-flex flex-wrap align-items-center justify-content-between"
        style="left:16px; right:16px; bottom:16px; z-index:99999; pointer-events:auto; gap:16px;
               background:#fff; color:var(--dark, #292933); border-radius:12px; padding:20px 24px;
               box-shadow:0 8px 30px rgba(0,0,0,.18); border:1px solid var(--soft-light, #dfdfe6);">
        <div style="flex:1 1 320px; font-size:14px; line-height:1.6;">
            <strong style="font-size:15px;">{{ translate('We value your privacy') }}</strong><br>
            {{ translate('We use cookies to keep the site running smoothly, remember your cart, and show you relevant ads on Facebook and Google. The site works either way.') }}
            <a href="{{ route('privacypolicy') }}" target="_blank" style="color:var(--primary, #d43533); text-decoration:underline;">{{ translate('Privacy Policy') }}</a>
        </div>
        <div class="d-flex flex-wrap" style="gap:10px; flex:0 0 auto;">
            <button type="button" data-cookie-consent="declined" class="btn btn-sm"
                style="background:transparent; border:1px solid var(--soft-white, #b5b5bf); color:var(--dark, #292933); border-radius:8px; padding:8px 18px;">
                {{ translate('Necessary Only') }}
            </button>
            <button type="button" data-cookie-consent="accepted" class="btn btn-sm"
                style="background:var(--primary, #d43533); border:none; color:#fff; border-radius:8px; padding:8px 18px; font-weight:600;">
                {{ translate('Accept All') }}
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('click', function (event) {
            var target = event.target.closest('[data-cookie-consent]');
            if (!target) {
                return;
            }

            var value = target.getAttribute('data-cookie-consent');
            // 1 year, site-wide, readable by PHP too (not httpOnly) so both the tracking scripts
            // this cookie gates and FacebookCapiUtility's server-side check can see the same value.
            document.cookie = 'cookie_consent=' + value + ';path=/;max-age=31536000;SameSite=Lax';

            var banner = document.getElementById('aiz-cookie-consent');
            if (banner) {
                banner.remove();
            }

            if (value === 'accepted') {
                // Reload rather than injecting GTM/gtag/Pixel inline here: app.blade.php already
                // knows exactly what to render once it sees the cookie server-side, so this avoids
                // maintaining that init code in two places.
                window.location.reload();
            }
        });
    </script>
@endif
