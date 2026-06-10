// ==========================================
// CONFIGURACIÓN Y CONSTANTES
// ==========================================
const CONFIG = {
    MAX_MESSAGES: 50,        // Aumentado para el nuevo sistema
    MAX_CONFETTI: 100,
    BASE_POLL_INTERVAL: 2000,
    CHAT_POLL_INTERVAL: 600,
    MAX_POLL_INTERVAL: 10000,
    USER_COUNT_INTERVAL: 2500,
    ACCUMULATED_COUNT_INTERVAL: 2500,
    MESSAGE_LIFETIME: 30000, // 30 segundos para mensajes
    FADE_OUT_TIME: 500,      // Tiempo de animación de desvanecimiento
    DEBOUNCE_DELAY: 100,
    AUDIO_POOL_SIZE: 10,
    MESSAGE_POOL_SIZE: 15,
    WINNER_SLIDER_INTERVAL: 5000,
    COUNTDOWN_INTERVAL: 1000
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
let lastChatPollId = 0;
let pendingOutgoingMessageIds = new Set();
let chatSendInFlight = false;
let intervalNextGame;
let winners = [];
let winnerIndex = 0;
let winnerSliderTimeout;
let gameTimerInterval;
let startTime;
let bingoInProgress = false; // Nueva variable para controlar si hay un bingo en progreso
let simultaneousBingos = []; // Nueva variable para manejar bingos simultáneos

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

// Pool de elementos de mensajes para reutilización
class MessagePool {
    constructor(maxSize = CONFIG.MESSAGE_POOL_SIZE) {
        this.pool = [];
        this.maxSize = maxSize;
    }
    
    get() {
        if (this.pool.length > 0) {
            return this.pool.pop();
        }
        return this.createNew();
    }
    
    release(element) {
        if (this.pool.length < this.maxSize) {
            element.className = 'message-bubble';
            element.style.cssText = '';
            element.innerHTML = '';
            this.pool.push(element);
        }
    }
    
    createNew() {
        const bubble = document.createElement("div");
        bubble.classList.add("message-bubble");
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
        
        // Clone para permitir múltiples reproducciones simultáneas
        const audioClone = audio.cloneNode();
        audioClone.play().catch(e => console.warn('Audio play failed:', e));
        
        return audioClone;
    }
    
    preloadNumberAudios() {
        // Precargar audios de números 1-75
        for (let i = 1; i <= 75; i++) {
            this.preload(audioPath + i + '.mp3');
        }
        this.preload(audioPath + 'winner.mp3');
    }
}

// Polling inteligente con backoff exponencial
class SmartPoller {
    constructor(baseInterval = CONFIG.BASE_POLL_INTERVAL) {
        this.baseInterval = baseInterval;
        this.currentInterval = baseInterval;
        this.maxInterval = CONFIG.MAX_POLL_INTERVAL;
        this.consecutiveErrors = 0;
        this.isActive = true;
        this.timeoutId = null;
    }
    
    async poll(callback) {
        if (!this.isActive) return;
        
        try {
            const result = await callback();
            
            // Reset interval on success or empty poll
            if (result && (result.status === 'success' || result.status === 'empty')) {
                this.currentInterval = this.baseInterval;
                this.consecutiveErrors = 0;
            }
            
        } catch (error) {
            this.consecutiveErrors++;
            // Exponential backoff on errors
            this.currentInterval = Math.min(
                this.baseInterval * Math.pow(2, this.consecutiveErrors),
                this.maxInterval
            );
            console.warn('Polling error:', error);
        }
        
        this.timeoutId = setTimeout(() => this.poll(callback), this.currentInterval);
    }
    
    stop() {
        this.isActive = false;
        if (this.timeoutId) {
            clearTimeout(this.timeoutId);
            this.timeoutId = null;
        }
    }
    
    restart() {
        this.stop();
        this.isActive = true;
        this.currentInterval = this.baseInterval;
        this.consecutiveErrors = 0;
        this.poll(this.lastCallback);
    }
}

// Confetti optimizado con Canvas
class CanvasConfetti {
    constructor() {
        this.particles = [];
        this.isActive = false;
        this.activeElements = new Set();
    }
    
    createParticles() {
        const emojis = ['🎉', '🎊', '✨', '🌟', '🥳', '🍾', '💥', '🔥', '💫', '🍬', '🎈'];
        this.particles = [];
        
        // Limpiar partículas anteriores si existen
        this.cleanup();
        
        for (let i = 0; i < CONFIG.MAX_CONFETTI; i++) {
            // Crear elemento DOM para cada partícula
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.textContent = emojis[Math.floor(Math.random() * emojis.length)];
            
            // Propiedades mejoradas basadas en tu función preferida
            const particle = {
                element: confetti,
                emoji: confetti.textContent,
                x: Math.random() * 100,
                y: Math.random() * -100,
                vx: (Math.random() - 0.5) * 2,
                vy: Math.random() * 1.5 + 0.5, // velocidad más lenta
                rotation: Math.random() * 360,
                rotationSpeed: (Math.random() - 0.5) * 3,
                size: Math.random() * 30 + 10,
                alpha: 1,
                decay: Math.random() * 0.02 + 0.01,
                animationDuration: Math.random() * 6 + 4, // animación más lenta
                animationDelay: Math.random()
            };
            
            // Aplicar estilos CSS mejorados
            confetti.style.cssText = `
                position: fixed;
                left: ${particle.x}vw;
                top: ${particle.y}vh;
                font-size: ${particle.size}px;
                animation-duration: ${particle.animationDuration}s;
                animation-delay: ${particle.animationDelay}s;
                animation-name: confettiFall;
                animation-timing-function: ease-out;
                animation-fill-mode: forwards;
                pointer-events: none;
                z-index: 9999;
                transform: rotate(${particle.rotation}deg);
                user-select: none;
            `;
            
            // Agregar al DOM
            document.body.appendChild(confetti);
            this.activeElements.add(confetti);
            
            // Auto-eliminar cuando termine la animación
            const handleAnimationEnd = () => {
                if (confetti.parentNode) {
                    confetti.parentNode.removeChild(confetti);
                }
                this.activeElements.delete(confetti);
                confetti.removeEventListener('animationend', handleAnimationEnd);
            };
            
            confetti.addEventListener('animationend', handleAnimationEnd);
            
            this.particles.push(particle);
        }
    }
    
    cleanup() {
        // Limpiar elementos activos
        this.activeElements.forEach(element => {
            if (element.parentNode) {
                element.parentNode.removeChild(element);
            }
        });
        this.activeElements.clear();
        this.particles = [];
    }
    
    start() {
        if (this.isActive) return;
        
        this.isActive = true;
        this.createParticles();
        
        // Auto-stop después de la duración máxima de animación
        setTimeout(() => {
            this.stop();
        }, 6000); // 5s max duration + 1s buffer
    }
    
    stop() {
        this.isActive = false;
        // Los elementos se limpiarán automáticamente cuando termine su animación
    }
    
    forceStop() {
        this.isActive = false;
        this.cleanup();
    }
    
    resize() {
        // Método para manejar cambios de tamaño
        if (this.isActive) {
            this.forceStop();
            setTimeout(() => this.start(), 100);
        }
    }
}

// ==========================================
// INSTANCIAS GLOBALES
// ==========================================
const intervalManager = new IntervalManager();
const domCache = new DOMCache();
const messagePool = new MessagePool();
const audioManager = new AudioManager();
const messagePoller = new SmartPoller(CONFIG.CHAT_POLL_INTERVAL);
const confettiManager = new CanvasConfetti();

// ==========================================
// UTILIDADES
// ==========================================
const $id = (id) => domCache.get(id);

// Debounce function
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

// Throttle function
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    }
}

// ==========================================
// CONFIGURACIÓN INICIAL DE CARTONES
// ==========================================
function setupCartonLayout() {
    const container = document.querySelector('.content-cartons');
    const cartons = document.querySelectorAll('.bingo-carton');
    
    if (!container || !cartons.length) return;
    
    // Limpiar clases previas
    container.classList.remove('one-carton', 'two-cartons', 'three-cartons', 'four-cartons');
    
    // Aplicar clase según cantidad de cartones
    const classMap = {
        1: 'one-carton',
        2: 'two-cartons', 
        3: 'three-cartons',
        4: 'four-cartons'
    };
    
    const className = classMap[cartons.length];
    if (className) {
        container.classList.add(className);
    }
}

// ==========================================
// FUNCIONES DE CHAT MEJORADAS
// ==========================================

// Función para crear burbujas de mensaje estilo redes sociales
function createMessageBubble(content, profilePicUrl, isOwn = false) {
    const bubble = messagePool.get();
    bubble.style.display = "flex";
    
    // Configurar alineación
    if (isOwn) {
        bubble.classList.add("own-message");
    } else {
        bubble.classList.remove("own-message");
    }
    
    // Reutilizar o crear imagen de perfil
    let img = bubble.querySelector('.profile-pic');
    if (!img) {
        img = document.createElement("img");
        img.classList.add("profile-pic");
        bubble.appendChild(img);
    }
    img.src = profilePicUrl || 'default-avatar.png';
    
    // Reutilizar o crear span para el contenido
    let span = bubble.querySelector('span');
    if (!span) {
        span = document.createElement("span");
        bubble.appendChild(span);
    }
    
    span.textContent = content;
    span.style.fontSize = '';
    
    // Check if the content is only emojis (no alphanumeric or standard punctuation characters)
    const trimmed = content.trim();
    const isOnlyEmoji = !/[\p{L}\p{N}¡!¿?.,;]/u.test(trimmed) && trimmed.length <= 8;
    span.className = isOnlyEmoji ? 'emoji-message' : 'text-message';
    bubble.style.background = '';

    return bubble;
}

