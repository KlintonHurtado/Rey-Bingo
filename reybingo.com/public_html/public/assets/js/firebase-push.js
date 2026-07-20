// firebase-push.js
// Configuración de Firebase para el Frontend

// Asegúrate de que las librerías de Firebase hayan sido cargadas antes de este script
if (typeof firebase !== 'undefined') {
    const firebaseConfig = {
        apiKey: "AIzaSyDn20f_8nVHeU_XzGDAbv2OOLfyplFKXIk",
        authDomain: "bingofamily-5a09a.firebaseapp.com",
        projectId: "bingofamily-5a09a",
        storageBucket: "bingofamily-5a09a.firebasestorage.app",
        messagingSenderId: "346461010889",
        appId: "1:346461010889:web:b5a8c5beeb57ec69a18618"
    };

    // Inicializar Firebase
    if (!firebase.apps.length) {
        firebase.initializeApp(firebaseConfig);
    }

    const messaging = firebase.messaging();

    // Solicitar permiso al usuario para enviar notificaciones
    function requestPushPermission() {
        Notification.requestPermission().then((permission) => {
            if (permission === 'granted') {
                console.log('Permiso de notificación concedido.');
                
                // Obtener el token FCM (requiere tener el Service Worker registrado)
                messaging.getToken({ 
                    vapidKey: 'BIZ7bLgH58eH5bT5Z-z-H2sR-qT3zGz6bQ-z_M7-h0h_V9D0JzW9W3N6Q9V2H3_L' // Este VAPID Key usualmente se requiere, pero podemos intentar sin él si es legacy, aunque Web Push siempre lo pide. Si falla, el usuario debe generar uno en la consola.
                }).then((currentToken) => {
                    if (currentToken) {
                        console.log('Token FCM obtenido:', currentToken);
                        saveTokenToServer(currentToken);
                    } else {
                        console.log('No se obtuvo token de registro. Se necesita permisos.');
                    }
                }).catch((err) => {
                    console.log('Ocurrió un error obteniendo el token. ', err);
                });
            } else {
                console.log('Permiso denegado para notificaciones.');
            }
        });
    }

    // Enviar el token al servidor PHP
    function saveTokenToServer(token) {
        // Enviar por AJAX usando Fetch
        fetch(site_url + 'notifications/registerToken', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                token: token,
                device: navigator.userAgent
            })
        }).then(response => response.json())
          .then(data => console.log('Token guardado en servidor:', data))
          .catch(error => console.error('Error guardando token:', error));
    }

    // Manejar mensajes cuando la aplicación está en primer plano
    messaging.onMessage((payload) => {
        console.log('Mensaje recibido (App abierta): ', payload);
        // Opcional: mostrar una alerta o toast con la notificación
        if(typeof toastr !== 'undefined') {
            toastr.info(payload.notification.body, payload.notification.title);
        } else {
            alert(payload.notification.title + "\n" + payload.notification.body);
        }
    });

    // Solicitar permisos al cargar la página (o puedes asociarlo a un botón)
    document.addEventListener("DOMContentLoaded", function() {
        // Solo solicitar si no lo ha bloqueado/concedido antes, o forzar para pruebas
        if (Notification.permission === 'default') {
            // Un pequeño delay para no asustar apenas carga
            setTimeout(requestPushPermission, 3000);
        } else if (Notification.permission === 'granted') {
            requestPushPermission(); // Para refrescar y guardar el token si ya lo permitió
        }
    });
} else {
    console.error("Firebase no está cargado.");
}
