document.addEventListener('DOMContentLoaded', function () {
    const useBtn = document.getElementById('use-questions-btn');
    const sentAgeEl = document.getElementById('sent-age');

    // read sent timestamp from server-rendered data via dataset on body (fallback)
    // We embed the timestamp as a JS var by reading a hidden meta tag if present
    let sentAt = 0;
    const meta = document.querySelector('meta[name="admin-passwordless-sent-at"]');
    if (meta) {
        sentAt = parseInt(meta.content, 10) || 0;
    }

    // also try server-rendered PHP variable if provided in markup as data attribute
    const form = document.getElementById('use-questions-form');
    if (!form) return;

    // if no sentAt available, enable button immediately (server will re-check)
    if (!sentAt || sentAt <= 0) {
        useBtn.disabled = false;
        return;
    }

    function updateTimer() {
        const now = Math.floor(Date.now() / 1000);
        const age = Math.max(0, now - sentAt);
        if (sentAgeEl) sentAgeEl.textContent = age + 's';
        if (age >= 60) {
            useBtn.disabled = false;
            if (sentAgeEl) sentAgeEl.textContent = Math.floor(age / 60) + ' min';
            clearInterval(interval);
        }
    }

    updateTimer();
    const interval = setInterval(updateTimer, 1000);
});
