// ==========================================
// CONFIGURACIÓN Y CONSTANTES
// ==========================================
const CONFIG = {
    MAX_MESSAGES: 50,        // Aumentado para el nuevo sistema
    MAX_CONFETTI: 100,
    BASE_POLL_INTERVAL: 3500,
    MAX_POLL_INTERVAL: 20000,
    // Un solo poll liveStatusGet (jugadores+acumulado). Intervalo alto = menos 403 hcdn
    LIVE_STATUS_INTERVAL: 10000,
    USER_COUNT_INTERVAL: 10000,
    ACCUMULATED_COUNT_INTERVAL: 10000,
    MESSAGE_LIFETIME: 30000, // 30 segundos para mensajes
    FADE_OUT_TIME: 500,      // Tiempo de animación de desvanecimiento
    DEBOUNCE_DELAY: 100,
    AUDIO_POOL_SIZE: 10,
    MESSAGE_POOL_SIZE: 15,
    WINNER_SLIDER_INTERVAL: 5000,
    WAF_COOLDOWN_MS: 45000
};

// Pausa global de polls cuando Hostinger CDN (hcdn) responde 403
window.__bingoWafCooldownUntil = 0;

function bingoIsWafCooling() {
    return Date.now() < (window.__bingoWafCooldownUntil || 0);
}

function bingoTripWafCooldown(ms) {
    const wait = ms || CONFIG.WAF_COOLDOWN_MS || 45000;
    window.__bingoWafCooldownUntil = Date.now() + wait;
    console.warn('CDN/WAF 403: pausando polls AJAX ~' + Math.round(wait / 1000) + 's');
}

if (typeof $ !== 'undefined' && !window.__bingoAjaxWafHook) {
    window.__bingoAjaxWafHook = true;
    $(document).ajaxComplete(function (_event, xhr) {
        if (xhr && xhr.status === 403) {
            bingoTripWafCooldown();
        }
    });
}

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
let centerBallTimer = null;
let centerBallHideTimer = null;
let pendingNumberSubmits = new Set();
// LIVE = solo clic manual. Nunca cantar bolas con numberAutoSubmit.
const LIVE_MANUAL_ONLY = true;
let bingoPauseInProgress = false;
let lastBingoPauseKey = '';
let liveSubmitInFlight = false;
let liveCenterAnimBusy = false;
let liveCenterAnimQueue = [];

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

