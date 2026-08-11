(function () {
    'use strict';

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        window.__deferredInstall = e;
        document.querySelectorAll('[data-install-app]').forEach(function (el) {
            el.textContent = 'Install App';
        });
    });

    window.addEventListener('appinstalled', function () {
        window.__deferredInstall = null;
        var loginUrl = document.body.getAttribute('data-login-url');
        if (loginUrl) {
            window.location.href = loginUrl;
        }
    });

    function tryInstall() {
        var prompt = window.__deferredInstall;
        if (!prompt) {
            return false;
        }

        prompt.prompt();
        prompt.userChoice.finally(function () {
            window.__deferredInstall = null;
        });
        return true;
    }

    function bindButtons() {
        document.querySelectorAll('[data-install-app]').forEach(function (el) {
            if (el.dataset.installBound === '1') {
                return;
            }
            el.dataset.installBound = '1';

            el.addEventListener('click', function (event) {
                if (tryInstall()) {
                    event.preventDefault();
                }
            });
        });
    }

    window.installFusionLinkApp = tryInstall;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindButtons);
    } else {
        bindButtons();
    }
})();
