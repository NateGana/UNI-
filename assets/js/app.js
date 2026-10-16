// assets/js/app.js
// Lightweight UI behaviour only (no libraries, no network requests):
//   1. Mobile navigation toggle for the authenticated layout.
//   2. Show / hide password on the login page.
// The pages remain fully usable if this file does not load.
(function () {
    'use strict';

    // ---- 1. Mobile navigation -------------------------------------------
    var toggle   = document.getElementById('navToggle');
    var sidebar  = document.getElementById('appSidebar');
    var backdrop = document.getElementById('navBackdrop');
    var shell    = document.getElementById('appShell');

    if (toggle && sidebar && shell) {
        var setOpen = function (open) {
            shell.classList.toggle('nav-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
        };

        toggle.addEventListener('click', function () {
            setOpen(!shell.classList.contains('nav-open'));
        });

        if (backdrop) {
            backdrop.addEventListener('click', function () { setOpen(false); });
        }

        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && shell.classList.contains('nav-open')) {
                setOpen(false);
                toggle.focus();
            }
        });

        // Going back to a wide screen closes the panel.
        if (window.matchMedia) {
            var wide = window.matchMedia('(min-width: 901px)');
            var onChange = function () { if (wide.matches) { setOpen(false); } };
            if (wide.addEventListener) { wide.addEventListener('change', onChange); }
            else if (wide.addListener) { wide.addListener(onChange); }
        }
    }

    // ---- 2. Password visibility (login page) -----------------------------
    var pwButton = document.querySelector('[data-toggle-password]');
    if (pwButton) {
        var pwField = document.getElementById(pwButton.getAttribute('data-toggle-password'));
        if (pwField) {
            pwButton.addEventListener('click', function () {
                var show = pwField.type === 'password';
                pwField.type = show ? 'text' : 'password';
                pwButton.setAttribute('aria-pressed', show ? 'true' : 'false');
                pwButton.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                pwField.focus();
            });
            // Never leave the password visible when leaving the page.
            window.addEventListener('pagehide', function () { pwField.type = 'password'; });
        }
    }
})();
