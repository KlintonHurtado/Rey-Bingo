/**
 * Ayudas de consola para probar notificaciones in-app (solo desarrollo).
 */
(function () {
    function formatTimeDev(dateString) {
        const isoString = String(dateString).replace(' ', 'T');
        const date = new Date(isoString);
        if (Number.isNaN(date.getTime())) {
            return 'Ahora';
        }
        return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    }

    function fallbackShowNotification(payload) {
        const container = document.getElementById('notificationsContainer');
        if (!container) {
            console.error('No existe #notificationsContainer en esta página.');
            return;
        }
        if (typeof attachNotificationSwipeDismiss !== 'function') {
            console.error('Falta el archivo in-app-notifications.js. Recarga con Ctrl+F5.');
            return;
        }

        const el = document.createElement('div');
        el.className = 'notification notification-' + (payload.type || 'info');
        el.innerHTML =
            '<div class="notification-header"><h6 class="notification-title">' + payload.title + '</h6></div>' +
            '<div class="notification-message">' + payload.message + '</div>' +
            '<span class="notification-hint">Desliza a la derecha para cerrar</span>' +
            '<span class="notification-time mt-1">' + formatTimeDev(payload.created_at) + '</span>';
        container.appendChild(el);
        setTimeout(function () {
            el.classList.add('show');
        }, 100);

        const dismiss = function (node) {
            node.classList.add('hide');
            setTimeout(function () {
                if (node.parentNode) {
                    node.remove();
                }
            }, 300);
        };
        attachNotificationSwipeDismiss(el, dismiss);
        setTimeout(function () {
            dismiss(el);
        }, 15000);
    }

    window.testAppNotification = function () {
        const payload = {
            title: 'Prueba',
            message: 'Desliza hacia la derecha para cerrar esta notificación.',
            created_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
            type: 'info',
        };
        if (typeof window.showNotification === 'function') {
            window.showNotification(payload);
            return;
        }
        fallbackShowNotification(payload);
    };

    window.triggerTestServerNotification = async function () {
        try {
            const response = await fetch('/users/testNotification', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await response.json();
            if (data.ok) {
                if (typeof window.loadNotifications === 'function') {
                    window.loadNotifications();
                } else {
                    console.info('Notificación creada (id ' + data.id + '). Recarga o espera el polling.');
                }
                return;
            }
            console.error('No se pudo crear la notificación:', data);
        } catch (err) {
            console.error('Error de red:', err);
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        console.info('%cRey Bingo — prueba de notificaciones', 'color:#8767fa;font-weight:bold;font-size:13px');
        console.info('1) Escribe en esta línea (abajo):  testAppNotification()');
        console.info('2) Desde servidor:  triggerTestServerNotification()');
    });
})();