// Pool de elementos de mensajes mejorado para el nuevo chat
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
            // Reset element
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
            
            // Reset interval on success
            if (result && result.status === 'success') {
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

// Sistema de marcado de números en cartones
class BingoCardManager {
    constructor() {
        this.markedNumbers = new Set();
        this.cardElements = new Map(); // Mapeo de números a elementos DOM
        this.storageKey = 'bingo_marked_numbers';
        this.gameId = null;
        this.initialized = false;
    }
    
    init(gameId) {
        if (this.initialized) return;
        
        this.gameId = gameId || 'default';
        this.storageKey = `bingo_marked_numbers_${this.gameId}`;
        
        // Cargar números marcados desde localStorage
        this.loadMarkedNumbers();
        
        // Indexar todos los elementos de cartón para acceso rápido
        this.indexCardElements();
        
        // Aplicar marcas a los elementos
        this.applyMarkedNumbers();
        
        // Configurar observador para cambios dinámicos en el DOM
        this.setupMutationObserver();
        
        this.initialized = true;
        console.log('BingoCardManager inicializado para el juego:', this.gameId);
    }
    
    indexCardElements() {
        this.cardElements.clear();
        
        // Seleccionar todas las celdas de números en cartones
        document.querySelectorAll('.card-number').forEach(element => {
            const number = this.extractNumber(element);
            if (number > 0) {
                if (!this.cardElements.has(number)) {
                    this.cardElements.set(number, []);
                }
                this.cardElements.get(number).push(element);
            }
        });
        
        console.log(`Indexados ${this.cardElements.size} números únicos en cartones`);
    }
    
    extractNumber(element) {
        // Obtener el texto y limpiarlo
        const text = element.textContent.trim();
        
        // Intentar extraer un número
        const match = text.match(/\d+/);
        if (match) {
            return parseInt(match[0], 10);
        }
        
        return 0;
    }
    
    loadMarkedNumbers() {
        try {
            const saved = localStorage.getItem(this.storageKey);
            if (saved) {
                const numbers = JSON.parse(saved);
                this.markedNumbers = new Set(numbers);
                console.log(`Cargados ${this.markedNumbers.size} números marcados desde localStorage`);
            }
        } catch (error) {
            console.error('Error al cargar números marcados:', error);
            // Reiniciar en caso de error
            this.markedNumbers = new Set();
            localStorage.removeItem(this.storageKey);
        }
    }
    
    saveMarkedNumbers() {
        try {
            const numbers = Array.from(this.markedNumbers);
            localStorage.setItem(this.storageKey, JSON.stringify(numbers));
        } catch (error) {
            console.error('Error al guardar números marcados:', error);
        }
    }
    
    applyMarkedNumbers() {
        // Aplicar marcas según los números guardados
        this.markedNumbers.forEach(number => {
            this.markNumberWithoutSaving(number);
        });
        
        console.log(`Aplicadas marcas a ${this.markedNumbers.size} números`);
    }
    
    markNumber(number) {
        number = parseInt(number, 10);
        if (isNaN(number)) return;
        
        // Marcar el número
        const marked = this.markNumberWithoutSaving(number);
        
        // Si se marcó correctamente, guardar
        if (marked) {
            this.markedNumbers.add(number);
            this.saveMarkedNumbers();
        }
    }
    
    markNumberWithoutSaving(number) {
        const elements = this.cardElements.get(number) || [];
        
        if (elements.length === 0) {
            return false;
        }
        
        elements.forEach(element => {
            // Marcar el elemento
            element.classList.add('marked');
            
            // Añadir efecto visual temporal
            element.classList.add('just-marked');
            setTimeout(() => {
                element.classList.remove('just-marked');
            }, 2000);
        });
        
        return true;
    }
    
    setupMutationObserver() {
        // Crear un observador que detecte cuando se añaden nuevos cartones
        const observer = new MutationObserver((mutations) => {
            let needsReindex = false;
            
            mutations.forEach(mutation => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // Verificar si alguno de los nodos añadidos contiene cartones
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1 && // Es un elemento
                            (node.classList?.contains('bingo-card') || 
                             node.querySelector?.('.bingo-card'))) {
                            needsReindex = true;
                        }
                    });
                }
            });
            
            if (needsReindex) {
                console.log('Detectados nuevos cartones, reindexando...');
                this.indexCardElements();
                this.applyMarkedNumbers();
            }
        });
        
        // Observar cambios en todo el documento
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
}

// ==========================================
// INSTANCIAS GLOBALES
// ==========================================
const intervalManager = new IntervalManager();
const domCache = new DOMCache();
const messagePool = new MessagePool();
const audioManager = new AudioManager();
const messagePoller = new SmartPoller(CONFIG.BASE_POLL_INTERVAL);
const confettiManager = new CanvasConfetti();
const bingoCardManager = new BingoCardManager();

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
// FUNCIONES DE CHAT MEJORADAS
// ==========================================

