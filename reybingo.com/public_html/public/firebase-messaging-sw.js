// Firebase Service Worker para notificaciones push

importScripts('https://www.gstatic.com/firebasejs/9.6.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.6.1/firebase-messaging-compat.js');

// Configuración de Firebase
firebase.initializeApp({
  apiKey: "AIzaSyDn20f_8nVHeU_XzGDAbv2OOLfyplFKXIk",
  authDomain: "bingofamily-5a09a.firebaseapp.com",
  projectId: "bingofamily-5a09a",
  storageBucket: "bingofamily-5a09a.firebasestorage.app",
  messagingSenderId: "346461010889",
  appId: "1:346461010889:web:b5a8c5beeb57ec69a18618"
});

// Inicializar Firebase Messaging
const messaging = firebase.messaging();

// Manejar mensajes en segundo plano
messaging.onBackgroundMessage((payload) => {
  console.log('[firebase-messaging-sw.js] Recibido mensaje en segundo plano:', payload);
  
  // Personalizar notificación
  const notificationTitle = payload.notification.title || 'Nueva notificación';
  const notificationOptions = {
    body: payload.notification.body || 'Tienes una nueva notificación',
    icon: '/assets/img/logo.png',
    badge: '/assets/img/badge.png',
    tag: 'bingo-notification',
    data: payload.data || {},
    actions: [
      {
        action: 'view',
        title: 'Ver'
      },
      {
        action: 'close',
        title: 'Cerrar'
      }
    ]
  };

  // Mostrar notificación
  self.registration.showNotification(notificationTitle, notificationOptions);
});

// Manejar clic en notificación
self.addEventListener('notificationclick', (event) => {
  console.log('[firebase-messaging-sw.js] Notificación clickeada:', event);
  
  event.notification.close();
  
  // Acción al hacer clic
  if (event.action === 'view' || !event.action) {
    const urlToOpen = event.notification.data.url || '/';
    
    event.waitUntil(
      clients.matchAll({type: 'window'}).then((windowClients) => {
        // Verificar si ya hay una ventana abierta
        for (let i = 0; i < windowClients.length; i++) {
          const client = windowClients[i];
          if (client.url.indexOf(urlToOpen) >= 0 && 'focus' in client) {
            return client.focus();
          }
        }
        
        // Si no hay ventana abierta, abrir una nueva
        if (clients.openWindow) {
          return clients.openWindow(urlToOpen);
        }
      })
    );
  }
});
