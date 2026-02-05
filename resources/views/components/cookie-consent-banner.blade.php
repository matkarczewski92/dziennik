<div id="cookie-consent-banner" class="cookie-consent-banner d-none" role="region" aria-label="Informacja o cookies">
    <div class="cookie-consent-content">
        <p class="mb-2 mb-md-0">
            Uzywamy plikow cookie niezbednych do dzialania serwisu (m.in. logowanie, bezpieczenstwo, utrzymanie sesji).
            Korzystajac dalej ze strony, potwierdzasz zapoznanie sie z ta informacja.
        </p>
        <button type="button" id="cookie-consent-accept" class="btn btn-sm btn-primary">Rozumiem</button>
    </div>
</div>

<noscript>
    <div class="alert alert-info m-3" role="alert">
        Uzywamy plikow cookie niezbednych do dzialania serwisu.
    </div>
</noscript>

<script>
    (function () {
        const STORAGE_KEY = 'cookie_notice_accepted_v1';
        const COOKIE_NAME = 'cookie_notice_accepted';
        const MAX_AGE_SECONDS = 31536000; // 365 dni

        const banner = document.getElementById('cookie-consent-banner');
        const button = document.getElementById('cookie-consent-accept');

        if (!banner || !button) {
            return;
        }

        const hasCookieConsent = () => {
            const storageAccepted = window.localStorage.getItem(STORAGE_KEY) === '1';
            const cookieAccepted = document.cookie.split(';').some((entry) => entry.trim().startsWith(COOKIE_NAME + '=1'));
            return storageAccepted || cookieAccepted;
        };

        const persistConsent = () => {
            window.localStorage.setItem(STORAGE_KEY, '1');
            document.cookie = COOKIE_NAME + '=1; Max-Age=' + MAX_AGE_SECONDS + '; Path=/; SameSite=Lax';
        };

        if (!hasCookieConsent()) {
            banner.classList.remove('d-none');
        }

        button.addEventListener('click', function () {
            persistConsent();
            banner.classList.add('d-none');
        });
    })();
</script>