// Función para eliminar mensajes con animación mejorada
function removeMessageWithFade(el) {
    el.classList.add("fade-out");
    setTimeout(() => {
        if (el.parentNode) {
            el.parentNode.removeChild(el);
            messagePool.release(el);
        }
    }, CONFIG.FADE_OUT_TIME);
}

// Función para limitar mensajes con el nuevo sistema
function limitMessages() {
    const display = $id("message-display");
    if (!display) return;
    
    const bubbles = display.getElementsByClassName("message-bubble");
    while (bubbles.length >= CONFIG.MAX_MESSAGES) {
        removeMessageWithFade(bubbles[0]);
    }
}

// Scroll optimizado con debounce para el nuevo chat
const debouncedScroll = debounce(() => {
    const el = $id("message-display");
    if (el) {
        // Para el nuevo sistema que usa column-reverse, scroll al final
        el.scrollTop = el.scrollHeight;
    }
}, CONFIG.DEBOUNCE_DELAY);

function scrollToBottom() {
    debouncedScroll();
}

function getMessageText(messageData) {
    if (!messageData) return '';
    if (typeof messageData === 'string') return messageData;
    return messageData.message || messageData.text || '';
}

function getCurrentUserId() {
    if (typeof window.currentUserId !== 'undefined' && window.currentUserId !== null && window.currentUserId !== '') {
        return parseInt(window.currentUserId, 10) || 0;
    }

    if (typeof USER_ID !== 'undefined' && USER_ID !== null && USER_ID !== '') {
        return parseInt(USER_ID, 10) || 0;
    }

    return 0;
}

function isOwnBingoEvent(data) {
    if (!data) {
        return false;
    }

    if (data.isOwnBingo === true) {
        return true;
    }

    const winnerUserId = parseInt(data.winnerUserId, 10);
    const currentUserId = getCurrentUserId();

    return winnerUserId > 0 && currentUserId > 0 && winnerUserId === currentUserId;
}

function applyNumberGetMeta(data) {
    if (!data) {
        return;
    }

    if (typeof data.currentUserId !== 'undefined' && data.currentUserId !== null && data.currentUserId !== '') {
        window.currentUserId = parseInt(data.currentUserId, 10) || window.currentUserId;
    }

    if (typeof data.gameHasWinner !== 'undefined') {
        window.gameHasWinner = !!data.gameHasWinner;
    }
}

function registerChatMessageId(messageId) {
    const parsed = parseInt(messageId, 10);
    if (Number.isNaN(parsed) || parsed <= 0) {
        return;
    }

    lastChatPollId = Math.max(lastChatPollId, parsed);

    if (!messagesDisplayed.includes(parsed)) {
        messagesDisplayed.push(parsed);
    }
}

function getLastChatMessageId() {
    return lastChatPollId;
}

function processIncomingChatMessages(data) {
    if (!data) return;

    const list = Array.isArray(data.messages)
        ? data.messages
        : (data.status === 'success' && data.message ? [data.message] : []);

    const currentUserId = getCurrentUserId();

    list.forEach((row) => {
        const id = parseInt(row.id, 10);
        const text = getMessageText(row);
        if (!text) return;

        if (!Number.isNaN(id) && id > 0) {
            if (messagesDisplayed.includes(id)) {
                return;
            }

            if (pendingOutgoingMessageIds.has(id)) {
                pendingOutgoingMessageIds.delete(id);
                registerChatMessageId(id);
                return;
            }

            registerChatMessageId(id);
        }

        const rowUserId = parseInt(row.user, 10);
        const isOwn = !Number.isNaN(rowUserId) && rowUserId > 0
            ? rowUserId === currentUserId
            : false;

        displayMessage(
            { message: text, id: Number.isNaN(id) || id <= 0 ? undefined : id },
            row.image || data.image,
            isOwn
        );
    });
}

// Función mejorada para mostrar mensajes estilo redes sociales
function displayMessage(messageData, imageUrl, isOwn = false) {
    const display = $id("message-display");
    if (!display) return;
    
    limitMessages();

    const bubble = createMessageBubble(
        getMessageText(messageData),
        imageUrl || imagePath || 'default-avatar.png',
        isOwn
    );
    
    // Insertar al principio para que aparezca abajo (ya que usamos column-reverse)
    display.insertBefore(bubble, display.firstChild);
    
    if (messageData.id) {
        const msgId = parseInt(messageData.id, 10);
        if (!Number.isNaN(msgId) && msgId > 0) {
            registerChatMessageId(msgId);
        }
    }
    
    // Programar eliminación automática
    setTimeout(() => removeMessageWithFade(bubble), CONFIG.MESSAGE_LIFETIME);
}

// Función mejorada para enviar mensajes
function sendMessage(content, id) {
    if (!content || !content.trim()) return;
    if (chatSendInFlight) return;

    const trimmedContent = content.trim();
    chatSendInFlight = true;

    const inputField = $('#message-send-new');
    if (inputField.length) {
        inputField.val('');
    }

    displayMessage({ message: trimmedContent }, imagePath, true);

    $.post(site_url + 'playings/messageSubmit', { message: trimmedContent })
        .done((data) => {
            if (data.status === 'success') {
                const msgId = parseInt(data.id, 10);
                if (!Number.isNaN(msgId) && msgId > 0) {
                    pendingOutgoingMessageIds.add(msgId);
                    registerChatMessageId(msgId);
                }
            }
        })
        .fail(() => {
            console.warn('Error al enviar mensaje');
        })
        .always(() => {
            chatSendInFlight = false;
        });
}

// Función para enviar emojis (reutiliza la lógica de sendMessage)
function sendEmoji(content, id) {
    sendMessage(content, id);
}

// Función para enviar mensaje desde el campo de texto
function sendMessageText() {
    const input = document.getElementById('message-send-new');
    if (!input) return;
    const content = input.value;
    if (content.trim() === '') return;
    sendMessage(content);
}

// ==========================================
// AUTO-BINGO: si el jugador completa cartón lleno
// ==========================================
let lastAutoSingBall = null;
let autoSingInFlight = false;
let ballRevealTimer = null;
let ballRevealAfterTimer = null;
let ballRevealSequence = 0;
let pendingMarkNumber = null;
let pendingMarkSequence = 0;
let autoMarkedNumbers = new Set();
let autoSingCheckTimer = null;

function getLastBoardNumber() {
    const el = document.querySelector('#last-number span');
    if (el) {
        const val = parseInt((el.textContent || '').trim(), 10);
        if (!Number.isNaN(val)) {
            return val;
        }
    }

    if (numbersgenerated.length) {
        return numbersgenerated[numbersgenerated.length - 1];
    }

    if (Array.isArray(lastNumbers) && lastNumbers.length) {
        return lastNumbers[lastNumbers.length - 1];
    }

    return null;
}

function isCardWinningPattern(cartonEl) {
    if (!cartonEl) return false;
    const cells = Array.from(cartonEl.querySelectorAll('.bingo-carton-number'));
    if (cells.length < 24) return false;

    // Obtener las posiciones marcadas en este cartón
    const markedPositions = new Set();
    cells.forEach(cell => {
        if (cell.classList.contains('modality') || cell.classList.contains('data-position-13') || cell.classList.contains('marked')) {
            const pos = parseInt(cell.getAttribute('data-position'), 10);
            if (!Number.isNaN(pos)) {
                markedPositions.add(pos);
            }
        }
    });
    // Agregar la posición del medio por defecto
    markedPositions.add(13);

    const lastBall = getLastBoardNumber();

    // Comprobar contra las modalidades activas
    if (window.activeModalities && window.activeModalities.length > 0) {
        return window.activeModalities.some(modality => {
            if (!modality.positions) return false;
            const requiredPositions = modality.positions.split(',').map(Number);
            if (!requiredPositions.every(pos => markedPositions.has(pos))) {
                return false;
            }

            if (window.singBingoOnlyLastBall === true && lastBall) {
                const lastBallCell = cartonEl.querySelector(`.bingo-carton-number.number-${lastBall}`);
                if (!lastBallCell || !lastBallCell.classList.contains('marked')) {
                    return false;
                }

                const lastBallPos = parseInt(lastBallCell.getAttribute('data-position'), 10);
                if (!requiredPositions.includes(lastBallPos)) {
                    return false;
                }
            }

            return true;
        });
    }

    // Fallback: requerir cartón lleno (25 posiciones) si no hay modalidades definidas
    return markedPositions.size >= 25;
}

function registerWinner(player, modality) {
    if (!player) {
        return;
    }

    mergeWinnersFromServer([{ player: player, modality: modality || '' }]);
}

function buildWinnersFinalText() {
    const finishedLabel = (__['game finished!'] || 'JUEGO FINALIZADO').toUpperCase();

    if (!winners.length) {
        return finishedLabel;
    }

    if (winners.length === 1) {
        return `${finishedLabel}<br><br><strong>🎉 ${winners[0].player}</strong><br>${winners[0].modality}`;
    }

    const lines = winners.map(function(w) {
        return `🎉 ${w.player} — ${w.modality}`;
    }).join('<br>');

    return `${finishedLabel}<br><br>${lines}`;
}

function fetchWinnersBeforeFinalize(callback) {
    $.get(site_url + 'playings/winnersGet')
        .done(function(data) {
            if (data && data.status === 'success' && Array.isArray(data.winners)) {
                mergeWinnersFromServer(data.winners);
            }
        })
        .always(function() {
            if (typeof callback === 'function') {
                callback();
            }
        });
}

