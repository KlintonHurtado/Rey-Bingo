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
                if (this.connectionAttempts < this.maxConnectionAttempts) {
                    var delay = 2000 * this.connectionAttempts;
                    console.log('WS reintentando en ' + delay + 'ms (' + this.connectionAttempts + '/' + this.maxConnectionAttempts + ')...');
                    if (this._reconnectTimer) clearTimeout(this._reconnectTimer);
                    this._reconnectTimer = setTimeout(() => this.reconnect(), delay);
                } else {
                    console.error('WS: maximo de reintentos alcanzado. Usando poll de respaldo.');
                    this._triggerEvent('connection:failed', error);
                }
            });

            this.pusher.connection.bind('disconnected', () => {
                if (this.isConnected) {
                    console.warn('WS: desconectado inesperadamente');
                    this.isConnected = false;
                    this._triggerEvent('connection:failed', { message: 'disconnected' });
                }
            });

            this.pusher.connection.bind('connected', () => {
                console.log('WS: conexion TCP establecida');
            });

            this._setupGameEvents();
            return true;

        } catch (error) {
            console.error('WS: error al inicializar:', error);
            this._triggerEvent('connection:error', error);
            return false;
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
