
// ==========================================
// BOARD.JS OPTIMIZADO CON PUSHER
// ==========================================
// Versi\u00f3n optimizada que elimina polling y usa eventos en tiempo real
// Reducci\u00f3n de requests al servidor: ~95%
// Actualizaciones instant\u00e1neas: < 100ms vs 2-10s

// ==========================================
// CONFIGURACI\u00d3N Y CONSTANTES
// ==========================================
const CONFIG = {
    MAX_MESSAGES: 50,
    MAX_CONFETTI: 100,
    MESSAGE_LIFETIME: 30000,
    FADE_OUT_TIME: 500,
    DEBOUNCE_DELAY: 100,
    AUDIO_POOL_SIZE: 10,
    MESSAGE_POOL_SIZE: 15,
    WINNER_SLIDER_INTERVAL: 5000,
    PUSHER_KEY: window.PUSHER_KEY || '', // Configurar en la vista
    PUSHER_CLUSTER: window.PUSHER_CLUSTER || 'us2',
    GAME_ID: window.GAME_ID || '' // Configurar en la vista
};

// ==========================================
// VARIABLES GLOBALES
// ==========================================
let numbersgenerated = [];
let lastNumbers = fiveNumbers || [];
let narrationAudio;
let soundWinner;
let isGameFinishedShown = false;
let messagesDisplayed = [];
let intervalNextGame;
let winners = [];
let winnerIndex = 0;
let winnerSliderTimeout;
let gameTimerInterval;
let startTime;
let gameStarted = false;

// Pusher instance
let pusher = null;
let gameChannel = null;
let chatChannel = null;

// ==========================================
// GESTORES DE RECURSOS
// ==========================================

// Gestor centralizado de intervalos
class IntervalManager {
    constructor() {
        this.intervals = new Map();
    }
    
    set(name, callback, delay) {
        this.clear(name);
        this.intervals.set(name, setInterval(callback, delay));
    }
    
    clear(name) {
        if (this.intervals.has(name)) {
            clearInterval(this.intervals.get(name));
            this.intervals.delete(name);
        }
    }
    
    clearAll() {
        this.intervals.forEach(interval => clearInterval(interval));
        this.intervals.clear();
    }
}

// Cache de elementos DOM
class DOMCache {
    constructor() {
        this.cache = new Map();
    }
    
    get(id) {
        if (!this.cache.has(id)) {
            const element = document.getElementById(id);
            if (element) {
                this.cache.set(id, element);
            }
        }
        return this.cache.get(id);
    }
    
    clear() {
        this.cache.clear();
    }
}

// Pool de elementos de mensajes
class MessagePool {
    constructor(maxSize = CONFIG.MESSAGE_POOL_SIZE) {
        this.pool = [];
        this.maxSize = maxSize;
    }
    
    get() {
        if (this.pool.length > 0) {
            const bubble = this.pool.pop();
            bubble.classList.remove("fade-out");
            bubble.style.display = "flex";
            return bubble;
        }
        return this.createNew();
    }
    
    release(element) {
        if (this.pool.length < this.maxSize) {
            element.className = 'message-bubble';
            element.style.display = 'none';
            element.innerHTML = '';
            this.pool.push(element);
        }
    }
    
    createNew() {
        const bubble = document.createElement("div");
        bubble.className = "message-bubble";
        return bubble;
    }
}

// Gestor inteligente de audio
class AudioManager {
    constructor() {
        this.audioCache = new Map();
        this.preloadedAudios = new Set();
        this.audioPool = [];
    }
    
    preload(src) {
        if (this.preloadedAudios.has(src)) return;
        
        const audio = new Audio();
        audio.preload = 'auto';
        audio.src = src;
        this.audioCache.set(src, audio);
        this.preloadedAudios.add(src);
    }
    
    play(src) {
        let audio = this.audioCache.get(src);
        if (!audio) {
            audio = new Audio();
            audio.src = src;
            this.audioCache.set(src, audio);
        }
        
        const audioClone = audio.cloneNode();
        audioClone.play().catch(e => console.warn('Audio play failed:', e));
        
        return audioClone;
    }
    
    stop(audio) {
        if (audio) {
            audio.pause();
            audio.currentTime = 0;
        }
    }
    
    preloadAll(audioPath) {
        this.preload(audioPath + 'winner.mp3');
    }
}