// Función para crear burbujas de mensaje estilo redes sociales
function createMessageBubble(content, profilePicUrl) {
    const bubble = messagePool.get();
    bubble.style.display = "flex";
    
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

// Función mejorada para mostrar mensajes estilo redes sociales
function displayMessage(messageData, imageUrl) {
    const display = $id("message-display");
    if (!display) return;
    
    limitMessages();
    
    const bubble = createMessageBubble(
        messageData.message || messageData, 
        imageUrl || imagePath || 'default-avatar.png'
    );
    
    // Insertar al principio para que aparezca abajo (ya que usamos column-reverse)
    display.insertBefore(bubble, display.firstChild);
    
    // Guardar ID del mensaje si existe
    if (messageData.id) {
        messagesDisplayed.push(messageData.id);
    }
    
    // Programar eliminación automática
    setTimeout(() => removeMessageWithFade(bubble), CONFIG.MESSAGE_LIFETIME);
}

// Función mejorada para enviar mensajes
function sendMessage(content, id) {
    if (!content || !content.trim()) return;
    
    const trimmedContent = content.trim();
    
    // Mostrar mensaje inmediatamente en la interfaz sin ID local
    // para evitar contaminar el array messagesDisplayed.
    displayMessage({ message: trimmedContent }, imagePath);
    
    // Enviar al servidor
    $.post(site_url + 'playings/messageSubmit', { message: trimmedContent })
        .done((data) => {
            if (data.status === 'success') {
                const inputField = $('#message-send-new');
                if (inputField.length) {
                    inputField.val('');
                }
            }
        })
        .fail(() => {
            console.warn('Error al enviar mensaje');
        });
}

// Función para enviar emojis (reutiliza la lógica de sendMessage)
function sendEmoji(content, id) {
    sendMessage(content, id);
}

// Función para enviar mensaje desde el campo de texto
function sendMessageText() {
    const inputField = $id('message-send-new');
    if (inputField && inputField.value.trim()) {
        sendMessage(inputField.value);
        inputField.value = '';
    }
}

// Polling optimizado de mensajes mejorado
function pollMessagesOptimized() {
    return new Promise((resolve) => {
        if (bingoIsWafCooling()) {
            return resolve({ status: 'success' });
        }

        $.get(site_url + 'playings/messageGet')
            .done((data) => {
                if (data.status === 'stop') {
                    messagePoller.stop();
                    return resolve(data);
                }
                
                if (data.status === 'success' && data.message && 
                    !messagesDisplayed.includes(data.message.id)) {
                    displayMessage(data.message, data.image);
                }
                resolve(data);
            })
            .fail((xhr) => {
                if (xhr && xhr.status === 403) {
                    bingoTripWafCooldown();
                }
                console.warn('Error en polling de mensajes:', xhr);
                resolve({ status: 'error' });
            });
    });
}

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

function startWinnerSlider() {
    if (winners.length === 0) return;

    if (winnerSliderTimeout) {
        clearTimeout(winnerSliderTimeout);
        winnerSliderTimeout = null;
    }

    const intervalMs = Math.max(3000, parseInt(CONFIG.WINNER_SLIDER_INTERVAL, 10) || 5000);
    const nextGameSpan = document.querySelector('.next-game');
    if (!nextGameSpan) return;

    nextGameSpan.classList.add('is-winner');

    // Un solo ganador: texto fijo (sin bucle)
    if (winners.length === 1) {
        const current = winners[0];
        nextGameSpan.textContent = `GANADOR: ${current.player} - ${current.modality}`;
        return;
    }

    function showNext() {
        const current = winners[winnerIndex % winners.length];
        if (current && nextGameSpan) {
            nextGameSpan.textContent = `GANADOR: ${current.player} - ${current.modality}`;
        }
        winnerIndex = (winnerIndex + 1) % winners.length;
        winnerSliderTimeout = setTimeout(showNext, intervalMs);
    }

    showNext();
}

function showCountdown(data, callback) {
    const pauseKey = [
        data && data.player ? data.player : '',
        data && data.modalityId ? data.modalityId : (data && data.modality ? data.modality : ''),
        data && data.number ? data.number : ''
    ].join('|');

    // Evitar apilar pausas / audio / callbacks (traba la UI)
    if (bingoPauseInProgress && pauseKey === lastBingoPauseKey) {
        return;
    }
    bingoPauseInProgress = true;
    lastBingoPauseKey = pauseKey;

    // En LIVE nunca reanudar generación automática tras un bingo
    stopAutomaticGeneration();
    pendingNumberSubmits.clear();

    const container = $id('countdown-container');

    if (data && data.player && data.modality) {
        if (!winners.some(w => w.player === data.player && w.modality === data.modality)) {
            winners.push({ player: data.player, modality: data.modality });
        }
        startWinnerSlider();
    }

    if (container) {
        container.style.display = 'none';
    }

    try {
        audioManager.play(audioPath + 'winner.mp3');
    } catch (e) {}

    const cartn = data && data.modalityId ? $id(`modality-${data.modalityId}`) : null;
    if (cartn) {
        cartn.classList.add('cartn-sing');
        cartn.querySelectorAll('.card-number.modality-sing').forEach(el => {
            el.classList.add('sing');
            el.innerText = '⭐️';
        });
    }

    if (typeof window.loadNotifications === 'function') {
        setTimeout(function () {
            window.loadNotifications();
        }, 300);
    }

    setTimeout(function () {
        bingoPauseInProgress = false;
        // Solo reanudar poll de estado (bingos), NUNCA auto-cantar bolas
        if (typeof callback === 'function' && callback !== startAutomaticGeneration) {
            callback();
        } else {
            startAutomaticLast();
        }
    }, 1500);
}

function updateBallsCounter(totalNumbersGenerated) {
    const totalBalls = 75;
    const drawn = totalNumbersGenerated;
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

function clearCenterBallTimers() {
    if (centerBallTimer) {
        clearTimeout(centerBallTimer);
        centerBallTimer = null;
    }
    if (centerBallHideTimer) {
        clearTimeout(centerBallHideTimer);
        centerBallHideTimer = null;
    }
}

function enqueueLiveCenterBall(parsed) {
    liveCenterAnimQueue.push(parsed);
    drainLiveCenterQueue();
}

function drainLiveCenterQueue() {
    if (liveCenterAnimBusy || !liveCenterAnimQueue.length) {
        return;
    }
    liveCenterAnimBusy = true;
    const parsed = liveCenterAnimQueue.shift();
    paintLiveCenterBallOnly(parsed, function() {
        liveCenterAnimBusy = false;
        drainLiveCenterQueue();
    });
}

function paintLiveCenterBallOnly(parsed, onDone) {
    const centerBlock = $id('block-number');
    const centerBall = $id('last-number-center');

    if (!centerBlock || !centerBall) {
        if (typeof onDone === 'function') {
            onDone();
        }
        return;
    }

    clearCenterBallTimers();

    if (typeof narrationPlaying !== 'undefined' && narrationPlaying) {
        audioManager.play(audioPath + parsed + '.mp3');
    }

    centerBlock.style.display = 'flex';
    centerBall.innerHTML = '';
    centerBall.innerHTML = `<small style="position: absolute; top: -1px; font-size: 2.5rem; z-index: 1;">${getColumnClass(parsed)}</small><span>${parsed}</span>`;
    centerBall.className = `bingo-ball-200 ${getColumnClass(parsed)} size-200`;
    centerBall.style.display = 'flex';
    centerBall.style.transform = '';
    centerBall.style.opacity = '1';

    const displayMs = 1200;

    centerBallHideTimer = setTimeout(function() {
        centerBall.style.transform = 'translate(-50%, -50%) scale(0)';
        centerBall.style.opacity = '0';

        centerBallTimer = setTimeout(function() {
            centerBall.removeAttribute('style');
            centerBall.className = '';
            centerBlock.style.display = 'none';
            if (typeof onDone === 'function') {
                onDone();
            }
        }, 350);
    }, displayMs);
}

function paintLiveBallUi(newNumber, totalNumbersGenerated) {
    const parsed = parseInt(newNumber, 10);
    if (!parsed) {
        return;
    }

    if (!numbersgenerated.includes(parsed)) {
        numbersgenerated.push(parsed);
    }

    if (typeof totalNumbersGenerated !== 'undefined' && totalNumbersGenerated !== null) {
        updateBallsCounter(totalNumbersGenerated);
    } else {
        updateBallsCounter(numbersgenerated.length);
    }

    const el = $id('number-' + parsed);
    if (el) el.removeAttribute('onclick');

    lastNumbers = lastNumbers.filter(function(n) { return parseInt(n, 10) !== parsed; });
    lastNumbers.push(parsed);
    if (lastNumbers.length > 5) lastNumbers.shift();

    const lastNumberEl = $('#last-number');
    if (lastNumberEl.length) {
        lastNumberEl.empty()
            .html(`<small style="position: absolute; top: -13px; font-size: 1.2rem; z-index: 1;">${getColumnClass(parsed)}</small><span>${parsed}</span>`)
            .removeClass()
            .addClass(`bingo-ball ${getColumnClass(parsed)} size-130 move-number`);
        setTimeout(function() {
            lastNumberEl.removeClass('move-number');
        }, 400);
    }

    const latestUncurrent = lastNumbers.slice(0, -1);
    const container = $("#last-five-numbers");
    if (container.length) {
        container.empty();
        latestUncurrent.forEach(function(num) {
            container.append(`<div class="bingo-ball ${getColumnClass(num)} size-40"><span>${num}</span></div>`);
        });
    }

    const numberEl = $("#number-" + parsed);
    if (numberEl.length) {
        numberEl.addClass(`bingo-ball ${getColumnClass(parsed)} size-70`);
    }

    // Cola: evita dos bolas grandes / audios a la vez
    enqueueLiveCenterBall(parsed);
}

function handleNewNumber(newNumber, totalNumbersGenerated) {
    const parsed = parseInt(newNumber, 10);
    if (!parsed) {
        return;
    }

    // Si ya se pintó de forma optimista, solo sincroniza contador
    if (numbersgenerated.includes(parsed)) {
        if (typeof totalNumbersGenerated !== 'undefined') {
            updateBallsCounter(totalNumbersGenerated);
        }
        return;
    }

    paintLiveBallUi(parsed, totalNumbersGenerated);
}

function handleNewNumberCRON(newNumber, totalNumbersGenerated) {
    handleNewNumber(newNumber, totalNumbersGenerated);

    if (bingoCardManager.initialized) {
        bingoCardManager.markNumber(newNumber);
    }
}

function generateNumber(number) {
    const parsed = parseInt(number, 10);
    if (!parsed || pendingNumberSubmits.has(parsed)) {
        return;
    }

    if (bingoPauseInProgress || isGameFinishedShown) {
        return;
    }

    // Un solo submit a la vez: evita 2 bolas cantadas / UI adelantada
    if (liveSubmitInFlight) {
        return;
    }

    if (numbersgenerated.includes(parsed)) {
        return;
    }

    // Por si quedó un intervalo auto activo, cortarlo (LIVE es manual)
    stopAutomaticGeneration();

    // Iniciar conteo solo la primera vez que se llama manualmente
    if (!gameStarted) {
        gameStarted = true;
        startTime = new Date();
        updateGameTimer();
        gameTimerInterval = setInterval(updateGameTimer, 1000);
        sendMessage((__['game started!'] || '¡JUEGO INICIADO!') + ' 🎯', 26);
    }

    // Mostrar al instante (sin esperar al servidor)
    liveSubmitInFlight = true;
    pendingNumberSubmits.add(parsed);
    paintLiveBallUi(parsed);

    $.get(site_url + 'boards/numberSubmit/' + parsed)
        .done(function(data) {
            if (data.status === 'pause') {
                // IMPORTANTE: no pasar startAutomaticGeneration (cantaba bolas solas)
                showCountdown(data, startAutomaticLast);
            } else if (data.status === 'completed') {
                showGameFinalized();
            } else if (data.status === 'success') {
                if (typeof data.totalNumbersGenerated !== 'undefined') {
                    updateBallsCounter(data.totalNumbersGenerated);
                }
                if (Array.isArray(data.drawnNumbers)) {
                    data.drawnNumbers.forEach(function(n) {
                        const num = parseInt(n, 10);
                        if (num && !numbersgenerated.includes(num)) {
                            numbersgenerated.push(num);
                        }
                    });
                }
            } else if (data.status === 'error') {
                numbersgenerated = numbersgenerated.filter(function(n) { return parseInt(n, 10) !== parsed; });
                lastNumbers = lastNumbers.filter(function(n) { return parseInt(n, 10) !== parsed; });
                updateBallsCounter(numbersgenerated.length);
                const numberEl = $("#number-" + parsed);
                if (numberEl.length) {
                    numberEl.removeClass('bingo-ball B I N G O size-50 size-70')
                        .attr('onclick', 'generateNumber(' + parsed + ');');
                }
                if (typeof Toastify === 'function') {
                    Toastify({
                        text: data.message || 'No se pudo cantar la bola',
                        duration: 4000,
                        gravity: 'top',
                        position: 'right',
                        style: { background: '#dc3545' },
                        stopOnFocus: true
                    }).showToast();
                }
            }
        })
        .fail(function() {
            console.warn('Error al generar el número:', parsed);
        })
        .always(function() {
            pendingNumberSubmits.delete(parsed);
            liveSubmitInFlight = false;
        });
}

function formatTimeUnit(value) {
    return value < 10 ? `0${value}` : value;
}

function updateGameTimer() {
    const now = new Date();
    const diffMs = now - startTime;

    const totalSeconds = Math.floor(diffMs / 1000);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    const label = hours > 0
        ? `HORA${hours > 1 ? 'S' : ''}`
        : minutes > 0
            ? `MINUTO${minutes > 1 ? 'S' : ''}`
            : `SEGUNDO${seconds > 1 ? 'S' : ''}`;

    const timeText = `${formatTimeUnit(hours)}:${formatTimeUnit(minutes)}:${formatTimeUnit(seconds)}`;

    $('.init-count small').text(label);
    $('.init-count .time-text').text(timeText);
}

// Funciones de generación de números optimizadas
function generateAutoNumber() {
    // LIVE: jamás cantar bolas solas
    if (LIVE_MANUAL_ONLY) {
        stopAutomaticGeneration();
        return;
    }

    if (bingoPauseInProgress || isGameFinishedShown || bingoIsWafCooling()) {
        return;
    }

    $.get(site_url + 'boards/numberAutoSubmit')
        .done((data) => {
            if (data.status === 'pause') {
                stopAutomaticGeneration();
                showCountdown(data, startAutomaticLast);
            } else if (data.status === 'completed') {
                showGameFinalized();
            } else if (data.status === 'success') {
                handleNewNumber(data.number, data.totalNumbersGenerated);
            }
        })
        .fail(() => {
            console.warn('Failed to generate auto number');
        });
}

function startAutomaticGeneration() {
    // LIVE es solo selección manual del admin
    if (LIVE_MANUAL_ONLY) {
        stopAutomaticGeneration();
        console.warn('LIVE: generación automática desactivada (solo clic manual)');
        return;
    }
    intervalManager.clear('generation');
    intervalManager.set('generation', generateAutoNumber, timeBallGet);
}

function stopAutomaticGeneration() {
    intervalManager.clear('generation');
}

function syncLiveDrawnFromServer(drawnNumbers, totalNumbersGenerated) {
    if (!Array.isArray(drawnNumbers) || !drawnNumbers.length) {
        return;
    }

    drawnNumbers.forEach(function(n) {
        const parsed = parseInt(n, 10);
        if (!parsed || numbersgenerated.includes(parsed)) {
            return;
        }
        // Solo marcar en tablero (sin animación de centro en ráfaga)
        numbersgenerated.push(parsed);
        const numberEl = $("#number-" + parsed);
        if (numberEl.length) {
            numberEl.addClass('bingo-ball ' + getColumnClass(parsed) + ' size-50')
                .removeAttr('onclick');
        }
    });

    if (typeof totalNumbersGenerated !== 'undefined') {
        updateBallsCounter(totalNumbersGenerated);
    } else {
        updateBallsCounter(numbersgenerated.length);
    }
}

function lastNumberGet() {
    if (bingoIsWafCooling() || isGameFinishedShown) {
        return;
    }

    // Durante anuncio de bingo no spamear pause otra vez
    if (bingoPauseInProgress) {
        return;
    }

    $.get(site_url + 'boards/numberGet')
        .done((data) => {
            if (!data) {
                return;
            }

            // Sincronizar bolas ya cantadas (sin auto-generar)
            if (Array.isArray(data.drawnNumbers) && data.drawnNumbers.length) {
                syncLiveDrawnFromServer(data.drawnNumbers, data.totalNumbersGenerated);
            }

            if (data.status === 'pause') {
                stopAutomaticGeneration();
                intervalManager.clear('lastNumber');
                showCountdown(data, startAutomaticLast);
            } else if (data.status === 'completed') {
                stopAutomaticGeneration();
                intervalManager.clear('lastNumber');
                if (data.player && data.player !== '') {
                    showCountdown(data, () => {
                        setTimeout(showGameFinalized, timeBallGet);
                    });
                } else {
                    setTimeout(showGameFinalized, timeBallGet);
                }
            } else if (data.status === 'iscron' || data.status === 'success') {
                // En LIVE no tratamos iscron como "cantar sola": solo sync ya hecho arriba
            }
        })
        .fail((xhr, status, error) => {
            if (xhr && xhr.status === 403) {
                bingoTripWafCooldown();
            }
            console.warn('Failed to get last number:', error);
        });
}

function updateLastNumber(number, total) {
    // noop: la sync va por syncLiveDrawnFromServer / paintLiveBallUi
}

function startAutomaticLast() {
    intervalManager.clear('lastNumber');
    // Poll solo para bingos/estado; NUNCA para cantar bolas
    const pollMs = Math.max(3000, parseInt(timeBallLast, 10) || 3000);
    intervalManager.set('lastNumber', lastNumberGet, pollMs);
}

function stopAutomaticLast() {
    intervalManager.clear('lastNumber');
}

function showGameFinalized() {
    if (isGameFinishedShown) return;
    isGameFinishedShown = true;
    
    const container = $id('game-finalized');
    const text = $id('finalized');
    
    if (container && text) {
        container.style.display = 'block';
        text.innerHTML = __['game finished!'] || 'JUEGO FINALIZADO!';
        
        setTimeout(() => {
            if (typeof awardsGet === 'function') {
                awardsGet();
            }
            container.style.display = 'none';
        }, 5000);
    }

    stopAutomaticGeneration();
    stopAutomaticLast();
    stopUpdateLiveStatus();
    messagePoller.stop();

    const controlsDiv = $id('controls');
    if (controlsDiv) {
        controlsDiv.remove();
    }
}

function applyLiveStatusUi(data) {
    if (!data) {
        return;
    }

    const countEl = $('.count_notifications');
    if (data.userCount && data.userCount > 0) {
        countEl.text(data.userCount).show();
    } else {
        countEl.hide();
    }

    const accumulatedEl = $('#accumulated-counter');
    if (accumulatedEl.length && typeof data.gameAccumulated !== 'undefined') {
        accumulatedEl.text(currency + ' ' + data.gameAccumulated);
    }

    if (data.modalities && data.modalities.length > 0) {
        data.modalities.forEach(modality => {
            const modalityEl = $('#modality-amount-' + modality.id);
            if (modalityEl.length > 0) {
                modalityEl.text(currency + ' ' + modality.amount);
            }
        });
    }
}

// Un solo AJAX: jugadores + acumulado (antes eran 2 polls)
const updateLiveStatus = throttle(() => {
    if (bingoIsWafCooling()) {
        return;
    }

    $.get(site_url + 'games/liveStatusGet')
        .done((data) => {
            applyLiveStatusUi(data);
            if (data.status === 'completed') {
                stopUpdateLiveStatus();
            }
        })
        .fail((xhr) => {
            if (xhr && xhr.status === 403) {
                bingoTripWafCooldown();
            }
            console.warn('Failed to update live status');
        });
}, 2000);

function stopUpdateLiveStatus() {
    intervalManager.clear('liveStatus');
}

function stopUpdateUserCount() {
    stopUpdateLiveStatus();
}

function stopUpdateGameAccumulated() {
    stopUpdateLiveStatus();
}

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

// ==========================================
// CONFIGURACIÓN DE EVENTOS MEJORADA
// ==========================================
function setupEvents() {
    // Eventos de mensajes mejorados
    $('#message-button').on('click', sendMessageText);
    
    $('#message-send-new').on('keypress', (e) => {
        if (e.which === 13) {
            e.preventDefault();
            sendMessageText();
        }
    });

    // Eventos para emojis (si tienes botones de emoji)
    $('.emoji-button').on('click', function() {
        const emoji = $(this).data('emoji') || $(this).text();
        sendEmoji(emoji);
    });

    // Eventos de control del juego (si existieran botones play/auto en otra vista)
    $('#start-button').on('click', () => {
        $('#start-button').hide();
        $('#stop-button, #next-number-button').show();
        sendMessage((__['game started!'] || '¡JUEGO INICIADO!') + ' 😎', 26);

        if (!gameStarted) {
            gameStarted = true;
            startTime = new Date();
            updateGameTimer();
            gameTimerInterval = setInterval(updateGameTimer, 1000);
        }

        // LIVE: no arrancar auto-canto
        if (LIVE_MANUAL_ONLY) {
            stopAutomaticGeneration();
            return;
        }

        setTimeout(() => {
            generateAutoNumber();
            startAutomaticGeneration();
        }, 2000);
    });

    $('#next-number-button').on('click', () => {
        if (LIVE_MANUAL_ONLY) {
            stopAutomaticGeneration();
            return;
        }
        intervalManager.clear('generation');
        generateAutoNumber();
        startAutomaticGeneration();
    });

    $('#stop-button').on('click', () => {
        stopAutomaticGeneration();
        $('#stop-button, #next-number-button').hide();
        $('#play-button').show();
    });

    $('#play-button').on('click', () => {
        if (LIVE_MANUAL_ONLY) {
            stopAutomaticGeneration();
            return;
        }
        startAutomaticGeneration();
        $('#play-button').hide();
        $('#stop-button, #next-number-button').show();
    });

    // Silencio/micrófono: solo onclick RemoveVolume/RemoveMicrophone (sin doble toggle)

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

    function setChatPanelOpen(open) {
        const messageContainer = document.getElementById("message-display-container");
        const toggleBtn = document.getElementById("toggle-messages-btn");
        if (!messageContainer) {
            return;
        }
        messageContainer.classList.toggle("is-open", open);
        messageContainer.style.display = open ? "flex" : "none";
        messageContainer.setAttribute("aria-hidden", open ? "false" : "true");
        document.body.classList.toggle("chat-panel-open", open);
        if (toggleBtn) {
            toggleBtn.setAttribute("aria-expanded", open ? "true" : "false");
        }
    }

    function isChatPanelOpen() {
        const messageContainer = document.getElementById("message-display-container");
        if (!messageContainer) {
            return false;
        }
        return messageContainer.classList.contains("is-open")
            || messageContainer.style.display === "flex";
    }

    $(document).off("click.liveChatToggle", "#toggle-messages-btn")
        .on("click.liveChatToggle", "#toggle-messages-btn", function(event) {
            event.preventDefault();
            event.stopPropagation();
            setChatPanelOpen(!isChatPanelOpen());
        });

    $(document).off("click.liveChatClose", "#message-display-close")
        .on("click.liveChatClose", "#message-display-close", function(event) {
            event.preventDefault();
            event.stopPropagation();
            setChatPanelOpen(false);
        });

    $(document).off("click.liveChatOutside").on("click.liveChatOutside", function(event) {
        if (!isChatPanelOpen()) {
            return;
        }
        if ($(event.target).closest("#message-display-container, #toggle-messages-btn, #message-display-close").length) {
            return;
        }
        setChatPanelOpen(false);
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
    const container = document.querySelector(".board-section");
    if (!container) return;

    function isMobile() {
        return window.innerWidth <= 700;
    }

    function isTablet() {
        return window.innerWidth >= 701 && window.innerWidth <= 1024;
    }

    function shouldApplyMask() {
        return isMobile() || isTablet();
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

    container.addEventListener("scroll", updateMask);
    window.addEventListener("resize", updateMask);
    updateMask();
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

        if (timeDiff <= 0) {
            clearInterval(intervalNextGame);

            if (typeof totalNumbersGenerated !== 'undefined' && totalNumbersGenerated > 0) {
                if (winners.length > 0) {
                    startWinnerSlider();
                } else {
                    nextGameSpan.textContent = '¡EL JUEGO HA INICIADO!';
                }
            } else {
                nextGameSpan.textContent = 'LOS JUGADORES ESPERAN EL INICIO DE LA PARTIDA...';
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
        if (typeof totalNumbersGenerated !== 'undefined' && totalNumbersGenerated > 0) {
            if (winners.length > 0) {
                startWinnerSlider();
            } else {
                nextGameSpan.textContent = '¡EL JUEGO HA INICIADO!';
            }
        } else {
            nextGameSpan.textContent = 'LOS JUGADORES ESPERAN EL INICIO DE LA PARTIDA...';
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

        if (gameTimerInterval) {
            clearInterval(gameTimerInterval);
            gameTimerInterval = null;
        }

        // Detener confetti
        confettiManager.forceStop();
        
        // Limpiar cache DOM
        domCache.clear();
        
        // Limpiar arrays
        messagesDisplayed.length = 0;
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
        CONFIG.BASE_POLL_INTERVAL = 5000;
        CONFIG.LIVE_STATUS_INTERVAL = 15000;
        CONFIG.USER_COUNT_INTERVAL = 15000;
        CONFIG.ACCUMULATED_COUNT_INTERVAL = 15000;
        
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
    if (window.__liveAppInitialized) {
        return;
    }
    window.__liveAppInitialized = true;

    console.log('Initializing Bingo App with Enhanced Chat...');
    
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
    
    // Iniciar polling de mensajes
    messagePoller.lastCallback = pollMessagesOptimized;
    messagePoller.poll(pollMessagesOptimized);
    
    // Un solo poll: jugadores + acumulado
    intervalManager.set('liveStatus', updateLiveStatus, CONFIG.LIVE_STATUS_INTERVAL || 10000);
    updateLiveStatus();

    // LIVE: asegurar que no quede ningún auto-canto corriendo
    stopAutomaticGeneration();
    
    // Poll de estado/bingos (no canta bolas)
    if (typeof timeBallLast !== 'undefined') {
        startAutomaticLast();
    }
    
    // Limpiar mensajes antiguos periódicamente
    intervalManager.set('messageCleanup', cleanupOldMessages, 60000); // Cada minuto
    
    console.log('Bingo App with Enhanced Chat initialized successfully');
}

// ==========================================
// EVENT LISTENERS PRINCIPALES
// ==========================================

// Inicialización cuando el DOM esté listo (o ya listo si el script carga tarde)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApp);
} else {
    initializeApp();
}

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
    sendMessageText,
    generateNumber,
    startAutomaticGeneration,
    stopAutomaticGeneration,
    showGameFinalized,
    RemoveVolume,
    RemoveMicrophone,
    
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
                gameStarted,
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

console.log('Bingo App with Enhanced Chat script loaded successfully');