function handleBingoSuccess(data, resumeCallback) {
    if (!data || data.status !== 'success') {
        return;
    }

    window.gameHasWinner = true;

    registerWinner(data.player, data.modality);

    if (Array.isArray(data.winners) && data.winners.length) {
        mergeWinnersFromServer(data.winners);
    }

    bingoInProgress = true;
    intervalManager.clear('lastNumber');

    if (typeof sendEmoji === 'function') {
        sendEmoji('🥳', 21);
    }

    const cartonElement = document.getElementById(`carton-${data.carton}`);
    if (cartonElement && Array.isArray(data.numbers)) {
        data.numbers.forEach((num) => {
            const numberElement = cartonElement.querySelector(`.bingo-carton-number.number-${num}`);
            if (numberElement) {
                numberElement.classList.add('carton-sing');
            }
        });
    }

    const afterCountdown = function() {
        if (data.gameCompleted) {
            showGameFinalized();
            return;
        }

        if (typeof resumeCallback === 'function') {
            resumeCallback();
        } else {
            startAutomaticLast();
        }
    };

    showCountdown({
        player: data.player,
        modality: data.modality,
        modalityId: data.modalityId,
        image: data.image
    }, afterCountdown);
}

function mergeWinnersFromServer(serverWinners) {
    if (!Array.isArray(serverWinners)) {
        return;
    }

    serverWinners.forEach(function(winner) {
        const player = winner.player || '';
        const modality = winner.modality || '';

        if (!player) {
            return;
        }

        if (!winners.some(function(existing) {
            return existing.player === player && existing.modality === modality;
        })) {
            winners.push({ player: player, modality: modality });
        }
    });
}

function scheduleAutoSingCheck(delay) {
    if (!isAutoMarkEnabled()) {
        return;
    }

    if (autoSingCheckTimer) {
        clearTimeout(autoSingCheckTimer);
    }

    autoSingCheckTimer = setTimeout(function() {
        autoSingCheckTimer = null;
        autoSingIfComplete();
    }, typeof delay === 'number' ? delay : 450);
}

function autoSingIfComplete() {
    if (autoSingInFlight || bingoInProgress || window.gameIsFinished || window.gameHasWinner) {
        return;
    }

    if (!isAutoMarkEnabled()) {
        return;
    }

    if (!numbersgenerated.length) {
        return;
    }

    const lastBall = getLastBoardNumber();
    if (!lastBall) {
        return;
    }

    if (lastAutoSingBall === lastBall) {
        return;
    }

    const cartons = Array.from(document.querySelectorAll('.bingo-carton'));
    if (!cartons.length) {
        return;
    }

    const anyFull = cartons.some(isCardWinningPattern);
    if (!anyFull) {
        return;
    }

    autoSingInFlight = true;
    $.post(site_url + 'playings/singBingo', {})
        .done((data) => {
            if (data && data.status === 'success') {
                lastAutoSingBall = lastBall;
                handleBingoSuccess(data, startAutomaticLast);
            } else if (data && data.gameHasWinner) {
                window.gameHasWinner = true;
            } else if (data && data.message) {
                console.warn('autoSingIfComplete:', data.message);
            }
        })
        .always(() => {
            autoSingInFlight = false;
        });
}

// Polling de chat (mensajes de otros jugadores / admin en la misma partida)
function pollMessagesOptimized() {
    return new Promise((resolve) => {
        $.get(site_url + 'playings/messageGet', { after_id: getLastChatMessageId() })
            .done((data) => {
                if (data.status === 'stop') {
                    messagePoller.stop();
                    return resolve(data);
                }
                processIncomingChatMessages(data);
                resolve(data);
            })
            .fail((error) => {
                console.warn('Error en polling de mensajes:', error);
                resolve({ status: 'error' });
            });
    });
}

// Ejecutar auto-sing periódicamente (sin spamear)
setInterval(() => {
    try { autoSingIfComplete(); } catch (e) {}
}, 1500);

// ==========================================
// FUNCIONES PRINCIPALES (mantenidas del código original)
// ==========================================

function getColumnClass(number) {
    if (number <= 15) return 'B';
    if (number <= 30) return 'I';
    if (number <= 45) return 'N';
    if (number <= 60) return 'G';
    return 'O';
}

function isAutoMarkEnabled() {
    return window.autoMarkEnabled === true;
}

function disableManualClickForNumber(number) {
    if (!isAutoMarkEnabled()) {
        return;
    }

    $(".number-" + number).each(function() {
        this.removeAttribute('onclick');
    });
}

function applyAutoMarkPreferenceFromServer(autodial) {
    if (typeof autodial === 'undefined' || autodial === null) {
        return;
    }

    window.autoMarkEnabled = parseInt(autodial, 10) === 1;

    const btn = $('#btn-auto-mark');
    if (btn.length) {
        if (window.autoMarkEnabled) {
            btn.html('<i class="fa-duotone fa-solid fa-binary-circle-check"></i>');
        } else {
            btn.html('<i class="fa-duotone fa-solid fa-binary-slash"></i>');
        }
    }
}

function rememberDrawnNumber(number) {
    const parsed = parseInt(number, 10);
    if (!parsed) {
        return;
    }

    if (!Array.isArray(window.drawnNumbers)) {
        window.drawnNumbers = [];
    }

    if (!window.drawnNumbers.includes(parsed)) {
        window.drawnNumbers.push(parsed);
    }

    if (!numbersgenerated.includes(parsed)) {
        numbersgenerated.push(parsed);
    }
}

function parseBallNumber(value) {
    const parsed = parseInt(value, 10);
    return Number.isNaN(parsed) ? null : parsed;
}

function getCurrentMainBallNumber() {
    const el = document.querySelector('#last-number span');
    if (!el) {
        return null;
    }

    return parseBallNumber(el.textContent);
}

function updateMainBall(newNumber) {
    const parsed = parseBallNumber(newNumber);
    if (!parsed) {
        return;
    }

    const lastNumberEl = $('#last-number');
    if (!lastNumberEl.length) {
        return;
    }

    lastNumberEl.html(`<small style="position: absolute; top: -13px; font-size: 1.2rem; z-index: 1;">${getColumnClass(parsed)}</small><span>${parsed}</span>`)
        .removeClass()
        .addClass(`bingo-ball ${getColumnClass(parsed)} size-100`);
}

function getHistoryBallSizeClass() {
    return document.querySelector('.top-section.live') ? 'size-40' : 'size-40';
}

function renderBallHistory() {
    const container = $("#last-five-numbers");
    if (!container.length) {
        return;
    }

    const ordered = (window.drawnNumbers || [])
        .map(parseBallNumber)
        .filter(Boolean);

    if (!ordered.length) {
        container.empty();
        return;
    }

    const history = ordered.length > 1
        ? ordered.slice(Math.max(0, ordered.length - 5), -1)
        : [];

    container.empty();
    history.slice(-4).forEach(function(num) {
        container.append(`<div class="bingo-ball ${getColumnClass(num)} ${getHistoryBallSizeClass()}"><span>${num}</span></div>`);
    });
}

function reconcileBallDisplay(orderedNumbers) {
    const ordered = (orderedNumbers || window.drawnNumbers || [])
        .map(parseBallNumber)
        .filter(Boolean);

    if (!ordered.length) {
        return;
    }

    window.drawnNumbers = ordered.slice();
    lastNumbers = ordered.slice(-5);
    updateMainBall(ordered[ordered.length - 1]);
    renderBallHistory();
    ordered.forEach(markBoardNumber);
}

function registerDrawnNumber(newNumber) {
    const parsed = parseBallNumber(newNumber);
    if (!parsed || numbersgenerated.includes(parsed)) {
        return false;
    }

    numbersgenerated.push(parsed);
    rememberDrawnNumber(parsed);

    if (isAutoMarkEnabled()) {
        disableManualClickForNumber(parsed);
    }

    return true;
}

function seedAutoMarkedNumbers() {
    autoMarkedNumbers.clear();
    $('.bingo-carton-number.marked').each(function() {
        const match = (this.className || '').match(/\bnumber-(\d+)\b/);
        if (match) {
            autoMarkedNumbers.add(parseInt(match[1], 10));
        }
    });
}

function flushPendingMark() {
    if (pendingMarkNumber === null) {
        return false;
    }

    const num = pendingMarkNumber;
    pendingMarkNumber = null;
    pendingMarkSequence = 0;
    applyMarksForNumber(num);

    if (isAutoMarkEnabled()) {
        scheduleAutoSingCheck();
    }

    return true;
}

function clearBallRevealTimers(flushPending) {
    if (flushPending !== false) {
        flushPendingMark();
    }

    if (ballRevealTimer) {
        clearTimeout(ballRevealTimer);
        ballRevealTimer = null;
    }

    if (ballRevealAfterTimer) {
        clearTimeout(ballRevealAfterTimer);
        ballRevealAfterTimer = null;
    }
}

function hasGameStarted() {
    if (window.gameStartedLive === true) {
        return true;
    }

    if (numbersgenerated.length > 0) {
        return true;
    }

    if (Array.isArray(window.drawnNumbers) && window.drawnNumbers.length > 0) {
        return true;
    }

    if ((window.totalNumbersGenerated || 0) > 0) {
        return true;
    }

    if (typeof gameDate === 'undefined') {
        return true;
    }

    return new Date() >= new Date(gameDate);
}

function markGameAsStartedFromServer(totalNumbersGenerated) {
    const drawn = parseInt(totalNumbersGenerated, 10) || 0;
    const hasDrawn = drawn > 0
        || (Array.isArray(window.drawnNumbers) && window.drawnNumbers.length > 0)
        || numbersgenerated.length > 0;

    if (!hasDrawn) {
        return;
    }

    window.gameStartedLive = true;

    if (intervalNextGame) {
        clearInterval(intervalNextGame);
        intervalNextGame = null;
    }

    const nextGameSpan = document.querySelector('.next-game');
    if (nextGameSpan && !window.gameIsFinished && !isGameFinishedShown) {
        if (winners.length > 0) {
            startWinnerSlider();
        } else {
            nextGameSpan.textContent = '¡EL JUEGO HA INICIADO!';
        }
    }
}

