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

            // Forzar actualización inmediata en móviles si hay nueva versión de sw.js
            if (this.registration && typeof this.registration.update === 'function') {
                this.registration.update().catch(() => {});
            }

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
            // 1. Si el navegador ya tiene una suscripción Push activa, ocultar banner de inmediato
            let existingSub = null;
            try {
                existingSub = await this.registration.pushManager.getSubscription();
            } catch (err) {
                console.warn('No se pudo leer suscripción existente:', err);
            }

            const permission = (window.Notification && Notification.permission) || 'default';
            console.log('🔔 Estado de permisos de notificación:', permission, '| Suscripción activa:', !!existingSub);

            if (permission === 'granted') {
                this.hideBanner();
                this.hideFloatingBell();
                await this.subscribe();
                return;
            }

            if (existingSub) {
                this.hideBanner();
                this.hideFloatingBell();
                await this.saveSubscriptionToServer(existingSub);
                return;
            }

            if (permission === 'denied') {
                this.hideBanner();
                this.hideFloatingBell();
                return;
            }

            // permission === 'default'
            const dismissed = sessionStorage.getItem('push-banner-dismissed');
            if (dismissed) {
                this.hideBanner();
                this.showFloatingBell();
            } else {
                this.showBanner();
                this.hideFloatingBell();
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
        this.hideFloatingBell();
    }

    hideBanner() {
        const banner = document.getElementById('rey-push-banner');
        if (banner) {
            banner.style.display = 'none';
        }
    }

    showFloatingBell() {
        if (!this.isSupported || (window.Notification && Notification.permission !== 'default')) {
            this.hideFloatingBell();
            return;
        }
        const bell = document.getElementById('rey-push-floating-bell');
        if (bell) {
            bell.style.display = 'block';
        }
    }

    hideFloatingBell() {
        const bell = document.getElementById('rey-push-floating-bell');
        if (bell) {
            bell.style.display = 'none';
        }
    }

    async requestPermission() {
        if (!this.isSupported) {
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            const isStandalone = window.navigator.standalone || (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches);
            if (isIOS && !isStandalone) {
                alert('Para activar notificaciones en iPhone:\n\n1. Toca el botón Compartir (icono de cuadrado con flecha hacia arriba) en Safari.\n2. Elige "Agregar a pantalla de inicio".\n3. Abre Rey Bingo desde tu pantalla de inicio y pulsa "Activar Notificaciones".');
            } else {
                alert('Tu navegador no admite notificaciones push nativas.');
            }
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
                this.hideFloatingBell();
                sessionStorage.setItem('push-banner-dismissed', '1');
                const subOk = await this.subscribe(true);
                if (subOk) {
                    this.showToast('¡Notificaciones activadas! Te avisaremos al teléfono cuando empiece una partida en vivo.', 'success');
                } else {
                    this.showToast('Permiso concedido, pero hubo un problema registrando el dispositivo. Por favor recarga la página.', 'warning');
                }
                return true;
            } else if (permission === 'denied') {
                this.hideBanner();
                this.hideFloatingBell();
                this.showBlockedGuideModal();
                return false;
            } else {
                return false;
            }
        } catch (error) {
            console.error('Error solicitando permisos:', error);
            return false;
        }
    }

    async subscribe(forceRenew = false) {
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

            console.log('🔄 Sincronizando suscripción Push con clave VAPID...');
            const convertedKey = this.urlBase64ToUint8Array(this.vapidPublicKey);

            let subscription = await this.registration.pushManager.getSubscription();

            // En dispositivos móviles (Android / iOS), subscription.options.applicationServerKey
            // puede ser null o no coincidir con el getter por especificaciones anteriores del navegador.
            // Para garantizar que el teléfono quede enlazado a la clave VAPID activa del servidor,
            // validamos mediante almacenamiento local persistente y opciones de clave.
            const STORAGE_VAPID_KEY = 'rey_push_active_vapid_v3';
            const savedVapid = localStorage.getItem(STORAGE_VAPID_KEY);
            const existingKey = subscription && subscription.options && subscription.options.applicationServerKey;

            let needsRenewal = forceRenew;
            if (subscription) {
                if (savedVapid !== this.vapidPublicKey) {
                    needsRenewal = true;
                } else if (existingKey && !this.areKeysEqual(existingKey, convertedKey)) {
                    needsRenewal = true;
                }
            }

            if (subscription && needsRenewal) {
                console.log('🔄 Renovando suscripción Push obsoleta en este dispositivo móvil...');
                try {
                    await subscription.unsubscribe();
                } catch (unsubErr) {
                    console.warn('Aviso al desuscribir token previo:', unsubErr);
                }
                subscription = null;
            }

            if (!subscription) {
                console.log('📱 Creando nueva suscripción WebPush nativa en el dispositivo...');
                subscription = await this.registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: convertedKey
                });
            }

            localStorage.setItem(STORAGE_VAPID_KEY, this.vapidPublicKey);
            console.log('✅ Suscripción WebPush lista en dispositivo:', subscription);
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
            },
            contentEncoding: 'aes128gcm'
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
                console.warn(`El servidor devolvió HTTP ${response.status} al guardar suscripción push`);
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
            right: 20px;
            left: 20px;
            max-width: 420px;
            margin: 0 auto;
            background: ${type === 'success' ? '#10b981' : type === 'warning' ? '#f59e0b' : '#3b82f6'};
            color: #ffffff;
            padding: 14px 42px 14px 18px;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.35);
            z-index: 999999;
            font-size: 13.5px;
            font-family: inherit;
            font-weight: 600;
            line-height: 1.4;
            transition: opacity 0.3s ease;
        `;
        toast.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                <span>${message}</span>
                <button type="button" style="background: none; border: none; color: #fff; font-size: 22px; cursor: pointer; line-height: 1; padding: 0 4px; opacity: 0.85;">&times;</button>
            </div>
        `;
        const closeBtn = toast.querySelector('button');
        if (closeBtn) {
            closeBtn.onclick = () => toast.remove();
        }
        document.body.appendChild(toast);

        setTimeout(() => {
            if (toast.parentNode) {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }
        }, 6000);
    }

    showBlockedGuideModal() {
        const existing = document.getElementById('rey-push-blocked-modal');
        if (existing) {
            existing.remove();
        }

        const modal = document.createElement('div');
        modal.id = 'rey-push-blocked-modal';
        modal.style.cssText = 'position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 999999; display: flex; align-items: center; justify-content: center; padding: 20px;';
        modal.innerHTML = `
            <div style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); border: 1px solid rgba(255,255,255,0.2); border-radius: 20px; max-width: 420px; width: 100%; color: #fff; padding: 24px; box-shadow: 0 25px 50px rgba(0,0,0,0.5); font-family: inherit; position: relative;">
                <button type="button" id="btn-close-blocked-modal-x" style="position: absolute; top: 14px; right: 16px; background: none; border: none; color: #94a3b8; font-size: 26px; cursor: pointer; line-height: 1;">&times;</button>
                <div style="text-align: center; margin-bottom: 16px;">
                    <div style="font-size: 38px; margin-bottom: 6px;">🔔⚠️</div>
                    <h4 style="margin: 0; font-size: 18px; font-weight: 700; color: #fbbf24;">Permiso bloqueado en este sitio</h4>
                </div>
                <p style="font-size: 13.5px; color: #cbd5e1; line-height: 1.5; margin-bottom: 14px;">
                    Las notificaciones generales de Chrome en tu celular están activadas, pero <strong>dentro del navegador el sitio reybingo.com quedó bloqueado</strong>.
                </p>
                <div style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); border-radius: 14px; padding: 14px; margin-bottom: 18px; font-size: 13px; color: #e2e8f0; line-height: 1.6;">
                    <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                        <span style="background: #3b82f6; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; flex-shrink: 0; font-weight: bold;">1</span>
                        <span>En la barra de direcciones superior, toca el <strong>icono de ajustes/candado</strong> junto a <code>reybingo.com</code>.</span>
                    </div>
                    <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                        <span style="background: #3b82f6; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; flex-shrink: 0; font-weight: bold;">2</span>
                        <span>Toca en <strong>Permisos</strong>.</span>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <span style="background: #3b82f6; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; flex-shrink: 0; font-weight: bold;">3</span>
                        <span>Cambia <strong>Notificaciones</strong> a <span style="color: #34d399; font-weight: bold;">Permitir</span>.</span>
                    </div>
                </div>
                <button type="button" id="btn-close-blocked-modal" style="width: 100%; background: #3b82f6; color: white; border: none; border-radius: 10px; padding: 11px; font-weight: 600; font-size: 14px; cursor: pointer; box-shadow: 0 4px 12px rgba(59,130,246,0.4);">
                    Entendido, voy a cambiarlo
                </button>
            </div>
        `;

        const closeModal = () => modal.remove();
        modal.querySelector('#btn-close-blocked-modal-x').onclick = closeModal;
        modal.querySelector('#btn-close-blocked-modal').onclick = closeModal;
        modal.onclick = (e) => {
            if (e.target === modal) closeModal();
        };

        document.body.appendChild(modal);
    }
}

