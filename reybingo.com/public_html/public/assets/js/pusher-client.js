/**
 * SoketiClient / PusherClient
 *
 * Cliente universal de WebSocket para el juego de Bingo.
 * Funciona con Soketi self-hosted (VPS) y con Pusher Cloud.
 * La librería pusher-js es 100% compatible con ambos servicios.
 *
 * Config que recibe desde la vista PHP:
 *   GAME_ID         - ID del juego
 *   USER_ID         - ID del usuario
 *   PUSHER_KEY      - App key (SOKETI_KEY o PUSHER_KEY)
 *   PUSHER_CLUSTER  - Cluster de Pusher Cloud (vacío si usas Soketi)
 *   SOKETI_HOST     - Host de Soketi (ej: "bingo.reybingo.com" o "127.0.0.1")
 *   SOKETI_PORT     - Puerto de Soketi (ej: 443 cuando va por Nginx)
 *   AUTH_URL        - Endpoint PHP que firma la auth del canal privado
 */
class PusherClient {
    constructor(gameId, userId) {
        this.gameId              = gameId;
        this.userId              = userId;
        this.channel             = null;
        this.pusher              = null;
        this.eventHandlers       = {};
        this.isConnected         = false;
        this.connectionAttempts  = 0;
        this.maxConnectionAttempts = 5;
        this.key                 = null;
        this.cluster             = null;
        this.wsHost              = null;
        this.wsPort              = null;
        this.authEndpoint        = null;
        this._reconnectTimer     = null;
    }

    /**
     * Inicializa la conexión WebSocket.
     * Si se pasa wsHost/wsPort (Soketi), se conecta al servidor propio.
     * Si no, usa Pusher Cloud (cluster).
     */
    init(key, cluster, authEndpoint, wsHost, wsPort) {
        try {
            this.key          = key          || this.key;
            this.cluster      = cluster      || this.cluster;
            this.authEndpoint = authEndpoint || this.authEndpoint;
            this.wsHost       = wsHost       || this.wsHost;
            this.wsPort       = wsPort       || this.wsPort;

            if (!this.key || !this.authEndpoint) {
                console.warn('WS: faltan key/authEndpoint. Tiempo real desactivado.');
                this._triggerEvent('connection:failed', { message: 'missing_config' });
                return false;
            }

            // Desconectar instancia previa si existe
            if (this.pusher) {
                try { this.pusher.disconnect(); } catch (e) { /* ignore */ }
                this.pusher  = null;
                this.channel = null;
            }

            // Configuración del cliente Pusher.js
            // pusher-js soporta wsHost/wsPort para conectarse a Soketi self-hosted
            var pusherConfig = {
                channelAuthorization: {
                    endpoint  : this.authEndpoint,
                    transport : 'ajax'
                }
            };

            if (this.wsHost) {
                // Modo Soketi self-hosted: conectar al servidor propio
                pusherConfig.wsHost            = this.wsHost;
                pusherConfig.wsPort            = parseInt(this.wsPort || 443, 10);
                pusherConfig.wssPort           = parseInt(this.wsPort || 443, 10);
                pusherConfig.forceTLS          = true;
                pusherConfig.enabledTransports = ['ws', 'wss'];
                pusherConfig.disableStats      = true;
                // Cluster vacío para Soketi
                pusherConfig.cluster           = '';
                console.log('WS: conectando a Soketi en ' + this.wsHost + ':' + pusherConfig.wsPort);
            } else {
                // Modo Pusher Cloud
                pusherConfig.cluster = this.cluster || 'us2';
                console.log('WS: conectando a Pusher Cloud (cluster: ' + pusherConfig.cluster + ')');
            }

            this.pusher = new Pusher(this.key, pusherConfig);

            var channelName = 'private-game-' + this.gameId;
            this.channel    = this.pusher.subscribe(channelName);

            // Suscribirse también al canal privado del usuario para notificaciones personales en tiempo real
            if (this.userId && parseInt(this.userId, 10) > 0) {
                var userChannelName = 'private-user-' + this.userId;
                this.userChannel = this.pusher.subscribe(userChannelName);
                this.userChannel.bind('notification:new', (data) => {
                    console.log('WS Notificación personal recibida:', data);
                    if (typeof window.showNotification === 'function') {
                        window.showNotification(data);
                    }
                });
            }

            // Escuchar notificaciones de la partida
            this.channel.bind('game:notification', (data) => {
                if (typeof window.showNotification === 'function') {
                    window.showNotification(data);
                }
            });
            this.channel.bind('notification:new', (data) => {
                if (typeof window.showNotification === 'function') {
                    window.showNotification(data);
                }
            });

            this.channel.bind('pusher:subscription_succeeded', () => {
                console.log('WS suscripcion exitosa al canal: ' + channelName);
                this.isConnected       = true;
                this.connectionAttempts = 0;
                this._triggerEvent('connection:success');
            });

            this.channel.bind('pusher:subscription_error', (error) => {
                console.error('WS error de suscripcion:', error);
                this.isConnected = false;
                this.connectionAttempts++;
                this._scheduleReconnect();
                this._triggerEvent('connection:failed', error);
            });

            // Monitoreo completo del ciclo de conexión Pusher/Soketi para móviles y redes inestables
            this.pusher.connection.bind('state_change', (states) => {
                console.log('WS estado:', states.previous, '->', states.current);
                if (states.current === 'connected') {
                    this.isConnected = true;
                    this.connectionAttempts = 0;
                    if (this._reconnectTimer) {
                        clearTimeout(this._reconnectTimer);
                        this._reconnectTimer = null;
                    }
                    this._triggerEvent('connection:success');
                } else if (states.current === 'disconnected' || states.current === 'unavailable' || states.current === 'failed') {
                    this.isConnected = false;
                    this._triggerEvent('connection:failed', { state: states.current });
                    this._scheduleReconnect();
                }
            });

            this._setupGameEvents();
            this._bindMobileLifecycle();
            return true;

        } catch (error) {
            console.error('WS: error al inicializar:', error);
            this._triggerEvent('connection:error', error);
            return false;
        }
    }