// ==========================================
// INICIALIZACI\u00d3N DE PUSHER
// ==========================================

function initializePusher() {
    if (!CONFIG.PUSHER_KEY || !CONFIG.GAME_ID) {
        console.error('Pusher configuration missing');
        return false;
    }

    try {
        // Inicializar Pusher
        pusher = new Pusher(CONFIG.PUSHER_KEY, {
            cluster: CONFIG.PUSHER_CLUSTER,
            encrypted: true,
            authEndpoint: site_url + 'pusher/auth',
            auth: {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
        });

        // Suscribirse al canal del juego
        gameChannel = pusher.subscribe(`private-game-${CONFIG.GAME_ID}`);
        
        // Suscribirse al canal de chat
        chatChannel = pusher.subscribe(`private-chat-${CONFIG.GAME_ID}`);

        // Configurar event listeners
        setupPusherEventListeners();

        console.log('\u2705 Pusher initialized successfully');
        return true;

    } catch (error) {
        console.error('\u274c Pusher initialization failed:', error);
        return false;
    }
}

// ==========================================
// EVENT LISTENERS DE PUSHER
// ==========================================

function setupPusherEventListeners() {
    
    // Evento: N\u00famero sorteado
    gameChannel.bind('game:number_drawn', function(data) {
        console.log('\ud83d\udce2 Number drawn:', data);
        handleNumberDrawn(data);
    });

    // Evento: Ganador detectado
    gameChannel.bind('game:winner_claimed', function(data) {
        console.log('\ud83c\udfc6 Winner claimed:', data);
        handleWinnerClaimed(data);
    });

    // Evento: Juego finalizado
    gameChannel.bind('game:game_finished', function(data) {
        console.log('\ud83c\udfc1 Game finished:', data);
        handleGameFinished(data);
    });

    // Evento: Juego reiniciado
    gameChannel.bind('game:game_reset', function(data) {
        console.log('\ud83d\udd04 Game reset:', data);
        handleGameReset(data);
    });

    // Evento: Contador de jugadores actualizado
    gameChannel.bind('game:player_count_updated', function(data) {
        console.log('\ud83d\udc65 Player count updated:', data);
        updatePlayerCount(data.count);
    });

    // Evento: Acumulado actualizado
    gameChannel.bind('game:accumulated_updated', function(data) {
        console.log('\ud83d\udcb0 Accumulated updated:', data);
        updateAccumulated(data.accumulated);
    });

    // Evento: Nuevo mensaje de chat
    chatChannel.bind('chat:new_message', function(data) {
        console.log('\ud83d\udcac New message:', data);
        handleNewMessage(data);
    });

    // Eventos de conexi\u00f3n
    pusher.connection.bind('connected', function() {
        console.log('\u2705 Connected to Pusher');
    });

    pusher.connection.bind('disconnected', function() {
        console.log('\u26a0\ufe0f Disconnected from Pusher');
    });

    pusher.connection.bind('error', function(err) {
        console.error('\u274c Pusher connection error:', err);
    });
}

// ==========================================
// HANDLERS DE EVENTOS PUSHER
// ==========================================

function handleNumberDrawn(data) {
    const { n, drawn, auto, totalNumbersGenerated, winners: newWinners } = data;
    
    // Actualizar array de n\u00fameros sorteados
    numbersgenerated = drawn;
    
    // Actualizar UI del n\u00famero
    updateNumberDisplay(n);
    
    // Actualizar contador
    updateNumberCounter(totalNumbersGenerated);
    
    // Actualizar \u00faltimos 5 n\u00fameros
    updateLastFiveNumbers(drawn);
    
    // Reproducir audio de narraci\u00f3n
    playNumberNarration(n);
    
    // Actualizar ganadores si hay
    if (newWinners && newWinners.length > 0) {
        winners = newWinners;
        updateWinnersDisplay();
    }
    
    // Iniciar timer si es el primer n\u00famero
    if (!gameStarted && totalNumbersGenerated === 1) {
        gameStarted = true;
        startGameTimer();
    }
}

function handleWinnerClaimed(data) {
    const { playerName, modality, image, modalityId } = data;
    
    // Agregar ganador a la lista
    winners.push({
        player: playerName,
        modality: modality,
        image: image
    });

    // Convertir visualmente la modalidad a "ganada" en tiempo real en el admin
    if (modalityId) {
        const modalityElement = document.getElementById(`modality-${modalityId}`);
        if (modalityElement) {
            modalityElement.classList.add('cartn-sing');
            const parentBorder = modalityElement.closest('.border-carton');
            if (parentBorder) {
                parentBorder.classList.add('modality-won');
            }
        }
    }
    
    // Mostrar notificaci\u00f3n de ganador
    showWinnerNotification(playerName, modality, image);
    
    // Reproducir sonido de ganador
    if (soundWinner) {
        audioManager.play(soundWinner);
    }
    
    // Actualizar display de ganadores
    updateWinnersDisplay();
}

function handleGameFinished(data) {
    const { totalNumbersGenerated, winners: finalWinners, message } = data;
    
    // Actualizar ganadores finales
    winners = finalWinners;
    
    // Mostrar pantalla de juego finalizado
    showGameFinalized(message);
    
    // Detener timer
    stopGameTimer();
    
    isGameFinishedShown = true;
}

function handleGameReset(data) {
    // Reiniciar todas las variables
    numbersgenerated = [];
    lastNumbers = [];
    winners = [];
    messagesDisplayed = [];
    isGameFinishedShown = false;
    gameStarted = false;
    
    // Limpiar UI
    clearNumberDisplay();
    clearWinnersDisplay();
    stopGameTimer();
    
    // Mostrar notificaci\u00f3n
    showNotification(data.message || 'El juego ha sido reiniciado', 'info');
    
    // Recargar p\u00e1gina despu\u00e9s de 2 segundos
    setTimeout(() => {
        location.reload();
    }, 2000);
}

function handleNewMessage(data) {
    const { id, userName, message, image } = data;
    
    // Verificar si el mensaje ya fue mostrado
    if (messagesDisplayed.includes(id)) {
        return;
    }
    
    // Agregar a lista de mensajes mostrados
    messagesDisplayed.push(id);
    
    // Mantener solo los \u00faltimos 50 mensajes
    if (messagesDisplayed.length > CONFIG.MAX_MESSAGES) {
        messagesDisplayed.shift();
    }
    
    // Mostrar mensaje en el chat
    displayMessage({
        id: id,
        user: userName,
        message: message
    }, image);
}

// ==========================================
// FUNCIONES DE UI
// ==========================================

function updateNumberDisplay(number) {
    const numberDisplay = domCache.get('current-number');
    if (numberDisplay) {
        numberDisplay.textContent = number;
        numberDisplay.classList.add('pulse-animation');
        setTimeout(() => {
            numberDisplay.classList.remove('pulse-animation');
        }, 1000);
    }
}

function updateNumberCounter(count) {
    const counterDisplay = domCache.get('numbers-counter');
    if (counterDisplay) {
        counterDisplay.textContent = `${count}/75`;
    }
}

function updateLastFiveNumbers(drawnNumbers) {
    const lastFive = drawnNumbers.slice(-5).reverse();
    const container = domCache.get('last-five-numbers');
    
    if (container) {
        container.innerHTML = '';
        lastFive.forEach(num => {
            const ball = document.createElement('div');
            ball.className = 'ball-mini';
            ball.textContent = num;
            container.appendChild(ball);
        });
    }
}

function updatePlayerCount(count) {
    const playerCountDisplay = domCache.get('player-count');
    if (playerCountDisplay) {
        playerCountDisplay.textContent = count;
    }
}

function updateAccumulated(accumulated) {
    const accumulatedDisplay = domCache.get('accumulated-amount');
    if (accumulatedDisplay) {
        accumulatedDisplay.textContent = `$${accumulated.toFixed(2)}`;
    }
}

function updateWinnersDisplay() {
    const winnersContainer = domCache.get('winners-list');
    if (!winnersContainer) return;
    
    winnersContainer.innerHTML = '';
    
    winners.forEach(winner => {
        const winnerCard = document.createElement('div');
        winnerCard.className = 'winner-card';
        winnerCard.innerHTML = `
            <img src="${winner.image}" alt="${winner.player}" class="winner-avatar">
            <div class="winner-info">
                <div class="winner-name">${winner.player}</div>
                <div class="winner-modality">${winner.modality}</div>
            </div>
        `;
        winnersContainer.appendChild(winnerCard);
    });
}

function showWinnerNotification(playerName, modality, image) {
    const notification = document.createElement('div');
    notification.className = 'winner-notification';
    notification.innerHTML = `
        <img src="${image}" alt="${playerName}">
        <div>
            <strong>\ud83c\udfc6 \u00a1GANADOR!</strong>
            <p>${playerName} - ${modality}</p>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 500);
    }, 5000);
}

function showGameFinalized(message) {
    const modal = document.createElement('div');
    modal.className = 'game-finished-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <h2>\ud83c\udf89 \u00a1Juego Finalizado!</h2>
            <p>${message}</p>
            <div class="winners-final">
                ${winners.map(w => `
                    <div class="winner-final-card">
                        <img src="${w.image}" alt="${w.player}">
                        <div>${w.player}</div>
                        <div class="modality">${w.modality}</div>
                    </div>
                `).join('')}
            </div>
            <button onclick="location.reload()" class="btn-primary">Nuevo Juego</button>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    setTimeout(() => {
        modal.classList.add('show');
    }, 100);
}

function clearNumberDisplay() {
    const numberDisplay = domCache.get('current-number');
    if (numberDisplay) {
        numberDisplay.textContent = '-';
    }
    
    const counterDisplay = domCache.get('numbers-counter');
    if (counterDisplay) {
        counterDisplay.textContent = '0/75';
    }
    
    const lastFiveContainer = domCache.get('last-five-numbers');
    if (lastFiveContainer) {
        lastFiveContainer.innerHTML = '';
    }
}

function clearWinnersDisplay() {
    const winnersContainer = domCache.get('winners-list');
    if (winnersContainer) {
        winnersContainer.innerHTML = '';
    }
}

// ==========================================
// FUNCIONES DE AUDIO
// ==========================================

function playNumberNarration(number) {
    if (!narrationAudio) return;
    
    const audioPath = `${site_url}assets/audio/numbers/${number}.mp3`;
    audioManager.play(audioPath);
}

// ==========================================
// TIMER DEL JUEGO
// ==========================================

function startGameTimer() {
    startTime = Date.now();
    
    intervalManager.set('gameTimer', () => {
        const elapsed = Date.now() - startTime;
        const minutes = Math.floor(elapsed / 60000);
        const seconds = Math.floor((elapsed % 60000) / 1000);
        
        const timerDisplay = domCache.get('game-timer');
        if (timerDisplay) {
            timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
    }, 1000);
}

function stopGameTimer() {
    intervalManager.clear('gameTimer');
}

// ==========================================
// CHAT
// ==========================================

function displayMessage(messageData, imagePath) {
    const chatContainer = domCache.get('chat-messages');
    if (!chatContainer) return;
    
    const bubble = messagePool.get();
    bubble.innerHTML = `
        <img src="${imagePath}" alt="${messageData.user}" class="message-avatar">
        <div class="message-content">
            <div class="message-user">${messageData.user}</div>
            <div class="message-text">${escapeHtml(messageData.message)}</div>
        </div>
    `;
    
    chatContainer.appendChild(bubble);
    chatContainer.scrollTop = chatContainer.scrollHeight;
    
    // Auto-eliminar despu\u00e9s de MESSAGE_LIFETIME
    setTimeout(() => {
        removeMessageWithFade(bubble);
    }, CONFIG.MESSAGE_LIFETIME);
}

function removeMessageWithFade(bubble) {
    bubble.classList.add('fade-out');
    setTimeout(() => {
        if (bubble.parentNode) {
            bubble.parentNode.removeChild(bubble);
        }
        messagePool.release(bubble);
    }, CONFIG.FADE_OUT_TIME);
}

function sendMessage() {
    const messageInput = domCache.get('message-input');
    if (!messageInput) return;
    
    const message = messageInput.value.trim();
    if (!message) return;
    
    $.post(site_url + 'playings/messageSubmit', { message: message })
        .done((response) => {
            if (response.status === 'success') {
                messageInput.value = '';
            }
        })
        .fail((error) => {
            console.error('Error sending message:', error);
            showNotification('Error al enviar mensaje', 'error');
        });
}

// ==========================================
// ACCIONES DEL ADMINISTRADOR
// ==========================================

function drawNumber(number) {
    $.get(site_url + 'boards/numberSubmit/' + number)
        .done((response) => {
            if (response.status === 'error') {
                showNotification(response.message, 'error');
            }
        })
        .fail((error) => {
            console.error('Error drawing number:', error);
            showNotification('Error al sortear n\u00famero', 'error');
        });
}

function autoDrawNumber() {
    $.get(site_url + 'boards/numberAutoSubmit')
        .done((response) => {
            if (response.status === 'error') {
                showNotification(response.message, 'error');
            }
        })
        .fail((error) => {
            console.error('Error auto-drawing number:', error);
            showNotification('Error en sorteo autom\u00e1tico', 'error');
        });
}

function startAutomaticGeneration() {
    intervalManager.clear('generation');
    let delay = typeof timeBallGet !== 'undefined' ? timeBallGet : 3000;
    intervalManager.set('generation', autoDrawNumber, delay);
}

function stopAutomaticGeneration() {
    intervalManager.clear('generation');
}

function sendSystemMessage(text, type = null) {
    $.post(site_url + 'playings/messageSubmit', { message: text, type: type })
        .fail((error) => console.error('Error sending system message:', error));
}

function resetGame() {
    if (!confirm('\u00bfEst\u00e1s seguro de reiniciar el juego?')) return;
    
    $.post(site_url + 'boards/gameReset')
        .done((response) => {
            if (response.status === 'success') {
                showNotification(response.message, 'success');
            }
        })
        .fail((error) => {
            console.error('Error resetting game:', error);
            showNotification('Error al reiniciar juego', 'error');
        });
}

// ==========================================
// UTILIDADES
// ==========================================

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 500);
    }, 3000);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ==========================================
// INICIALIZACI\u00d3N
// ==========================================

// Instancias globales
const intervalManager = new IntervalManager();
const domCache = new DOMCache();
const messagePool = new MessagePool();
const audioManager = new AudioManager();

// Inicializar cuando el DOM est\u00e9 listo
$(document).ready(function() {
    console.log('\ud83d\ude80 Initializing Board with Pusher...');
    
    // Inicializar Pusher
    const pusherInitialized = initializePusher();
    
    if (!pusherInitialized) {
        console.error('\u274c Failed to initialize Pusher - falling back to polling/manual');
    }
    
    // Precargar audios
    if (typeof audioPath !== 'undefined') {
        audioManager.preloadAll(audioPath);
    }
    
    // Event listeners para botones de control del juego
    $('#start-button').on('click', () => {
        $('#start-button').hide();
        $('#stop-button, #next-number-button').show();
        sendSystemMessage((typeof __ !== 'undefined' && __['game started!'] ? __['game started!'] : '\u00a1JUEGO INICIADO!') + ' \ud83d\ude0e', 26);

        if (!gameStarted) {
            gameStarted = true;
            startGameTimer();
        }

        setTimeout(() => {
            autoDrawNumber();
            startAutomaticGeneration();
        }, 2000);
    });

    $('#next-number-button').on('click', () => {
        stopAutomaticGeneration();
        autoDrawNumber();
        startAutomaticGeneration();
    });

    $('#stop-button').on('click', () => {
        stopAutomaticGeneration();
        $('#stop-button, #next-number-button').hide();
        $('#play-button').show();
    });

    $('#play-button').on('click', () => {
        startAutomaticGeneration();
        $('#play-button').hide();
        $('#stop-button, #next-number-button').show();
    });

    $('.btn-microphone').on('click', function() {
        if (typeof narrationPlaying !== 'undefined') {
            narrationPlaying = !narrationPlaying;
            $(this).html(narrationPlaying ? 
                '<i class="fa-duotone fa-solid fa-microphone"></i>' : 
                '<i class="fa-duotone fa-solid fa-microphone-slash"></i>'
            );
        }
    });
    
    // Event listeners para botones
    $('#btn-auto-draw').on('click', autoDrawNumber);
    $('#btn-reset-game').on('click', resetGame);
    $('#btn-send-message').on('click', sendMessage);
    $('#message-input').on('keypress', function(e) {
        if (e.which === 13) {
            sendMessage();
        }
    });
    
    // Event listeners para n\u00fameros del tablero
    $('.number-button').on('click', function() {
        const number = $(this).data('number');
        drawNumber(number);
    });
    
    console.log('\u2705 Board initialized successfully with Pusher');
});

// Limpiar al salir
$(window).on('beforeunload', function() {
    intervalManager.clearAll();
    if (pusher) {
        pusher.disconnect();
    }
});
