document.addEventListener('DOMContentLoaded', function () {
    const banner = document.querySelector('.cookie-consent');
    if (!banner) return;

    function hasCookie(name) {
        if (document.cookie.length === 0) return false;
        const cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
            const cookie = cookies[i].trim();
            if (cookie.indexOf(name + '=') === 0) {
                return true;
            }
        }
        return false;
    }

    function ensureBannerVisible() {
        // if cookie exists, hide banner; otherwise show it
        if (hasCookie('lgpd_cookie_consent')) {
            banner.style.display = 'none';
            return true;
        } else {
            banner.style.display = 'flex';
            return false;
        }
    }

    // check immediately on page load
    ensureBannerVisible();

    // periodically re-check if tab was switched (cookie might have been set elsewhere)
    setInterval(function () {
        ensureBannerVisible();
    }, 1000);

    // also listen for storage events (if cookie changes in another tab - won't work for httponly cookies)
    // but we check periodically anyway
    window.addEventListener('focus', function () {
        ensureBannerVisible();
    });
});