    _scheduleReconnect() {
        if (this._reconnectTimer) return;
        this.connectionAttempts++;
        // Backoff exponencial suave: 1.5s, 3s, 5s, 8s, luego cada 10s continuo (sin límite artificial)
        const delay = Math.min(10000, Math.max(1500, this.connectionAttempts * 1500));
        console.log('WS móvil: programando reconexión en ' + delay + 'ms (intento #' + this.connectionAttempts + ')');
        this._reconnectTimer = setTimeout(() => {
            this._reconnectTimer = null;
            this.reconnect();
        }, delay);
    }

    _bindMobileLifecycle() {
        if (this._lifecycleBound) return;
        this._lifecycleBound = true;

        const handleResume = () => {
            if (document.hidden) return;
            console.log('WS móvil: reanudación de primer plano detectada');
            this.ensureConnected();
            this._triggerEvent('lifecycle:resume');
        };

        document.addEventListener('visibilitychange', handleResume);
        window.addEventListener('pageshow', handleResume);
        window.addEventListener('focus', handleResume);
        window.addEventListener('online', () => {
            console.log('WS móvil: red recuperada (online)');
            this.ensureConnected();
            this._triggerEvent('lifecycle:online');
        });
    }

    ensureConnected() {
        if (!this.pusher) {
            this.reconnect();
            return;
        }
        const state = (this.pusher.connection && this.pusher.connection.state) ? this.pusher.connection.state : 'disconnected';
        if (state !== 'connected' && state !== 'connecting') {
            console.log('WS móvil: forzando reconexión tras suspensión (estado: ' + state + ')');
            try {
                this.pusher.connect();
            } catch (e) {
                this.reconnect();
            }
        }
    }

    reconnect() {
        if (!this.key || !this.authEndpoint) {
            this._triggerEvent('connection:failed', { message: 'missing_config' });
            return;
        }
        if (this.pusher) {
            try { this.pusher.disconnect(); } catch (e) { /* ignore */ }
            this.pusher  = null;
            this.channel = null;
        }
        this.init(this.key, this.cluster, this.authEndpoint, this.wsHost, this.wsPort);
    }

    _setupGameEvents() {
        if (!this.channel) return;

        var gameEvents = [
            'game:number_drawn',
            'game:bingo_claimed',
            'game:bingo_accepted',
            'game:game_reset',
            'game:completed',
            'game:game_finished',
            'game:player_joined',
            'player:number_marked',
            'game:message',
            'game:chat_message',
            'game:postponed',
            'game:started'
        ];

        gameEvents.forEach(eventName => {
            this.channel.bind(eventName, (data) => {
                this._triggerEvent(eventName, data);
            });
        });
    }

    on(eventName, callback) {
        if (!this.eventHandlers[eventName]) {
            this.eventHandlers[eventName] = [];
        }
        this.eventHandlers[eventName].push(callback);
    }

    off(eventName, callback) {
        if (this.eventHandlers[eventName]) {
            if (callback) {
                this.eventHandlers[eventName] = this.eventHandlers[eventName].filter(
                    h => h !== callback
                );
            } else {
                delete this.eventHandlers[eventName];
            }
        }
    }

    _triggerEvent(eventName, data) {
        if (this.eventHandlers[eventName]) {
            this.eventHandlers[eventName].forEach(cb => {
                try { cb(data); }
                catch (e) { console.error('Error en handler de ' + eventName + ':', e); }
            });
        }
    }

    disconnect() {
        if (this._reconnectTimer) {
            clearTimeout(this._reconnectTimer);
            this._reconnectTimer = null;
        }
        if (this.pusher) {
            try { this.pusher.disconnect(); } catch (e) { /* ignore */ }
            this.isConnected = false;
            this.pusher      = null;
            this.channel     = null;
            this.userChannel = null;
        }
    }
}

window.PusherClient = PusherClient;