function applyMarksForNumber(newNumber) {
    const parsed = parseBallNumber(newNumber);
    if (!parsed) {
        return;
    }

    if (isAutoMarkEnabled()) {
        disableManualClickForNumber(parsed);
    }

    markBoardNumber(parsed);

    if (isAutoMarkEnabled()) {
        markCartonNumberLocally(parsed, false);

        if (!autoMarkedNumbers.has(parsed)) {
            autoMarkedNumbers.add(parsed);
            dialNumber(parsed);
        } else {
            scheduleAutoSingCheck();
        }
    }
}

function scheduleLatestBallMarks(latestNumber, options) {
    const parsed = parseBallNumber(latestNumber);
    if (!parsed) {
        return;
    }

    const opts = options || {};
    const shouldDelayMarks = opts.animate !== false && !bingoInProgress;

    const runMarks = function() {
        pendingMarkNumber = null;
        pendingMarkSequence = 0;
        applyMarksForNumber(parsed);

        if (isAutoMarkEnabled()) {
            scheduleAutoSingCheck();
        }
    };

    flushPendingMark();
    clearBallRevealTimers(false);

    if (!shouldDelayMarks) {
        runMarks();
        return;
    }

    if (typeof narrationPlaying !== 'undefined' && narrationPlaying) {
        audioManager.play(audioPath + parsed + '.mp3');
    }

    const sequence = ++ballRevealSequence;
    pendingMarkNumber = parsed;
    pendingMarkSequence = sequence;
    const ballDelay = getBallDisplayDelay();

    ballRevealTimer = setTimeout(function() {
        if (pendingMarkSequence !== sequence || pendingMarkNumber !== parsed) {
            return;
        }

        runMarks();

        ballRevealAfterTimer = setTimeout(function() {
            const lastNumberEl = $('#last-number');
            if (lastNumberEl.length) {
                lastNumberEl.removeClass('move-number');
            }
        }, 300);
    }, ballDelay);
}

function buildOrderedDrawnNumbers(newNumber, drawnNumbers) {
    if (Array.isArray(drawnNumbers) && drawnNumbers.length) {
        return drawnNumbers.map(parseBallNumber).filter(Boolean);
    }

    const ordered = numbersgenerated.slice();
    const parsed = parseBallNumber(newNumber);
    if (parsed && !ordered.includes(parsed)) {
        ordered.push(parsed);
    }

    return ordered;
}

function syncDrawnNumbersFromServer(drawnNumbers, totalNumbersGenerated, options) {
    const opts = options || {};
    const ordered = (drawnNumbers || [])
        .map(parseBallNumber)
        .filter(Boolean);

    if (!ordered.length) {
        return;
    }

    const serverTotal = ordered.length;
    const counterTotal = totalNumbersGenerated !== undefined
        ? parseInt(totalNumbersGenerated, 10)
        : serverTotal;
    const ballsCount = Number.isFinite(counterTotal) && counterTotal > 0
        ? Math.max(counterTotal, serverTotal)
        : serverTotal;

    const previous = numbersgenerated.slice();
    const missing = ordered.filter(function(num) {
        return !previous.includes(num);
    });

    numbersgenerated = ordered.slice();
    window.drawnNumbers = ordered.slice();

    updateBallsCounter(ballsCount);
    reconcileBallDisplay(ordered);
    markGameAsStartedFromServer(ballsCount);

    flushPendingMark();
    clearBallRevealTimers(false);

    if (isAutoMarkEnabled()) {
        syncAutoMarkedNumbers(ordered, { animate: false, persist: false });
    }

    if (missing.length && typeof narrationPlaying !== 'undefined' && narrationPlaying) {
        audioManager.play(audioPath + missing[missing.length - 1] + '.mp3');
    }

    if (opts.animate !== false && missing.length === 1 && !bingoInProgress) {
        const lastNumberEl = $('#last-number');
        if (lastNumberEl.length) {
            lastNumberEl.addClass('move-number');
            setTimeout(function() {
                lastNumberEl.removeClass('move-number');
            }, getBallDisplayDelay());
        }
    }

    if (isAutoMarkEnabled()) {
        scheduleAutoSingCheck(missing.length ? 300 : 200);
    }
}

