/**
 * PusherClient - Cliente para la integración con Pusher
 *
 * Maneja la conexión con Pusher y los eventos del juego de Bingo.
 */
class PusherClient {
    constructor(gameId, userId) {
        this.gameId = gameId;
        this.userId = userId;
        this.channel = null;
        this.pusher = null;
        this.eventHandlers = {};
        this.isConnected = false;
        this.connectionAttempts = 0;
        this.maxConnectionAttempts = 5;
        this.key = null;
        this.cluster = null;
        this.authEndpoint = null;
        this._reconnectTimer = null;
    }

    init(key, cluster, authEndpoint) {
        try {
            this.key = key || this.key;
            this.cluster = cluster || this.cluster;
            this.authEndpoint = authEndpoint || this.authEndpoint;

            if (!this.key || !this.cluster || !this.authEndpoint) {
                console.warn('Pusher: faltan key/cluster/authEndpoint. Tiempo real desactivado.');
                this._triggerEvent('connection:failed', { message: 'missing_config' });
                return false;
            }

            console.log('Inicializando Pusher con:', {
                key: this.key,
                cluster: this.cluster,
                authEndpoint: this.authEndpoint
            });

            if (this.pusher) {
                try {
                    this.pusher.disconnect();
                } catch (e) {
                    // ignore
                }
                this.pusher = null;
                this.channel = null;
            }

            this.pusher = new Pusher(this.key, {
                cluster: this.cluster,
                channelAuthorization: {
                    endpoint: this.authEndpoint,
                    transport: 'ajax'
                }
            });

            const channelName = 'private-game-' + this.gameId;
            this.channel = this.pusher.subscribe(channelName);

            this.channel.bind('pusher:subscription_succeeded', () => {
                console.log('✅ Suscripción exitosa al canal:', channelName);
                this.isConnected = true;
                this.connectionAttempts = 0;
                this._triggerEvent('connection:success');
            });

            this.channel.bind('pusher:subscription_error', (error) => {
                console.error('❌ Error de suscripción:', error);
                this.isConnected = false;
                this.connectionAttempts++;

                if (this.connectionAttempts < this.maxConnectionAttempts) {
                    console.log(`Reintentando conexión (${this.connectionAttempts}/${this.maxConnectionAttempts})...`);
                    if (this._reconnectTimer) {
                        clearTimeout(this._reconnectTimer);
                    }
                    this._reconnectTimer = setTimeout(() => this.reconnect(), 2000 * this.connectionAttempts);
                } else {
                    console.error('Número máximo de intentos alcanzado. El juego sigue con polling.');
                    this._triggerEvent('connection:failed', error);
                }
            });

            this._setupGameEvents();

            return true;
        } catch (error) {
            console.error('Error al inicializar Pusher:', error);
            this._triggerEvent('connection:error', error);
            return false;
        }
    }

    reconnect() {
        if (!this.key || !this.cluster || !this.authEndpoint) {
            console.warn('Pusher reconnect: no hay credenciales guardadas');
            this._triggerEvent('connection:failed', { message: 'missing_config' });
            return;
        }

        if (this.pusher) {
            try {
                this.pusher.disconnect();
            } catch (e) {
                // ignore
            }
            this.pusher = null;
            this.channel = null;
        }

        this.init(this.key, this.cluster, this.authEndpoint);
    }

    _setupGameEvents() {
        if (!this.channel) {
            return;
        }

        const gameEvents = [
            'game:number_drawn',
            'game:bingo_claimed',
            'game:bingo_accepted',
            'game:game_reset',
            'game:completed',
            'game:player_joined',
            'player:number_marked',
            'game:message',
            'game:postponed'
        ];

        gameEvents.forEach(eventName => {
            this.channel.bind(eventName, (data) => {
                console.log(`Evento recibido: ${eventName}`, data);
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
                    handler => handler !== callback
                );
            } else {
                delete this.eventHandlers[eventName];
            }
        }
    }

    _triggerEvent(eventName, data) {
        if (this.eventHandlers[eventName]) {
            this.eventHandlers[eventName].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Error en manejador de evento ${eventName}:`, error);
                }
            });
        }
    }

    disconnect() {
        if (this._reconnectTimer) {
            clearTimeout(this._reconnectTimer);
            this._reconnectTimer = null;
        }
        if (this.pusher) {
            try {
                this.pusher.disconnect();
            } catch (e) {
                // ignore
            }
            this.isConnected = false;
            this.pusher = null;
            this.channel = null;
        }
    }
}

window.PusherClient = PusherClient;
