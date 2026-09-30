/**
 * PusherClient - Cliente para la integración con Pusher
 * 
 * Esta clase maneja la conexión con Pusher y los eventos del juego de Bingo
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
    }

    init(key, cluster, authEndpoint) {
        try {
            console.log('Inicializando Pusher con:', { key, cluster, authEndpoint });
            
            this.pusher = new Pusher(key, {
                cluster: cluster,
                channelAuthorization: {
                    endpoint: authEndpoint,
                    transport: 'ajax'
                }
            });
            
            const channelName = 'private-game-' + this.gameId;
            this.channel = this.pusher.subscribe(channelName);
            
            // Manejar eventos de conexión
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
                    setTimeout(() => this.reconnect(), 2000);
                } else {
                    console.error('Número máximo de intentos alcanzado');
                    this._triggerEvent('connection:failed', error);
                }
            });
            
            // Configurar eventos del juego
            this._setupGameEvents();
            
            return true;
        } catch (error) {
            console.error('Error al inicializar Pusher:', error);
            this._triggerEvent('connection:error', error);
            return false;
        }
    }
    
    reconnect() {
        if (this.pusher) {
            this.pusher.disconnect();
        }
        
        this.init(
            this.pusher.config.key,
            this.pusher.config.cluster,
            this.pusher.config.channelAuthorization.endpoint
        );
    }
    
    _setupGameEvents() {
        // Eventos del juego
        const gameEvents = [
            'game:number_drawn',
            'game:bingo_claimed',
            'game:bingo_accepted',
            'game:game_reset',
            'game:completed',
            'game:player_joined',
            'player:number_marked',
            'game:message',
            'game:chat_message',
            'game:postponed',
            'game:started'
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
        if (this.pusher) {
            this.pusher.disconnect();
            this.isConnected = false;
        }
    }
}

// Exportar para uso global
window.PusherClient = PusherClient;