function getBallDisplayDelay() {
    const parsed = parseInt(window.timeBallGet, 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : 1500;
}

function applyMarksForDrawnNumber(number) {
    markBoardNumber(number);

    if (isAutoMarkEnabled()) {
        dialNumber(number);
    }
}

function markBoardNumber(number) {
    const boardNumber = $("#board-number-" + number);
    if (boardNumber.length) {
        boardNumber.addClass(getColumnClass(number));
    }
}

function markCartonNumberLocally(number, animate) {
    const elementsNumber = $(".number-" + number);
    if (!elementsNumber.length) {
        return;
    }

    elementsNumber.each(function() {
        const elementNumber = $(this);

        if (elementNumber.hasClass('marked')) {
            return;
        }

        if (animate) {
            const originalContent = elementNumber.text();
            elementNumber.text('⭐️').addClass('explosive-effect');

            setTimeout(function() {
                elementNumber.text(originalContent);
                elementNumber.removeClass('explosive-effect');
                elementNumber.addClass('marked');
            }, 300);
        } else {
            elementNumber.addClass('marked');
        }

        if (isAutoMarkEnabled()) {
            elementNumber.removeAttr('onclick');
        }
    });
}

function syncAutoMarkedNumbers(drawnNumbers, options) {
    if (!isAutoMarkEnabled()) {
        return;
    }

    const opts = options || {};
    const numbers = Array.isArray(drawnNumbers) ? drawnNumbers : (window.drawnNumbers || []);
    const animate = opts.animate === true;

    numbers.forEach(function(number) {
        const parsed = parseInt(number, 10);
        if (!parsed) {
            return;
        }

        rememberDrawnNumber(parsed);
        markBoardNumber(parsed);

        const elementsNumber = $(".number-" + parsed);
        if (!elementsNumber.length) {
            return;
        }

        const unmarked = elementsNumber.filter(':not(.marked)');
        if (!unmarked.length) {
            autoMarkedNumbers.add(parsed);
            return;
        }

        if (opts.persist === false) {
            markCartonNumberLocally(parsed, animate);
            autoMarkedNumbers.add(parsed);
            return;
        }

        if (!autoMarkedNumbers.has(parsed)) {
            autoMarkedNumbers.add(parsed);
            dialNumber(parsed);
        }
    });

    if (isAutoMarkEnabled()) {
        scheduleAutoSingCheck(animate ? 500 : 200);
    }
}

function startWinnerSlider() {
    if (winners.length === 0) return;

    clearTimeout(winnerSliderTimeout);

    function showNext() {
        const current = winners[winnerIndex];
        const nextGameSpan = document.querySelector('.next-game');
        if (nextGameSpan) {
            nextGameSpan.textContent = `GANADOR: ${current.player} - ${current.modality}`;
        }
        winnerIndex = (winnerIndex + 1) % winners.length;
        winnerSliderTimeout = setTimeout(showNext, CONFIG.WINNER_SLIDER_INTERVAL);
    }

    showNext();
}

function showCountdown(data, callback) {
    const numberHe = $id('countdown');
    const container = $id('countdown-container');
    const textHe = $id('text-countdown');
    
    if (!numberHe || !container || !textHe) return;
    
    let countdown = 5;

    container.style.display = 'block';
    numberHe.textContent = __['bingo!'] || 'BINGO!';
    textHe.innerHTML = `${data.modality}<br />${data.player}`;
    numberHe.style.color = 'white';

    registerWinner(data.player, data.modality);
    startWinnerSlider();

    // Reproducir sonido de victoria
    audioManager.play(audioPath + 'winner.mp3');

    // Actualizar cartón ganador
    const cartn = $id(`modality-${data.modalityId}`);
    if (cartn) {
        cartn.classList.add('cartn-sing');
        cartn.querySelectorAll('.card-number.modality-sing').forEach(el => {
            el.classList.add('sing');
            el.innerText = '⭐️';
        });
    }

    // Secuencia de countdown
    setTimeout(() => {
        if (data.image) {
            numberHe.style.backgroundImage = `url(${data.image})`;
            numberHe.style.backgroundSize = 'cover';
            numberHe.style.backgroundPosition = 'center';
            numberHe.style.color = 'transparent';
        }

        setTimeout(() => {
            numberHe.style.backgroundImage = '';
            numberHe.style.background = 'linear-gradient(145deg, #6236ff, #8767fa)';
            numberHe.style.color = 'white';
            numberHe.textContent = countdown;

            const interval = setInterval(() => {
                numberHe.textContent = --countdown;
                if (countdown === 0) {
                    clearInterval(interval);
                    container.style.display = 'none';
                    
                    // Procesar el siguiente bingo simultáneo si existe
                    if (simultaneousBingos.length > 0) {
                        const nextBingo = simultaneousBingos.shift();
                        showCountdown(nextBingo, callback);
                    } else {
                        bingoInProgress = false;
                        if (callback) callback();
                    }
                }
            }, 1000);
        }, 3000);
    }, 2000);

    AppcreateConfetti();
}

function showOtherPlayerBingoNotice(data, callback) {
    if (!data) {
        return;
    }

    window.gameHasWinner = true;
    intervalManager.clear('lastNumber');

    if (Array.isArray(data.winners)) {
        mergeWinnersFromServer(data.winners);
    }

    if (data.player && data.modality) {
        registerWinner(data.player, data.modality);
    }

    const nextGameSpan = document.querySelector('.next-game');
    if (nextGameSpan) {
        nextGameSpan.textContent = `GANADOR: ${data.player} - ${data.modality}`;
    }

    startWinnerSlider();

    if (data.gameCompleted || window.gameIsFinished) {
        setTimeout(showGameFinalized, 1200);
        return;
    }

    setTimeout(function() {
        if (typeof callback === 'function') {
            callback();
        } else {
            startAutomaticLast();
        }
    }, 3500);
}

function updateBallsCounter(totalNumbersGenerated) {
    const totalBalls = 75;
    const drawn = parseInt(totalNumbersGenerated, 10) || 0;
    window.totalNumbersGenerated = drawn;
    const remaining = totalBalls - drawn;
    
    const counter = $('#balls-counter');
    if (counter.length) {
        counter.text(`${drawn} - ${remaining}`);
    }

    const nextGameSpan = document.querySelector('.next-game');
    if (nextGameSpan && drawn === 1) {
        if (intervalNextGame) {
            clearInterval(intervalNextGame);
            intervalNextGame = null;
        }
        nextGameSpan.textContent = '¡EL JUEGO HA INICIADO!';
    }
}

function handleNewNumber(newNumber, totalNumbersGenerated, drawnNumbers) {
    const ordered = buildOrderedDrawnNumbers(newNumber, drawnNumbers);
    syncDrawnNumbersFromServer(ordered, totalNumbersGenerated, { animate: !bingoInProgress });
}

function processNumberGetResponse(data) {
    if (!data) {
        return;
    }

    applyNumberGetMeta(data);
    applyAutoMarkPreferenceFromServer(data.autodial);

    if (Array.isArray(data.drawnNumbers) && data.drawnNumbers.length) {
        syncDrawnNumbersFromServer(data.drawnNumbers, data.totalNumbersGenerated, { animate: false });
    } else if (data.number) {
        handleNewNumber(data.number, data.totalNumbersGenerated, data.drawnNumbers);
    }

    if (data.status === 'pause') {
        if (Array.isArray(data.winners)) {
            mergeWinnersFromServer(data.winners);
        }

        if (data.player && data.modality) {
            if (isOwnBingoEvent(data)) {
                if (!bingoInProgress) {
                    bingoInProgress = true;
                    intervalManager.clear('lastNumber');
                    showCountdown({
                        player: data.player,
                        modality: data.modality,
                        modalityId: data.modalityId,
                        image: data.image
                    }, function() {
                        if (data.gameCompleted) {
                            showGameFinalized();
                            return;
                        }
                        startAutomaticLast();
                    });
                }
            } else if (!isGameFinishedShown) {
                showOtherPlayerBingoNotice(data);
            }
        }
    } else if (data.status === 'completed') {
        if (Array.isArray(data.winners)) {
            mergeWinnersFromServer(data.winners);
        }

        if (data.player && data.modality) {
            registerWinner(data.player, data.modality);
        }

        window.gameHasWinner = true;
        setTimeout(showGameFinalized, typeof timeBallGet !== 'undefined' ? timeBallGet : 1000);
    }
}

function lastNumberGet() {
    if (bingoInProgress || window.gameIsFinished || isGameFinishedShown) {
        return;
    }

    $.get(site_url + 'playings/numberGet')
        .done((data) => {
            if (!data || data.status === 'error') {
                return;
            }

            processNumberGetResponse(data);
        })
        .fail((xhr, status, error) => {
            console.warn('Failed to get last number:', error);
        });
}

function startAutomaticLast() {
    intervalManager.clear('lastNumber');
    if (typeof timeBallLast !== 'undefined') {
        lastNumberGet();
        intervalManager.set('lastNumber', lastNumberGet, timeBallLast);
    }
}

function stopAutomaticLast() {
    intervalManager.clear('lastNumber');
}

function showGameFinalized() {
    if (isGameFinishedShown) {
        return;
    }

    fetchWinnersBeforeFinalize(function() {
        if (isGameFinishedShown) {
            return;
        }

        isGameFinishedShown = true;
        window.gameIsFinished = true;
        window.allowGameUnload = true;
        bingoInProgress = false;

        const countdownContainer = $id('countdown-container');
        if (countdownContainer) {
            countdownContainer.style.display = 'none';
        }

        const nextGameSpan = document.querySelector('.next-game');
        if (nextGameSpan) {
            if (winners.length > 0) {
                startWinnerSlider();
            } else {
                nextGameSpan.textContent = (__['game finished!'] || 'JUEGO FINALIZADO').toUpperCase();
            }
        }

        const container = $id('game-finalized');
        const text = $id('finalized');

        if (container && text) {
            container.style.display = 'block';
            text.innerHTML = buildWinnersFinalText();

            setTimeout(function() {
                if (typeof awardsGet === 'function') {
                    awardsGet();
                }
                container.style.display = 'none';
            }, 5000);
        }

        stopAutomaticLast();
        stopUpdateUserCount();
        stopUpdateGameAccumulated();
        messagePoller.stop();

        const controlsDiv = $id('controls');
        if (controlsDiv) {
            controlsDiv.remove();
        }
    });
}

// Contador de usuarios optimizado
const updateUserCount = throttle(() => {
    $.get(site_url + 'boards/playersGetCount')
        .done((data) => {
            const countEl = $('.count_notifications');
            if (data.status === 'success') {
                if (data.userCount && data.userCount > 0) {
                    countEl.text(data.userCount).show();
                } else {
                    countEl.hide();
                }
            } else {
                if (data.userCount && data.userCount > 0) {
                    countEl.text(data.userCount).show();
                } else {
                    countEl.hide();
                }

                stopUpdateUserCount();
            }
        })
        .fail(() => {
            console.warn('Failed to update user count');
        });
}, 1000);

function stopUpdateUserCount() {
    intervalManager.clear('userCount');
}

// Contador de acumulado optimizado
const updateGameAccumulated = throttle(() => {
    $.get(site_url + 'games/gameGetAccumulated')
        .done((data) => {
            const accumulatedEl = $('#accumulated-counter');

            if (data.status === 'success') {
                accumulatedEl.text(currency + ' ' + data.gameAccumulated);

                if (data.modalities && data.modalities.length > 0) {
                    data.modalities.forEach(modality => {
                        const modalityEl = $('#modality-amount-' + modality.id);
                        if (modalityEl.length > 0) {
                            modalityEl.text(currency + ' ' + modality.amount);
                        }
                    });
                }
            } else if (data.status === 'completed') {
                accumulatedEl.text(currency + ' ' + data.gameAccumulated);

                if (data.modalities && data.modalities.length > 0) {
                    data.modalities.forEach(modality => {
                        const modalityEl = $('#modality-amount-' + modality.id);
                        if (modalityEl.length > 0) {
                            modalityEl.text(currency + ' ' + modality.amount);
                        }
                    });
                }

                stopUpdateGameAccumulated();
                showGameFinalized();
            } else {
                accumulatedEl.text(currency + ' ' + data.gameAccumulated);
                stopUpdateGameAccumulated();
            }
        })
        .fail(() => {
            console.warn('Failed to update user count');
        });
}, 1000);

function stopUpdateGameAccumulated() {
    intervalManager.clear('gameAccumulated');
}

// Función optimizada para marcar números
function dialNumber(number) {
    const elementsNumber = $(".number-" + number);

    if (!elementsNumber.length) {
        console.warn("No se encontró el número en el DOM:", number);
        return;
    }

    $.ajax({
        url: site_url + 'playings/dialNumber',
        method: 'POST',
        data: { number: number },
        success: function(data) {
            if (data.status === 'success') {
                elementsNumber.each(function() {
                    const elementNumber = $(this);

                    if (elementNumber.hasClass('marked')) {
                        return;
                    }

                    if (isAutoMarkEnabled()) {
                        elementNumber.addClass('marked');
                        elementNumber.removeAttr('onclick');
                        return;
                    }

                    const originalContent = elementNumber.text();
                    elementNumber.text('⭐️').addClass('explosive-effect');

                    setTimeout(function() {
                        elementNumber.text(originalContent);
                        elementNumber.removeClass('explosive-effect');
                        elementNumber.addClass('marked');
                    }, 1000);
                });

                if (isAutoMarkEnabled()) {
                    scheduleAutoSingCheck();
                }
            } else {
                autoMarkedNumbers.delete(number);
                console.warn("Respuesta no exitosa:", data.message || data);
            }
        },
        error: function(xhr, status, error) {
            autoMarkedNumbers.delete(number);
            console.error("Error en AJAX al marcar número:", number, error);
        }
    });
}

// Función optimizada para marcar números
function autoDialNumber(number) {
    const elementsNumber = $(".number-" + number);
    elementsNumber.each(function() {
        const elementNumber = $(this);

        if (elementNumber.hasClass('marked')) return;

        const originalContent = elementNumber.text();
        elementNumber.text('⭐️').addClass('explosive-effect');

        setTimeout(function() {
            elementNumber.text(originalContent); 
            elementNumber.removeClass('explosive-effect');
            elementNumber.addClass('marked'); 
        }, 1000);
    });

    const numberEl = $("#board-number-" + number);
    if (numberEl.length) {
        numberEl.addClass(getColumnClass(number));
    }

    if (!elementsNumber.length) {
        console.warn("No se encontró el número en el DOM:", number);
        return;
    }
}


function singBingo() {
    // Si ya hay un bingo en progreso, no permitir cantar otro
    if (bingoInProgress) {
        // Mostrar mensaje al usuario
        const messageElement = messagePool.get();
        messageElement.className = 'message system-message';
        messageElement.innerHTML = '<strong>Sistema:</strong> ' + (__['please wait until the current bingo is verified'] || 'Por favor espera mientras se verifica el bingo actual');
        messageElement.style.display = 'block';
        messageElement.style.opacity = '1';
        
        const messagesContainer = document.querySelector('.messages-container');
        if (messagesContainer) {
            messagesContainer.appendChild(messageElement);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Programar eliminación del mensaje
        setTimeout(() => {
            messageElement.style.opacity = '0';
            setTimeout(() => {
                messagePool.release(messageElement);
            }, CONFIG.FADE_OUT_TIME);
        }, CONFIG.MESSAGE_LIFETIME);
        
        return;
    }
    
    const bingoButton = document.querySelector('.btn-bingooo');
    if (bingoButton) {
        bingoButton.classList.remove('animate-click');
        void bingoButton.offsetWidth;
        bingoButton.classList.add('animate-click');
    }

    $.ajax({
        url: site_url + 'playings/singBingo',
        method: 'POST',
        success: function(data) {
            if (data.status === 'success') {
                const lastBall = getLastBoardNumber();
                if (lastBall) {
                    lastAutoSingBall = lastBall;
                }
                handleBingoSuccess(data, startAutomaticLast);
            } else if (data && data.gameHasWinner) {
                window.gameHasWinner = true;
            }
        },
        error: function(xhr, status, error) {
            console.error("Error al cantar bingo:", error);
        }
    });
}

// Función optimizada para cantar bingo
// NOTE: La implementación válida de singBingo ya está definida arriba (con control de bingoInProgress).
// Eliminamos la segunda definición duplicada para evitar comportamiento inesperado.

// Funciones de audio
function RemoveVolume() {
    $.ajax({
        url: site_url + 'playings/volumeSubmit',
        method: 'POST',
        success: function(data) {
            if (data.status === 'success') {
                console.log("Sound disabled successfully");
            }
        },
        error: function() {
            console.warn("Error disabling sound");
        }
    });
}

function RemoveMicrophone() {
    $.ajax({
        url: site_url + 'playings/microphoneSubmit',
        method: 'POST',
        success: function(data) {
            if (data.status === 'success') {
                console.log("Narrator disabled successfully");
            }
        },
        error: function() {
            console.warn("Error disabling narrator");
        }
    });
}

function RemoveCheck() {
    $.ajax({
        url: site_url + 'playings/checkSubmit',
        method: 'POST',
        success: function(data) {
            if (data.status === 'success') {
                window.autoMarkEnabled = parseInt(data.autodial, 10) === 1;

                const btn = $('#btn-auto-mark');
                if (btn.length) {
                    if (window.autoMarkEnabled) {
                        btn.html('<i class="fa-duotone fa-solid fa-binary-circle-check"></i>');
                    } else {
                        btn.html('<i class="fa-duotone fa-solid fa-binary-slash"></i>');
                    }
                }

                if (window.autoMarkEnabled) {
                    if (Array.isArray(data.drawnNumbers)) {
                        window.drawnNumbers = data.drawnNumbers;
                    }
                    syncAutoMarkedNumbers(window.drawnNumbers, { animate: true, persist: false });
                    seedAutoMarkedNumbers();
                    $(".bingo-carton-number[id^='number-']").each(function() {
                        this.removeAttribute('onclick');
                    });
                } else {
                    autoMarkedNumbers.clear();
                    pendingMarkNumber = null;
                    pendingMarkSequence = 0;
                }
            } else {
                console.log("error sending request");
            }
        },
        error: function(error) {
            console.log("error in the request");
        }
    });
}

// ==========================================
// CONFIGURACIÓN DE EVENTOS
// ==========================================
function setupEvents() {
    // Eventos de mensajes (un solo handler; evitar duplicar con onclick/onkeypress en HTML)
    const messageButton = $('#message-button, #btn-send-message-new');
    messageButton.off('click.chatSend').on('click.chatSend', sendMessageText);

    const messageInput = $('#message-send-new');
    messageInput.off('keydown.chatSend').on('keydown.chatSend', (e) => {
        if (e.key === 'Enter' || e.which === 13) {
            e.preventDefault();
            sendMessageText();
        }
    });

    // Eventos para emojis (si tienes botones de emoji)
    $('.emoji-button').on('click', function() {
        const emoji = $(this).data('emoji') || $(this).text();
        sendEmoji(emoji);
    });

    // Control de micrófono
    $('.btn-microphone').on('click', function() {
        if (typeof narrationPlaying !== 'undefined') {
            narrationPlaying = !narrationPlaying;
            $(this).html(narrationPlaying ? 
                '<i class="fa-duotone fa-solid fa-microphone"></i>' : 
                '<i class="fa-duotone fa-solid fa-microphone-slash"></i>'
            );
        }
    });

    // Click en números del cartón (solo en modo manual)
    $(".bingo-carton-number").on('click', function() {
        if (!isAutoMarkEnabled()) {
            const number = $(this).data('number') || parseInt($(this).attr('id')?.replace('number-', ''), 10);
            if (number) {
                dialNumber(number);
            }
        }
    });

    // Gestión de modales
    $('.modal').on("hidden.bs.modal", function(e) {
        if ($('.modal:visible').length) {
            $('.modal-backdrop').first().css('z-index', parseInt($('.modal:visible').last().css('z-index')) - 10);
            $('body').addClass('modal-open');
        }
    }).on("show.bs.modal", function(e) {
        if ($('.modal:visible').length) {
            $('.modal-backdrop.in').first().css('z-index', parseInt($('.modal:visible').last().css('z-index')) + 10);
            $(this).css('z-index', parseInt($('.modal-backdrop.in').first().css('z-index')) + 10);
        }
    });

    function setModalitiesPanelOpen(open) {
        const panel = $id("playing-modalities-panel");
        const toggleBtn = $id("toggle-modalities-btn");
        if (!panel) {
            return;
        }
        panel.style.display = open ? "flex" : "none";
        panel.classList.toggle("is-open", open);
        panel.setAttribute("aria-hidden", open ? "false" : "true");
        document.body.classList.toggle("modalities-panel-open", open);
        if (toggleBtn) {
            toggleBtn.setAttribute("aria-expanded", open ? "true" : "false");
        }
        if (open && isChatPanelOpen()) {
            setChatPanelOpen(false);
        }
    }

    function isModalitiesPanelOpen() {
        const panel = $id("playing-modalities-panel");
        return panel && panel.classList.contains("is-open");
    }

    function setChatPanelOpen(open) {
        const messageContainer = $id("message-display-container");
        const toggleBtn = $id("toggle-messages-btn");
        if (!messageContainer) {
            return;
        }
        messageContainer.style.display = open ? "flex" : "none";
        messageContainer.classList.toggle("is-open", open);
        messageContainer.setAttribute("aria-hidden", open ? "false" : "true");
        document.body.classList.toggle("chat-panel-open", open);
        if (toggleBtn) {
            toggleBtn.setAttribute("aria-expanded", open ? "true" : "false");
        }
        if (open && isModalitiesPanelOpen()) {
            setModalitiesPanelOpen(false);
        }
    }

    function isChatPanelOpen() {
        const messageContainer = $id("message-display-container");
        return messageContainer && messageContainer.style.display === "flex";
    }

    function initModalitiesPanel() {
        const panel = $id("playing-modalities-panel");
        if (!panel) {
            return;
        }
        setModalitiesPanelOpen(false);
    }

    initModalitiesPanel();

    const modalitiesToggleBtn = $id("toggle-modalities-btn");
    if (modalitiesToggleBtn) {
        modalitiesToggleBtn.addEventListener("click", function(event) {
            setModalitiesPanelOpen(!isModalitiesPanelOpen());
            event.stopPropagation();
        });
    }

    const closeModalitiesBtn = $id("modalities-panel-close");
    if (closeModalitiesBtn) {
        closeModalitiesBtn.addEventListener("click", function(event) {
            setModalitiesPanelOpen(false);
            event.stopPropagation();
        });
    }

    const toggleBtn = $id("toggle-messages-btn");
    if (toggleBtn) {
        toggleBtn.addEventListener("click", function(event) {
            setChatPanelOpen(!isChatPanelOpen());
            event.stopPropagation();
        });
    }

    const closeChatBtn = $id("message-display-close");
    if (closeChatBtn) {
        closeChatBtn.addEventListener("click", function(event) {
            setChatPanelOpen(false);
            event.stopPropagation();
        });
    }

    document.addEventListener("click", function(event) {
        const messageContainer = $id("message-display-container");
        const toggleButton = $id("toggle-messages-btn");
        const closeButton = $id("message-display-close");
        const modalitiesPanel = $id("playing-modalities-panel");
        const modalitiesToggle = $id("toggle-modalities-btn");
        const closeModalities = $id("modalities-panel-close");

        if (messageContainer && toggleButton &&
            isChatPanelOpen() &&
            !messageContainer.contains(event.target) &&
            !toggleButton.contains(event.target) &&
            !(closeButton && closeButton.contains(event.target))) {
            setChatPanelOpen(false);
        }

        if (modalitiesPanel && modalitiesToggle &&
            isModalitiesPanelOpen() &&
            !modalitiesPanel.contains(event.target) &&
            !modalitiesToggle.contains(event.target) &&
            !(closeModalities && closeModalities.contains(event.target))) {
            setModalitiesPanelOpen(false);
        }
    });

    // Eventos para auto-scroll del chat
    const messageDisplay = $id("message-display");
    if (messageDisplay) {
        // Detectar cuando el usuario hace scroll manual
        let userScrolled = false;
        messageDisplay.addEventListener('scroll', () => {
            const { scrollTop, scrollHeight, clientHeight } = messageDisplay;
            userScrolled = scrollTop < scrollHeight - clientHeight - 50; // 50px de tolerancia
        });

        // Observer para nuevos mensajes
        const observer = new MutationObserver(() => {
            if (!userScrolled) {
                scrollToBottom();
            }
        });

        observer.observe(messageDisplay, { childList: true });
    }
}

// ==========================================
// CONFIGURACIÓN DE MÁSCARAS Y SCROLL
// ==========================================
function setupScrollMask() {
    const container = document.querySelector(".cartons-section");
    const cartons = document.querySelectorAll('.bingo-carton');

    function isMobile() {
        return window.innerWidth <= 700;
    }

    function isTablet() {
        return window.innerWidth >= 701 && window.innerWidth <= 1024;
    }

    function isDesktop() {
        return window.innerWidth >= 1025;
    }

    function shouldApplyMask() {
        const cartonCount = cartons.length;
        if (isMobile() && cartonCount > 4) return true;
        if (isTablet() && cartonCount > 6) return true;
        if (isDesktop() && cartonCount > 5) return true;
        return false;
    }

    const updateMask = debounce(() => {
        const scrollTop = container.scrollTop;
        const scrollHeight = container.scrollHeight;
        const clientHeight = container.clientHeight;

        if (!shouldApplyMask()) {
            container.style.maskImage = "none";
            container.style.webkitMaskImage = "none";
            return;
        }

        if (scrollHeight <= clientHeight) {
            container.style.maskImage = "none";
            container.style.webkitMaskImage = "none";
            return;
        }

        let maskValue;
        if (scrollTop === 0) {
            maskValue = "linear-gradient(to bottom, rgba(0, 0, 0, 1) 0%, rgba(0, 0, 0, 1) 80%, rgba(0, 0, 0, 0) 100%)";
        } else if (scrollTop + clientHeight >= scrollHeight) {
            maskValue = "linear-gradient(to top, rgba(0, 0, 0, 1) 0%, rgba(0, 0, 0, 1) 80%, rgba(0, 0, 0, 0) 100%)";
        } else {
            maskValue = "linear-gradient(to bottom, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 1) 15%, rgba(0, 0, 0, 1) 80%, rgba(0, 0, 0, 0) 100%)";
        }

        container.style.maskImage = maskValue;
        container.style.webkitMaskImage = maskValue;
    }, 50);

    if (cartons.length > 4) { 
        container.addEventListener("scroll", updateMask);
        window.addEventListener("resize", updateMask);
        updateMask(); 
    }
}

// ==========================================
// CONFIGURACIÓN DE COUNTDOWN Y GANADORES
// ==========================================
function setupGameCountdown() {
    const nextGameSpan = document.querySelector('.next-game');
    if (!nextGameSpan || typeof gameDate === 'undefined') return;

    const targetDate = new Date(gameDate);
    let winnerIndex = 0;

    function updateCountdown() {
        const now = new Date();
        const timeDiff = targetDate - now;

        if (hasGameStarted()) {
            if (winners.length > 0) {
                startWinnerSlider();
            } else {
                nextGameSpan.textContent = '¡EL JUEGO HA INICIADO!';
            }
            return;
        }

        if (timeDiff <= 0) {
            clearInterval(intervalNextGame);

            if (window.gameIsFinished || isGameFinishedShown) {
                if (winners.length > 0) {
                    startWinnerSlider();
                } else {
                    nextGameSpan.textContent = (__['game finished!'] || 'JUEGO FINALIZADO').toUpperCase();
                }
            } else {
                nextGameSpan.textContent = 'ESPERE QUE INICIE LA PARTIDA...';
            }
            return;
        }

        const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((timeDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((timeDiff % (1000 * 60)) / 1000);

        let text = '';
        if (days > 0) {
            text = `EL JUEGO INICIA EN: ${days} DÍA${days > 1 ? 'S' : ''} ${hours} HORA${hours > 1 ? 'S' : ''} - ${minutes}:${seconds < 10 ? '0' : ''}${seconds} MIN`;
        } else if (hours > 0) {
            text = `EL JUEGO INICIA EN: ${hours} HORA${hours > 1 ? 'S' : ''} - ${minutes}:${seconds < 10 ? '0' : ''}${seconds} MIN`;
        } else {
            if (minutes === 0) {
                const sec = Math.max(0, seconds);
                text = `EL JUEGO INICIA EN: ${sec} SEGUNDO${sec === 1 ? '' : 'S'}`;
            } else {
                text = `EL JUEGO INICIA EN: ${minutes}:${seconds < 10 ? '0' : ''}${seconds} MINUTO${minutes === 1 ? '' : 'S'}`;
            }
        }

        nextGameSpan.textContent = text;
    }

    const now = new Date();
    if (now < targetDate) {
        updateCountdown();
        intervalNextGame = setInterval(updateCountdown, 1000);
    } else {
        if (window.gameIsFinished || isGameFinishedShown) {
            if (winners.length > 0) {
                startWinnerSlider();
            } else {
                nextGameSpan.textContent = (__['game finished!'] || 'JUEGO FINALIZADO').toUpperCase();
            }
        } else if (hasGameStarted()) {
            if (winners.length > 0) {
                startWinnerSlider();
            } else {
                nextGameSpan.textContent = '¡EL JUEGO HA INICIADO!';
            }
        } else {
            nextGameSpan.textContent = 'ESPERE QUE INICIE LA PARTIDA...';
        }
    }
}

// ==========================================
// GESTIÓN DE RECURSOS Y LIMPIEZA
// ==========================================
class ResourceManager {
    constructor() {
        this.isCleaningUp = false;
    }

    cleanup() {
        if (this.isCleaningUp) return;
        this.isCleaningUp = true;

        console.log('Cleaning up resources...');

        // Limpiar intervalos
        intervalManager.clearAll();
        
        // Detener polling
        messagePoller.stop();
        
        // Limpiar timeouts
        if (winnerSliderTimeout) {
            clearTimeout(winnerSliderTimeout);
            winnerSliderTimeout = null;
        }
        
        if (intervalNextGame) {
            clearInterval(intervalNextGame);
            intervalNextGame = null;
        }

        // Detener confetti
        confettiManager.stop();
        
        // Limpiar cache DOM
        domCache.clear();
        
        // Limpiar arrays
        messagesDisplayed.length = 0;
        lastChatPollId = 0;
        pendingOutgoingMessageIds.clear();
        winners.length = 0;
        
        console.log('Resource cleanup completed');
    }

    initialize() {
        this.isCleaningUp = false;
        
        // Precargar recursos de audio
        audioManager.preloadNumberAudios();
        
        // Configurar eventos de limpieza
        window.addEventListener('beforeunload', () => this.cleanup());
        window.addEventListener('unload', () => this.cleanup());
        
        // Limpiar recursos cuando la página pierde el foco por mucho tiempo
        let pageHiddenTime = 0;
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                pageHiddenTime = Date.now();
            } else {
                const hiddenDuration = Date.now() - pageHiddenTime;
                // Si la página estuvo oculta por más de 5 minutos, reiniciar algunos recursos
                if (hiddenDuration > 300000) {
                    this.softReset();
                }
            }
        });
    }

    softReset() {
        console.log('Performing soft reset...');
        
        // Reiniciar polling si está detenido
        if (!messagePoller.isActive) {
            messagePoller.restart();
        }
        
        // Limpiar mensajes antiguos
        const display = $id("message-display");
        if (display) {
            const bubbles = display.getElementsByClassName("message-bubble");
            Array.from(bubbles).forEach(bubble => {
                messagePool.release(bubble);
                bubble.remove();
            });
        }
        
        // Resetear arrays de mensajes mostrados
        messagesDisplayed.length = 0;
        lastChatPollId = 0;
        pendingOutgoingMessageIds.clear();
        pollMessagesOptimized();
    }
}

// ==========================================
// FUNCIONES DE UTILIDAD ADICIONALES
// ==========================================

// Función para manejar errores de red de forma elegante
function handleNetworkError(error, context = '') {
    console.warn(`Network error in ${context}:`, error);
    
    // Mostrar notificación discreta al usuario
    const notification = document.createElement('div');
    notification.className = 'network-error-notification';
    notification.textContent = 'Conexión inestable. Reintentando...';
    notification.style.cssText = `
        display: none;
        position: fixed;
        top: 20px;
        right: 20px;
        background: #ff6b6b;
        color: white;
        padding: 10px 15px;
        border-radius: 5px;
        z-index: 10000;
        font-size: 13px;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;
    
    //document.body.appendChild(notification);
    
    // Fade in
    setTimeout(() => {
        notification.style.display = 'block';
        notification.style.opacity = '1';
    }, 100);
    
    // Fade out y remover después de 3 segundos
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Función para detectar si el dispositivo tiene recursos limitados
function isLowEndDevice() {
    // Detectar dispositivos con recursos limitados
    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    const isSlowConnection = connection && (connection.effectiveType === 'slow-2g' || connection.effectiveType === '2g');
    const isLowMemory = navigator.deviceMemory && navigator.deviceMemory < 4;
    const isOldDevice = navigator.hardwareConcurrency && navigator.hardwareConcurrency < 4;
    
    return isSlowConnection || isLowMemory || isOldDevice;
}

// Ajustar configuración según el dispositivo
function adjustConfigForDevice() {
    if (isLowEndDevice()) {
        console.log('Low-end device detected, adjusting configuration...');
        
        // Reducir frecuencia de polling
        CONFIG.BASE_POLL_INTERVAL = 3000;
        CONFIG.USER_COUNT_INTERVAL = 5000;
        CONFIG.ACCUMULATED_COUNT_INTERVAL = 5000;
        
        // Reducir efectos visuales
        CONFIG.MAX_CONFETTI = 15;
        CONFIG.MESSAGE_LIFETIME = 20000; // 20 segundos en lugar de 30
        
        // Reducir tamaños de pool
        CONFIG.MESSAGE_POOL_SIZE = 8;
        CONFIG.AUDIO_POOL_SIZE = 5;
        CONFIG.MAX_MESSAGES = 30; // Menos mensajes en pantalla
    }
}

// ==========================================
// FUNCIONES ESPECÍFICAS PARA EL CHAT MEJORADO
// ==========================================

// Función para limpiar mensajes antiguos automáticamente
function cleanupOldMessages() {
    const display = $id("message-display");
    if (!display) return;
    
    const bubbles = Array.from(display.getElementsByClassName("message-bubble"));
    const now = Date.now();
    
    bubbles.forEach(bubble => {
        const timestamp = parseInt(bubble.dataset.timestamp || '0');
        if (now - timestamp > CONFIG.MESSAGE_LIFETIME) {
            removeMessageWithFade(bubble);
        }
    });
}

// Función para formatear mensajes con menciones y enlaces
function formatMessageContent(content) {
    // Detectar menciones (@usuario)
    content = content.replace(/@(\w+)/g, '<span class="mention">@$1</span>');
    
    // Detectar URLs simples
    const urlRegex = /(https?:\/\/[^\s]+)/g;
    content = content.replace(urlRegex, '<a href="$1" target="_blank" rel="noopener">$1</a>');
    
    return content;
}

// Función para mostrar indicador de escritura
function showTypingIndicator(show = true) {
    const display = $id("message-display");
    if (!display) return;
    
    let indicator = display.querySelector('.typing-indicator');
    
    if (show && !indicator) {
        indicator = document.createElement('div');
        indicator.className = 'typing-indicator message-bubble';
        indicator.innerHTML = `
            <div class="typing-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        `;
        display.appendChild(indicator);
        scrollToBottom();
    } else if (!show && indicator) {
        indicator.remove();
    }
}

