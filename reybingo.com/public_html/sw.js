/**
 * Service Worker de Rey Bingo para notificaciones push.
 */

const CACHE_VERSION = 'v8';
const CACHE_NAME = `reybingo-cache-${CACHE_VERSION}`;
const DEFAULT_NOTIFICATION = {
    title: 'Nueva notificación',
    body: 'Tienes una nueva notificación de Rey Bingo',
    icon: `${self.location.origin}/assets/img/logo.png`,
    badge: `${self.location.origin}/assets/img/logo.png`,
    requireInteraction: true,
    vibrate: [300, 100, 300]
};

self.addEventListener('install', event => {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(cacheNames => Promise.all(
                cacheNames
                    .filter(cacheName => cacheName.startsWith('reybingo-cache-') && cacheName !== CACHE_NAME)
                    .map(cacheName => caches.delete(cacheName))
            ))
            .then(() => self.clients.claim())
    );
});

function asObject(value) {
    return value && typeof value === 'object' && !Array.isArray(value) ? value : {};
}

function isIOSClient() {
    return /iPad|iPhone|iPod/.test(navigator.userAgent || '') ||
           (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
}

function normalizeNotification(payload) {
    const source = asObject(payload);
    const notification = asObject(source.notification);
    const data = asObject(source.data);
    const nestedData = asObject(data.data);
    const url = notification.click_action
        || notification.clickAction
        || source.click_action
        || source.url
        || data.url
        || nestedData.url
        || (source.fcmOptions && source.fcmOptions.link)
        || '/';

    const title = notification.title || source.title || data.title || DEFAULT_NOTIFICATION.title;
    const body = notification.body || source.body || source.message || data.body || data.message || DEFAULT_NOTIFICATION.body;
    const tag = notification.tag || source.tag || data.tag || `rey-bingo-${Date.now()}`;
    const clickData = Object.assign({}, data, nestedData, { url, timestamp: Date.now() });

    // Apple iOS WebKit (Safari PWA) no soporta 'actions', 'requireInteraction', 'vibrate', 'badge', 'icon'
    // en showNotification y arroja un TypeError si se le envían, bloqueando la notificación en el iPhone.
    if (isIOSClient()) {
        return {
            title: title,
            options: {
                body: body,
                tag: tag,
                data: clickData
            }
        };
    }

    // Android / Windows / Desktop: Opciones completas
    return {
        title: title,
        options: {
            body: body,
            icon: notification.icon || source.icon || data.icon || DEFAULT_NOTIFICATION.icon,
            badge: notification.badge || source.badge || data.badge || DEFAULT_NOTIFICATION.badge,
            tag: tag,
            requireInteraction: typeof notification.requireInteraction === 'boolean'
                ? notification.requireInteraction
                : (typeof source.requireInteraction === 'boolean'
                    ? source.requireInteraction
                    : DEFAULT_NOTIFICATION.requireInteraction),
            vibrate: source.vibrate || data.vibrate || DEFAULT_NOTIFICATION.vibrate,
            actions: notification.actions || source.actions || data.actions,
            timestamp: Date.now(),
            data: clickData
        }
    };
}

async function getLatestNotification() {
    const response = await fetch(new URL('/notifications/getLatestPush', self.location.origin), {
        cache: 'no-store',
        credentials: 'same-origin'
    });

    if (!response.ok) {
        throw new Error(`No se pudo recuperar la notificación (HTTP ${response.status})`);
    }

    return response.json();
}

self.addEventListener('push', event => {
    event.waitUntil((async () => {
        let payload = null;

        if (event.data) {
            try {
                payload = event.data.json();
            } catch (error) {
                const body = event.data.text();
                payload = body ? { body } : null;
            }
        } else {
            try {
                payload = await getLatestNotification();
            } catch (error) {
                console.error('[sw.js] No se pudo recuperar el contenido del push:', error);
            }
        }

        const normalized = normalizeNotification(payload);
        try {
            await self.registration.showNotification(normalized.title, normalized.options);
        } catch (showError) {
            console.warn('[sw.js] Falló showNotification completo, ejecutando fallback básico para móvil/iOS:', showError);
            try {
                // Fallback seguro compatible con todas las versiones de iOS y Android
                const fallbackOpts = {
                    body: (normalized.options && normalized.options.body) || '¡Partida en vivo disponible!',
                    tag: (normalized.options && normalized.options.tag) || 'rey-bingo-push',
                    data: (normalized.options && normalized.options.data) || { url: '/' }
                };
                if (!isIOSClient()) {
                    fallbackOpts.icon = `${self.location.origin}/assets/img/logo.png`;
                }
                await self.registration.showNotification(normalized.title || 'Rey Bingo', fallbackOpts);
            } catch (fallbackError) {
                console.error('[sw.js] Error crítico en showNotification:', fallbackError);
            }
        }
    })());
});

self.addEventListener('notificationclick', event => {
    event.notification.close();

    if (event.action === 'close') {
        return;
    }

    event.waitUntil((async () => {
        let targetUrl = '/';
        try {
            const requestedUrl = event.notification.data && event.notification.data.url;
            const parsedUrl = new URL(requestedUrl || '/', self.location.origin);
            targetUrl = parsedUrl.origin === self.location.origin ? parsedUrl.href : `${self.location.origin}/`;
        } catch (error) {
            targetUrl = `${self.location.origin}/`;
        }

        const windowClients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        const sameOriginClient = windowClients.find(client => {
            try {
                return new URL(client.url).origin === self.location.origin;
            } catch (error) {
                return false;
            }
        });

        if (sameOriginClient) {
            if ('navigate' in sameOriginClient) {
                await sameOriginClient.navigate(targetUrl);
            }
            return sameOriginClient.focus();
        }

        if (self.clients.openWindow) {
            return self.clients.openWindow(targetUrl);
        }
    })());
});

self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        event.waitUntil(self.skipWaiting());
    }
});
