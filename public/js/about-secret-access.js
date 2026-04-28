(function () {
    var root = document.body;
    if (!root) {
        return;
    }

    var targetUrl = root.getAttribute('data-secret-access-url');
    if (!targetUrl) {
        return;
    }

    var logo = document.querySelector('.brand__icon');
    if (!logo) {
        return;
    }

    var lastClickAt = 0;
    var hasRedirected = false;

    function redirectToSecretAccess() {
        if (hasRedirected) {
            return;
        }

        hasRedirected = true;
        window.location.href = targetUrl;
    }

    logo.addEventListener('dblclick', redirectToSecretAccess);

    logo.addEventListener('click', function () {
        var now = Date.now();
        if (now - lastClickAt <= 450) {
            redirectToSecretAccess();
            return;
        }

        lastClickAt = now;
    });
})();