// Función para validar mensajes antes de enviar
function validateMessage(content) {
    if (!content || !content.trim()) {
        return { valid: false, error: 'El mensaje no puede estar vacío' };
    }
    
    if (content.length > 500) {
        return { valid: false, error: 'El mensaje es demasiado largo (máximo 500 caracteres)' };
    }
    
    // Filtro básico de spam
    const spamPatterns = [
        /(.)\1{10,}/, // Caracteres repetidos
        /^[A-Z\s!]{20,}$/, // Solo mayúsculas y espacios
    ];
    
    for (const pattern of spamPatterns) {
        if (pattern.test(content)) {
            return { valid: false, error: 'El mensaje parece spam' };
        }
    }
    
    return { valid: true };
}

// ==========================================
// INICIALIZACIÓN PRINCIPAL
// ==========================================
const resourceManager = new ResourceManager();

// Función de inicialización principal
function initializeApp() {
    console.log('Initializing Bingo App...');
    
    // Ajustar configuración según el dispositivo
    adjustConfigForDevice();
    
    // Inicializar gestor de recursos
    resourceManager.initialize();
    
    // Configurar eventos
    setupEvents();
    
    // Configurar scroll mask
    setupScrollMask();
    
    // Configurar countdown del juego
    setupGameCountdown();
    
    // Iniciar polling de mensajes solo cuando el panel de chat existe (transmisiones en vivo)
    const hasChatPanel = !!$id("message-display-container");
    if (hasChatPanel) {
        messagePoller.lastCallback = pollMessagesOptimized;
        messagePoller.poll(pollMessagesOptimized);
    } else {
        messagePoller.stop();
    }
    
    // Iniciar contador de usuarios
    /*intervalManager.set('userCount', updateUserCount, CONFIG.USER_COUNT_INTERVAL);
    updateUserCount();*/

    // Iniciar contador de acumulado
    intervalManager.set('gameAccumulated', updateGameAccumulated, CONFIG.ACCUMULATED_COUNT_INTERVAL);
    updateGameAccumulated();

    if (window.totalNumbersGenerated !== undefined) {
        updateBallsCounter(window.totalNumbersGenerated);
    }
    
    if (Array.isArray(window.drawnNumbers) && window.drawnNumbers.length) {
        numbersgenerated = window.drawnNumbers
            .map(parseBallNumber)
            .filter(Boolean);
        lastNumbers = numbersgenerated.slice(-5);
        reconcileBallDisplay(numbersgenerated);
        markGameAsStartedFromServer(numbersgenerated.length);
        updateBallsCounter(numbersgenerated.length);
    } else if (Array.isArray(window.fiveNumbers) && window.fiveNumbers.length) {
        lastNumbers = window.fiveNumbers
            .map(parseBallNumber)
            .filter(Boolean);
        numbersgenerated = lastNumbers.slice();
        reconcileBallDisplay(numbersgenerated);
        markGameAsStartedFromServer(lastNumbers.length);
        updateBallsCounter(lastNumbers.length);
    }

    mergeWinnersFromServer(window.winners);

    if (isAutoMarkEnabled()) {
        syncAutoMarkedNumbers(window.drawnNumbers, { animate: false, persist: false });
        $(".bingo-carton-number[id^='number-']").each(function() {
            if (isAutoMarkEnabled()) {
                this.removeAttribute('onclick');
            }
        });
        seedAutoMarkedNumbers();
        scheduleAutoSingCheck(300);
    } else {
        seedAutoMarkedNumbers();
    }

    // Si el juego ya terminó (recarga de página), no seguir sacando bolas
    if (window.gameIsFinished) {
        showGameFinalized();
    } else if (typeof timeBallLast !== 'undefined') {
        startAutomaticLast();
    }
    
    // Limpiar mensajes antiguos periódicamente
    intervalManager.set('messageCleanup', cleanupOldMessages, 60000); // Cada minuto
    
    console.log('Bingo App with Enhanced Chat initialized successfully');
}

