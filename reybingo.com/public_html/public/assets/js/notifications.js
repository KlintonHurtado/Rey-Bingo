/**
 * Rey Bingo - Gestor de Notificaciones Push para Móvil y Escritorio
 */
class NotificationManager {
    constructor() {
        this.isSupported = ('serviceWorker' in navigator) && ('PushManager' in window) && ('Notification' in window);
        this.registration = null;
        this.vapidPublicKey = 'BHdM69ue9dsb7wxXHWJrea_2F45sWLUd2hx33imtzLotBrIeqReJWcSHW48hCkL7iU5XGns96G7G6ViIC6MC8nI';
        this.init();
    }

    async init() {
        if (!this.isSupported) {
            console.log('ℹ️ Push notifications no están soportadas en este navegador');
            return;
        }

        try {
            // Registrar service worker en la raíz
            this.registration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
            console.log('✅ Service Worker registrado para notificaciones');

            await navigator.serviceWorker.ready;

            // Verificar y sincronizar estado
            await this.syncStatus();

        } catch (error) {
            console.error('❌ Error inicializando Service Worker push:', error);
        }
    }

    async syncStatus() {
        if (!this.registration) return;

        try {
            const permission = Notification.permission;
            console.log('🔔 Estado de permisos de notificación:', permission);

            if (permission === 'granted') {
                let subscription = await this.registration.pushManager.getSubscription();
                if (!subscription) {
                    console.log('🔄 Permiso otorgado pero sin suscripción activa, creando...');
                    await this.subscribe();
                } else {
                    console.log('✅ Suscripción push activa encontrada, sincronizando...');
                    await this.saveSubscriptionToServer(subscription);
                }
                this.hideBanner();
            } else if (permission === 'default') {
                // Verificar si no se ha descartado recientemente
                const dismissedTime = localStorage.getItem('push-banner-dismissed');
                const now = Date.now();
                // Mostrar banner cada 24 horas si no ha aceptado
                if (!dismissedTime || (now - parseInt(dismissedTime, 10)) > (24 * 60 * 60 * 1000)) {
                    this.showBanner();
                }
            } else if (permission === 'denied') {
                this.hideBanner();
            }
        } catch (e) {
            console.error('Error sincronizando estado de notificaciones:', e);
        }
    }

    showBanner() {
        const banner = document.getElementById('rey-push-banner');
        if (banner) {
            banner.style.display = 'block';
            banner.classList.add('show');
        }
    }

    hideBanner() {
        const banner = document.getElementById('rey-push-banner');
        if (banner) {
            banner.style.display = 'none';
            banner.classList.remove('show');
        }
    }

    async requestPermission() {
        if (!this.isSupported) {
            alert('Tu navegador no admite notificaciones push.');
            return false;
        }

        try {
            console.log('🔔 Solicitando permiso al usuario...');
            const permission = await Notification.requestPermission();
            console.log('Respuesta de permiso:', permission);

            if (permission === 'granted') {
                this.hideBanner();
                const success = await this.subscribe();
                if (success) {
                    this.showToast('¡Notificaciones activadas! Te avisaremos al teléfono cuando empiece una partida en vivo.', 'success');
                }
                return success;
            } else if (permission === 'denied') {
                this.hideBanner();
                this.showToast('Notificaciones bloqueadas. Para activarlas, habilítalas en los ajustes de tu navegador.', 'warning');
                return false;
            } else {
                return false;
            }
        } catch (error) {
            console.error('Error solicitando permisos:', error);
            return false;
        }
    }

    async subscribe() {
        if (!this.registration) {
            console.warn('Service Worker aún no registrado');
            return false;
        }

        try {
            console.log('🔄 Creando suscripción Push con clave VAPID...');
            const convertedKey = this.urlBase64ToUint8Array(this.vapidPublicKey);

            const subscription = await this.registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: convertedKey
            });

            console.log('✅ Suscripción WebPush obtenida:', subscription);
            return await this.saveSubscriptionToServer(subscription);
        } catch (error) {
            console.error('❌ Error suscribiendo a WebPush:', error);
            return false;
        }
    }

    async saveSubscriptionToServer(subscription) {
        try {
            const baseUrl = (typeof site_url !== 'undefined' ? site_url : '/').replace(/\/$/, '') + '/';
            const endpoint = baseUrl + 'notifications/subscribe';

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(subscription)
            });

            const result = await response.json();
            console.log('📡 Respuesta de guardado en servidor:', result);
            return result && result.success;
        } catch (err) {
            console.error('Error enviando suscripción al servidor:', err);
            return false;
        }
    }

    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    showToast(message, type = 'info') {
        if (typeof toastr !== 'undefined' && toastr[type]) {
            toastr[type](message, 'Rey Bingo');
            return;
        }

        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            bottom: 25px;
            right: 25px;
            background: ${type === 'success' ? '#10b981' : type === 'warning' ? '#f59e0b' : '#3b82f6'};
            color: #ffffff;
            padding: 14px 22px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            z-index: 99999;
            font-size: 14px;
            font-family: sans-serif;
            font-weight: 600;
            max-width: 350px;
            transition: opacity 0.3s ease;
        `;
        toast.innerHTML = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
}

// Inicialización global
document.addEventListener('DOMContentLoaded', function() {
    window.notificationManager = new NotificationManager();

    // Listener para el botón activar en banner
    const activateBtn = document.getElementById('btn-activate-push');
    if (activateBtn) {
        activateBtn.addEventListener('click', function() {
            window.notificationManager.requestPermission();
        });
    }

    // Listener para el botón descartar
    const dismissBtn = document.getElementById('btn-dismiss-push');
    if (dismissBtn) {
        dismissBtn.addEventListener('click', function() {
            window.notificationManager.hideBanner();
            localStorage.setItem('push-banner-dismissed', Date.now().toString());
        });
    }
});

window.requestPushNotifications = function() {
    if (window.notificationManager) {
        return window.notificationManager.requestPermission();
    }
};