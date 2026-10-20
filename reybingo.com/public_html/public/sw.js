/**
 * Service Worker de Rey Bingo para notificaciones push
 * Versión: 4.0
 */

const CACHE_VERSION = 'v4';
const CACHE_NAME = `reybingo-cache-${CACHE_VERSION}`;

console.log('🔧 Service Worker cargado - Versión 4.0');

// Instalación
self.addEventListener('install', function(event) {
    console.log('⚙️ Service Worker instalando...');
    self.skipWaiting();
});

// Activación
self.addEventListener('activate', function(event) {
    console.log('🚀 Service Worker activado');
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.filter(cacheName => {
                    return cacheName.startsWith('reybingo-cache-') && cacheName !== CACHE_NAME;
                }).map(cacheName => {
                    return caches.delete(cacheName);
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Evento PUSH para recibir notificaciones en segundo plano
self.addEventListener('push', function(event) {
    console.log('📨 PUSH EVENT RECIBIDO!', event);
    
    let defaultData = {
        title: '🔴 ¡NUEVA PARTIDA EN VIVO!',
        body: '¡Hay una nueva partida en vivo disponible en Rey Bingo! Entra a comprar tus cartones.',
        icon: '/assets/img/logo.png',
        badge: '/assets/img/badge.png',
        tag: 'live-game-' + Date.now(),
        requireInteraction: true,
        vibrate: [300, 100, 300, 100, 300],
        data: {
            url: '/',
            timestamp: Date.now()
        },
        actions: [
            {
                action: 'view',
                title: '🎮 Entrar a Jugar'
            },
            {
                action: 'close',
                title: '❌ Cerrar'
            }
        ]
    };

    if (event.data) {
        try {
            const data = event.data.json();
            console.log('📋 Datos recibidos en push:', data);

            const title = data.title || defaultData.title;
            const body = data.message || data.body || defaultData.body;
            const url = data.url || (data.data && data.data.url) || '/';
            const icon = data.icon || defaultData.icon;
            const badge = data.badge || defaultData.badge;
            const tag = data.tag || defaultData.tag;
            const vibrate = data.vibrate || defaultData.vibrate;

            const options = {
                body: body,
                icon: icon,
                badge: badge,
                tag: tag,
                requireInteraction: true,
                vibrate: vibrate,
                data: {
                    url: url,
                    timestamp: Date.now()
                },
                actions: data.actions || defaultData.actions,
                timestamp: Date.now()
            };

            event.waitUntil(self.registration.showNotification(title, options));
            return;
        } catch (e) {
            console.warn('Push payload no era JSON, probando texto...', e);
            try {
                const text = event.data.text();
                if (text) {
                    defaultData.body = text;
                }
            } catch (err) {
                console.error('Error leyendo texto:', err);
            }
        }
    }

    // Si no vino payload, consultar la API para la partida más reciente
    event.waitUntil(
        fetch('/notifications/getLatestPush')
            .then(res => res.json())
            .then(apiData => {
                const title = apiData.title || defaultData.title;
                const body = apiData.message || apiData.body || defaultData.body;
                const url = apiData.url || '/';

                return self.registration.showNotification(title, {
                    body: body,
                    icon: apiData.icon || defaultData.icon,
                    badge: apiData.badge || defaultData.badge,
                    tag: apiData.tag || defaultData.tag,
                    requireInteraction: true,
                    vibrate: defaultData.vibrate,
                    data: {
                        url: url,
                        timestamp: Date.now()
                    },
                    actions: defaultData.actions
                });
            })
            .catch(() => {
                return self.registration.showNotification(defaultData.title, defaultData);
            })
    );
});

// Manejo de clic en la notificación
self.addEventListener('notificationclick', function(event) {
    console.log('🖱️ Clic en notificación push:', event);
    event.notification.close();

    if (event.action === 'close') {
        return;
    }

    let targetUrl = '/';
    if (event.notification.data && event.notification.data.url) {
        targetUrl = event.notification.data.url;
    }

    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        })
        .then(clientList => {
            const targetUrlObj = new URL(targetUrl, self.location.origin);
            const targetHref = targetUrlObj.href;

            for (const client of clientList) {
                if ('focus' in client) {
                    // Si ya hay ventana abierta, dirigir a la partida y enfocar
                    if ('navigate' in client) {
                        client.navigate(targetHref);
                    }
                    return client.focus();
                }
            }

            // Si no hay ventana abierta, abrir nueva ventana en el navegador
            if (clients.openWindow) {
                return clients.openWindow(targetHref);
            }
        })
        .catch(err => {
            console.error('Error abriendo ventana desde notificación:', err);
        })
    );
});

// Mensajes internos
self.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});