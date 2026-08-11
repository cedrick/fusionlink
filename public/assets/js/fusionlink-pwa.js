(function () {
  'use strict';

  var base = typeof window.FUSIONLINK_BASE === 'string' ? window.FUSIONLINK_BASE : '';
  var prefix = base || '';
  var swUrl = prefix + '/sw.js';
  var scope = prefix + '/';
  var loginUrl = prefix + '/login';

  function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches
      || window.navigator.standalone === true;
  }

  function isIos() {
    return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
  }

  function isSecureContext() {
    return window.isSecureContext === true;
  }

  window.FUSIONLINK_PWA = window.FUSIONLINK_PWA || {};
  window.FUSIONLINK_PWA.canInstall = !!window.FUSIONLINK_INSTALL_PROMPT;
  window.FUSIONLINK_PWA.isStandalone = isStandalone();
  window.FUSIONLINK_PWA.isIos = isIos();
  window.FUSIONLINK_PWA.isSecure = isSecureContext();

  var deferredPrompt = window.FUSIONLINK_INSTALL_PROMPT || null;

  function registerServiceWorker() {
    if (!('serviceWorker' in navigator) || !isSecureContext()) {
      return;
    }

    navigator.serviceWorker.register(swUrl, { scope: scope })
      .then(function (reg) {
        window.FUSIONLINK_PWA.swReady = true;
        window.FUSIONLINK_PWA.swScope = reg.scope;
        document.dispatchEvent(new CustomEvent('fusionlink-pwa-status'));
      })
      .catch(function (err) {
        window.FUSIONLINK_PWA.swError = String(err && err.message ? err.message : err);
        document.dispatchEvent(new CustomEvent('fusionlink-pwa-status'));
      });
  }

  registerServiceWorker();

  var banner = document.getElementById('fusionlinkInstallBanner');
  var installBtn = document.getElementById('fusionlinkInstallBtn');
  var dismissBtn = document.getElementById('fusionlinkInstallDismiss');
  var dismissed = false;

  try {
    dismissed = sessionStorage.getItem('fusionlink_install_dismissed') === '1';
  } catch (e) {}

  function showBanner() {
    if (!banner || dismissed || isStandalone()) {
      return;
    }
    banner.classList.add('is-visible');
  }

  function runPrompt() {
    if (!deferredPrompt) {
      return Promise.resolve('unavailable');
    }

    deferredPrompt.prompt();
    return deferredPrompt.userChoice.then(function (choice) {
      deferredPrompt = null;
      window.FUSIONLINK_INSTALL_PROMPT = null;
      window.FUSIONLINK_PWA.canInstall = false;
      if (banner) {
        banner.classList.remove('is-visible');
      }
      return choice.outcome;
    });
  }

  function waitForPrompt(maxMs) {
    return new Promise(function (resolve) {
      if (deferredPrompt) {
        resolve(true);
        return;
      }

      var done = false;
      var timeout = setTimeout(function () {
        if (done) {
          return;
        }
        done = true;
        resolve(false);
      }, maxMs || 8000);

      function onReady() {
        if (done) {
          return;
        }
        if (deferredPrompt) {
          done = true;
          clearTimeout(timeout);
          document.removeEventListener('fusionlink-install-ready', onReady);
          resolve(true);
        }
      }

      document.addEventListener('fusionlink-install-ready', onReady);
    });
  }

  function warmInstall(targetLogin) {
    try {
      sessionStorage.setItem('fusionlink_want_install', '1');
    } catch (e) {}
    window.location.href = targetLogin || loginUrl;
    return Promise.resolve('warming');
  }

  window.fusionlinkInstallApp = function (opts) {
    opts = opts || {};
    var targetLogin = opts.loginUrl || loginUrl;

    if (isStandalone()) {
      window.location.href = targetLogin;
      return Promise.resolve('already');
    }

    if (deferredPrompt) {
      return runPrompt();
    }

    if (isIos()) {
      return warmInstall(targetLogin);
    }

    return waitForPrompt(2500).then(function (ready) {
      if (ready && deferredPrompt) {
        return runPrompt();
      }
      return warmInstall(targetLogin);
    });
  };

  function tryAutoInstallFromLogin() {
    var want = false;
    try {
      want = sessionStorage.getItem('fusionlink_want_install') === '1';
    } catch (e) {}

    if (!want || isStandalone()) {
      return;
    }

    waitForPrompt(12000).then(function (ready) {
      if (!ready || !deferredPrompt) {
        return;
      }
      try {
        sessionStorage.removeItem('fusionlink_want_install');
      } catch (e) {}
      runPrompt();
    });
  }

  if (window.FUSIONLINK_INSTALL_PROMPT) {
    deferredPrompt = window.FUSIONLINK_INSTALL_PROMPT;
    showBanner();
  }

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    window.FUSIONLINK_INSTALL_PROMPT = e;
    window.FUSIONLINK_PWA.canInstall = true;
    showBanner();
    document.dispatchEvent(new CustomEvent('fusionlink-install-ready'));
  });

  if (installBtn) {
    installBtn.addEventListener('click', function () {
      window.fusionlinkInstallApp();
    });
  }

  if (dismissBtn) {
    dismissBtn.addEventListener('click', function () {
      if (banner) {
        banner.classList.remove('is-visible');
      }
      try {
        sessionStorage.setItem('fusionlink_install_dismissed', '1');
      } catch (e) {}
    });
  }

  document.querySelectorAll('[data-fusionlink-install]').forEach(function (el) {
    el.addEventListener('click', function (event) {
      event.preventDefault();
      window.fusionlinkInstallApp();
    });
  });

  if (/\/login\/?$/i.test(window.location.pathname)) {
    window.addEventListener('load', tryAutoInstallFromLogin);
  }
})();
