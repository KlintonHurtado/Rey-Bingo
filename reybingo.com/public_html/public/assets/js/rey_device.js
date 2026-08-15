/**
 * Rey Bingo - Detección Automática de Dispositivo / MAC Virtual
 * Genera y mantiene un identificador de hardware único y persistente en formato MAC standard (XX:XX:XX:XX:XX:XX)
 */
(function() {
    'use strict';

    function generateHardwareMAC() {
        try {
            var screenData = (window.screen.width || 0) + 'x' + (window.screen.height || 0) + 'x' + (window.screen.colorDepth || 0);
            var nav = window.navigator || {};
            var navData = (nav.userAgent || '') + '|' + (nav.language || '') + '|' + (nav.hardwareConcurrency || 4) + '|' + (nav.platform || '');
            var tz = (window.Intl && Intl.DateTimeFormat) ? Intl.DateTimeFormat().resolvedOptions().timeZone : '';
            
            var canvasStr = '';
            try {
                var canvas = document.createElement('canvas');
                canvas.width = 200;
                canvas.height = 50;
                var ctx = canvas.getContext('2d');
                if (ctx) {
                    ctx.textBaseline = 'top';
                    ctx.font = '14px Arial';
                    ctx.fillStyle = '#f60';
                    ctx.fillRect(125, 1, 62, 20);
                    ctx.fillStyle = '#069';
                    ctx.fillText('ReyBingoDevice1.0', 2, 15);
                    ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
                    ctx.fillText('ReyBingoDevice1.0', 4, 17);
                    canvasStr = canvas.toDataURL();
                }
            } catch (e) {}

            var raw = screenData + '|' + navData + '|' + tz + '|' + canvasStr;
            var h1 = 0xdeadbeef, h2 = 0x41c64e6d;
            for (var i = 0; i < raw.length; i++) {
                var ch = raw.charCodeAt(i);
                h1 = Math.imul(h1 ^ ch, 2654435761);
                h2 = Math.imul(h2 ^ ch, 1597334677);
            }
            h1 = Math.imul(h1 ^ (h1 >>> 16), 2246822507) ^ Math.imul(h2 ^ (h2 >>> 13), 3266489909);
            h2 = Math.imul(h2 ^ (h2 >>> 16), 2246822507) ^ Math.imul(h1 ^ (h1 >>> 13), 3266489909);
            
            var hex1 = ('00000000' + (h1 >>> 0).toString(16)).slice(-8);
            var hex2 = ('00000000' + (h2 >>> 0).toString(16)).slice(-4);
            var fullHex = (hex1 + hex2).toUpperCase();
            
            var parts = fullHex.match(/.{1,2}/g);
            return parts ? parts.join(':') : '02:B1:C0:7E:9A:3D';
        } catch (e) {
            return '02:B1:C0:7E:9A:3D';
        }
    }

    function getDeviceMAC() {
        var mac = null;
        try {
            mac = localStorage.getItem('rey_device_mac');
        } catch (e) {}

        if (!mac || !/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/i.test(mac)) {
            mac = generateHardwareMAC();
            try {
                localStorage.setItem('rey_device_mac', mac);
            } catch (e) {}
        }

        try {
            document.cookie = 'rey_device_mac=' + encodeURIComponent(mac) + '; path=/; max-age=31536000; SameSite=Lax';
        } catch (e) {}

        return mac;
    }

    var currentMAC = getDeviceMAC();
    window.REY_DEVICE_MAC = currentMAC;

    function applyGlobalHeaders() {
        if (window.jQuery && window.jQuery.ajaxSetup) {
            window.jQuery.ajaxSetup({
                headers: {
                    'X-Client-MAC': currentMAC,
                    'X-Device-MAC': currentMAC
                }
            });
        }
    }

    if (window.jQuery) {
        applyGlobalHeaders();
    } else {
        document.addEventListener('DOMContentLoaded', applyGlobalHeaders);
    }

    document.addEventListener('submit', function(e) {
        var form = e.target;
        if (form && form.tagName === 'FORM') {
            var existing = form.querySelector('input[name="last_mac"], input[name="mac_address"]');
            if (existing) {
                if (!existing.value) {
                    existing.value = currentMAC;
                }
            } else {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'last_mac';
                input.value = currentMAC;
                form.appendChild(input);
            }
        }
    }, true);
})();