// ==========================================
// EVENT LISTENERS PRINCIPALES
// ==========================================

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', initializeApp);

// Manejo de errores globales
window.addEventListener('error', (event) => {
    console.error('Global error:', event.error);
    handleNetworkError(event.error, 'global');
});

// Manejo de promesas rechazadas
window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled promise rejection:', event.reason);
    handleNetworkError(event.reason, 'promise');
});

// Optimización para cambios de orientación en móviles
window.addEventListener('orientationchange', debounce(() => {
    // Recalcular elementos que dependen del viewport
    confettiManager.resize();
    
    // Forzar recálculo de máscaras de scroll
    setTimeout(() => {
        const container = document.querySelector(".board-section");
        if (container) {
            container.dispatchEvent(new Event('scroll'));
        }
    }, 100);
}, 250));

// Optimización para cambios de tamaño de ventana
window.addEventListener('resize', debounce(() => {
    // Limpiar cache de elementos que pueden haber cambiado
    domCache.clear();
    
    // Recalcular confetti canvas
    confettiManager.resize();
}, 250));

// ==========================================
// EXPORTAR FUNCIONES PARA USO GLOBAL
// ==========================================

// Hacer disponibles las funciones principales globalmente para compatibilidad
window.BingoApp = {
    // Funciones principales
    sendMessage,
    sendEmoji,
    showGameFinalized,
    RemoveVolume,
    RemoveMicrophone,
    RemoveCheck,

    // Funciones del chat mejorado
    displayMessage,
    validateMessage,
    formatMessageContent,
    showTypingIndicator,
    cleanupOldMessages,
    
    // Gestores
    intervalManager,
    audioManager,
    resourceManager,
    confettiManager,
    messagePool,
    
    // Utilidades
    handleNetworkError,
    isLowEndDevice,
    
    // Estado
    get winners() { return winners; },
    get numbersGenerated() { return numbersgenerated; },
    get isGameFinished() { return isGameFinishedShown; },
    get messagesDisplayed() { return messagesDisplayed; }
};

