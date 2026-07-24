/**
 * Pull-to-refresh para móvil.
 * El layout usa body { overflow: hidden }, así que el refresh nativo del navegador no corre.
 */
(function () {
    'use strict';

    var THRESHOLD = 90;
    var MAX_PULL = 140;
    var startY = 0;
    var pulling = false;
    var armed = false;
    var pullDistance = 0;
    var indicator = null;

    function isBlocked() {
        if (document.querySelector('.modal.show')) {
            return true;
        }
        if (document.querySelector('.container-section--playing')) {
            return true;
        }
        if (document.body.classList.contains('modal-open')) {
            return true;
        }
        if (document.querySelector('.swal2-container')) {
            return true;
        }
        return false;
    }

    function getScrollTop() {
        var candidates = [
            document.querySelector('.admin-profile-scroll'),
            document.querySelector('.start-section'),
            document.querySelector('.store-panel-fit'),
            document.querySelector('.operator-panel-fit'),
            document.querySelector('#content-page > .board-section'),
            document.scrollingElement,
            document.documentElement,
            document.body
        ];

        for (var i = 0; i < candidates.length; i++) {
            var el = candidates[i];
            if (!el) {
                continue;
            }
            if (el === document.body || el === document.documentElement || el === document.scrollingElement) {
                if ((el.scrollTop || window.pageYOffset || 0) > 2) {
                    return el.scrollTop || window.pageYOffset || 0;
                }
                continue;
            }
            if (el.scrollHeight > el.clientHeight + 4) {
                return el.scrollTop || 0;
            }
        }

        return window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
    }

    function ensureIndicator() {
        if (indicator) {
            return;
        }

        indicator = document.createElement('div');
        indicator.id = 'pull-to-refresh-indicator';
        indicator.setAttribute('aria-hidden', 'true');
        indicator.innerHTML = '<i class="fa-duotone fa-arrows-rotate ptr-spinner" aria-hidden="true"></i>';
        document.body.appendChild(indicator);
    }

    function setPull(distance) {
        ensureIndicator();
        pullDistance = distance;
        var progress = Math.min(distance / THRESHOLD, 1);
        var offset = Math.min(distance, MAX_PULL);
        indicator.style.transform = 'translate(-50%, ' + (offset - 56) + 'px)';
        indicator.style.opacity = String(Math.min(progress * 1.2, 1));
        indicator.classList.toggle('ptr-ready', distance >= THRESHOLD);
    }

    function resetPull() {
        pullDistance = 0;
        if (!indicator) {
            return;
        }
        indicator.style.transform = 'translate(-50%, -80px)';
        indicator.style.opacity = '0';
        indicator.classList.remove('ptr-ready', 'ptr-loading');
    }

    function triggerRefresh() {
        ensureIndicator();
        indicator.classList.add('ptr-loading');
        indicator.style.transform = 'translate(-50%, 12px)';
        indicator.style.opacity = '1';
        setTimeout(function () {
            window.location.reload();
        }, 250);
    }

    document.addEventListener('touchstart', function (e) {
        if (isBlocked() || !e.touches || e.touches.length !== 1) {
            armed = false;
            return;
        }
        if (getScrollTop() > 2) {
            armed = false;
            return;
        }
        startY = e.touches[0].clientY;
        pulling = false;
        pullDistance = 0;
        armed = true;
    }, { passive: true });

    document.addEventListener('touchmove', function (e) {
        if (!armed || isBlocked() || !e.touches || e.touches.length !== 1) {
            return;
        }
        if (getScrollTop() > 2) {
            armed = false;
            resetPull();
            return;
        }

        var dy = e.touches[0].clientY - startY;
        if (dy < 8) {
            return;
        }

        pulling = true;
        setPull(dy * 0.55);
    }, { passive: true });

    document.addEventListener('touchend', function () {
        if (!armed) {
            return;
        }

        var shouldRefresh = pulling && pullDistance >= THRESHOLD;

        armed = false;
        pulling = false;

        if (shouldRefresh && !isBlocked()) {
            triggerRefresh();
            return;
        }

        resetPull();
    }, { passive: true });

    document.addEventListener('touchcancel', function () {
        armed = false;
        pulling = false;
        resetPull();
    }, { passive: true });
})();
