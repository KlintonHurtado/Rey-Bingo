/**
 * Rey Bingo - Gestor de Notificaciones Push para Móvil y Escritorio
 */
class NotificationManager {
    constructor() {
        this.isSupported = ('serviceWorker' in navigator) && ('PushManager' in window) && ('Notification' in window);
        this.registration = null;
        this.baseUrl = new URL((typeof site_url !== 'undefined' && site_url) ? site_url : '/', window.location.origin);
        this.vapidPublicKey = null;
        this.init();
    }

    async init() {
        if (!this.isSupported) {
            console.log('ℹ️ Push notifications no están soportadas en este navegador');
            return;
        }

        try {
            const swUrl = new URL('sw.js', this.baseUrl);

            // Mantener un único service worker en el ámbito del sitio para todos los dispositivos.
            this.registration = await navigator.serviceWorker.register(swUrl.href, {
                scope: this.baseUrl.pathname,
                updateViaCache: 'none'
            });
            console.log('✅ Service Worker registrado para notificaciones');

            await navigator.serviceWorker.ready;
            await this.loadVapidPublicKey();

            // Verificar y sincronizar estado
            await this.syncStatus();

        } catch (error) {
            console.error('❌ Error inicializando Service Worker push:', error);
        }
    }

    buildUrl(path) {
        const baseUrl = this.baseUrl.href.endsWith('/') ? this.baseUrl : new URL(this.baseUrl.href + '/');
        return new URL(path.replace(/^\/+/, ''), baseUrl).href;
    }

    async loadVapidPublicKey() {
        const response = await fetch(this.buildUrl('notifications/getPublicKey'), {
            method: 'GET',
            cache: 'no-store',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!response.ok) {
            throw new Error(`No se pudo obtener la clave VAPID (HTTP ${response.status})`);
        }

        const result = await response.json();
        if (!result || typeof result.publicKey !== 'string' || !result.publicKey.trim()) {
            throw new Error('El servidor no devolvió una clave VAPID válida');
        }

        this.vapidPublicKey = result.publicKey.trim();
        return this.vapidPublicKey;
    }

    async syncStatus() {
        if (!this.registration) return;

        try {
            const permission = Notification.permission;
            console.log('🔔 Estado de permisos de notificación:', permission);

            if (permission === 'granted') {
                await this.subscribe();
                this.hideBanner();
            } else if (permission === 'default') {
                const dismissedTime = localStorage.getItem('push-banner-dismissed');
                const now = Date.now();
                if (!dismissedTime || (now - parseInt(dismissedTime, 10)) > (12 * 60 * 60 * 1000)) {
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
        if (!this.isSupported || (window.Notification && Notification.permission !== 'default')) {
            this.hideBanner();
            return;
        }
        const banner = document.getElementById('rey-push-banner');
        if (banner) {
            banner.style.display = 'block';
        }
    }

    hideBanner() {
        const banner = document.getElementById('rey-push-banner');
        if (banner) {
            banner.style.display = 'none';
        }
    }

    async requestPermission() {
        if (!this.isSupported) {
            alert('Tu navegador no admite notificaciones push nativas.');
            return false;
        }

        try {
            console.log('🔔 Solicitando permiso al usuario...');
            let permission = Notification.permission;

            if (permission !== 'granted') {
                permission = await Notification.requestPermission();
            }
            console.log('Respuesta de permiso:', permission);

            if (permission === 'granted') {
                this.hideBanner();
                const success = await this.subscribe();
                if (success) {
                    this.showToast('¡Notificaciones activadas! Te avisaremos al teléfono cuando empiece una partida en vivo.', 'success');
                } else {
                    this.showToast('Permiso concedido, pero ocurrió un problema al registrar el teléfono.', 'warning');
                }
                return success;
            } else if (permission === 'denied') {
                this.hideBanner();
                this.showToast('Las notificaciones para este sitio están bloqueadas en tu navegador. Toca el icono de controles/ajustes junto a reybingo.com en la barra superior -> Permisos -> Notificaciones y selecciona "Permitir".', 'warning');
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
            try {
                this.registration = await navigator.serviceWorker.ready;
            } catch (e) {
                console.warn('Service Worker aún no listo:', e);
                return false;
            }
        }

        try {
            if (!this.vapidPublicKey) {
                await this.loadVapidPublicKey();
            }

            console.log('🔄 Creando suscripción Push con clave VAPID...');
            const convertedKey = this.urlBase64ToUint8Array(this.vapidPublicKey);

            let subscription = await this.registration.pushManager.getSubscription();

            // Una suscripción creada con otra clave pública ya no puede recibir envíos del servidor.
            const existingKey = subscription && subscription.options && subscription.options.applicationServerKey;
            if (subscription && existingKey && !this.areKeysEqual(existingKey, convertedKey)) {
                await subscription.unsubscribe();
                subscription = null;
            }

            if (!subscription) {
                subscription = await this.registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: convertedKey
                });
            }

            console.log('✅ Suscripción WebPush lista:', subscription);
            return await this.saveSubscriptionToServer(subscription);
        } catch (error) {
            console.error('❌ Error suscribiendo a WebPush:', error);
            return false;
        }
    }

    formatSubscriptionData(subscription) {
        if (!subscription) return null;

        let p256dh = '';
        let auth = '';
        const json = subscription.toJSON ? subscription.toJSON() : {};

        if (json.keys) {
            p256dh = json.keys.p256dh || '';
            auth = json.keys.auth || '';
        }

        try {
            if (subscription.getKey && (!p256dh || !auth)) {
                const rawP256dh = subscription.getKey('p256dh');
                const rawAuth = subscription.getKey('auth');

                if (!p256dh && rawP256dh) {
                    p256dh = this.arrayBufferToBase64Url(rawP256dh);
                }
                if (!auth && rawAuth) {
                    auth = this.arrayBufferToBase64Url(rawAuth);
                }
            }
        } catch (err) {
            console.warn('Error leyendo getKey(), usando toJSON():', err);
        }

        return {
            endpoint: subscription.endpoint,
            keys: {
                p256dh: p256dh,
                auth: auth
            }
        };
    }

    async saveSubscriptionToServer(subscription) {
        try {
            const endpoint = this.buildUrl('notifications/subscribe');

            const payloadData = this.formatSubscriptionData(subscription);
            if (!payloadData || !payloadData.keys.p256dh || !payloadData.keys.auth) {
                console.error('❌ No se pudieron extraer las claves criptográficas de la suscripción');
                return false;
            }

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify(payloadData)
            });

            if (!response.ok) {
                console.error(`❌ El servidor rechazó la suscripción push (HTTP ${response.status})`);
                return false;
            }

            const result = await response.json();
            console.log('📡 Respuesta del servidor al guardar suscripción:', result);
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

    areKeysEqual(firstKey, secondKey) {
        const first = new Uint8Array(firstKey);
        const second = new Uint8Array(secondKey);

        if (first.length !== second.length) return false;
        return first.every((value, index) => value === second[index]);
    }

    arrayBufferToBase64Url(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        bytes.forEach(byte => { binary += String.fromCharCode(byte); });
        return btoa(binary)
            .replace(/\+/g, '-')
            .replace(/\//g, '_')
            .replace(/=+$/, '');
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
            z-index: 999999;
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
        }, 6000);
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