// ==========================================
// FUNCIONES DE DEBUGGING (solo en desarrollo)
// ==========================================
if (typeof DEBUG !== 'undefined' && DEBUG) {
    window.BingoDebug = {
        // Información de estado
        getState() {
            return {
                numbersGenerated: numbersgenerated.length,
                messagesDisplayed: messagesDisplayed.length,
                winners: winners.length,
                intervals: intervalManager.intervals.size,
                isPollingActive: messagePoller.isActive,
                audioCache: audioManager.audioCache.size,
                domCache: domCache.cache.size,
                messagePool: messagePool.pool.length,
                isGameFinished: isGameFinishedShown
            };
        },
        
        // Forzar limpieza de recursos
        forceCleanup() {
            resourceManager.cleanup();
        },
        
        // Simular error de red
        simulateNetworkError() {
            handleNetworkError(new Error('Simulated network error'), 'debug');
        },

        // Simular mensaje
        simulateMessage(content = 'Mensaje de prueba 🎮') {
            displayMessage({ message: content, id: Date.now() }, imagePath);
        },

        // Limpiar chat
        clearChat() {
            const display = $id("message-display");
            if (display) {
                Array.from(display.children).forEach(child => {
                    if (child.classList.contains('message-bubble')) {
                        child.remove();
                    }
                });
            }
            messagesDisplayed.length = 0;
        },
        
        // Información de rendimiento
        getPerformanceInfo() {
            return {
                memory: performance.memory ? {
                    used: Math.round(performance.memory.usedJSHeapSize / 1048576) + ' MB',
                    total: Math.round(performance.memory.totalJSHeapSize / 1048576) + ' MB',
                    limit: Math.round(performance.memory.jsHeapSizeLimit / 1048576) + ' MB'
                } : 'Not available',
                timing: performance.timing,
                navigation: performance.navigation,
                config: CONFIG
            };
        }
    };
    
    console.log('Bingo Debug tools available in window.BingoDebug');
}

console.log('Bingo App script loaded successfully');