// Inicialización global segura (soporta carga diferida o DOM ya listo)
function initReyBingoPush() {
    if (!window.notificationManager) {
        window.notificationManager = new NotificationManager();
    }

    const activateBtn = document.getElementById('btn-activate-push');
    if (activateBtn && !activateBtn._pushBound) {
        activateBtn._pushBound = true;
        activateBtn.addEventListener('click', async function() {
            const originalHtml = activateBtn.innerHTML;
            activateBtn.disabled = true;
            activateBtn.innerHTML = '⌛ Activando...';
            try {
                await window.notificationManager.requestPermission();
            } finally {
                activateBtn.disabled = false;
                activateBtn.innerHTML = originalHtml;
            }
        });
    }

    const dismissBtn = document.getElementById('btn-dismiss-push');
    if (dismissBtn && !dismissBtn._pushBound) {
        dismissBtn._pushBound = true;
        dismissBtn.addEventListener('click', function() {
            window.notificationManager.hideBanner();
            sessionStorage.setItem('push-banner-dismissed', '1');
            window.notificationManager.showFloatingBell();
        });
    }

    const floatingBtn = document.getElementById('btn-floating-activate-push');
    if (floatingBtn && !floatingBtn._pushBound) {
        floatingBtn._pushBound = true;
        floatingBtn.addEventListener('click', async function() {
            await window.notificationManager.requestPermission();
        });
    }

    // Escuchar cambios de permiso automáticos en el navegador
    if (navigator.permissions && navigator.permissions.query) {
        try {
            navigator.permissions.query({ name: 'notifications' }).then(function(permissionStatus) {
                permissionStatus.onchange = function() {
                    console.log('🔔 Cambio en permiso de notificaciones detectado:', permissionStatus.state);
                    if (window.notificationManager) {
                        window.notificationManager.syncStatus();
                    }
                };
            }).catch(function() {});
        } catch (e) {}
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initReyBingoPush);
} else {
    initReyBingoPush();
}

window.requestPushNotifications = function() {
    if (window.notificationManager) {
        return window.notificationManager.requestPermission();
    }
};
