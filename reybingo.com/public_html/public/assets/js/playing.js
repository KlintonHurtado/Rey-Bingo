// ==========================================
// CONFIGURACIÓN Y CONSTANTES
// ==========================================
const CONFIG = {
    MAX_MESSAGES: 50,        // Aumentado para el nuevo sistema
    MAX_CONFETTI: 100,
    BASE_POLL_INTERVAL: 2000,
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

// ==========================================
// INSTANCIAS GLOBALES
// ==========================================
const intervalManager = new IntervalManager();
const domCache = new DOMCache();
const messagePool = new MessagePool();
const audioManager = new AudioManager();
const messagePoller = new SmartPoller(CONFIG.BASE_POLL_INTERVAL);
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
    span.className = /[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/u.test(content)
        ? 'emoji-message'
        : 'text-message';
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
    return messageData.message ?? '';
}

function getLastChatMessageId() {
    const numericIds = messagesDisplayed
        .map((id) => parseInt(id, 10))
        .filter((id) => !Number.isNaN(id) && id > 0);
    return numericIds.length ? Math.max(...numericIds) : 0;
}

function processIncomingChatMessages(data) {
    if (!data) return;

    const list = Array.isArray(data.messages)
        ? data.messages
        : (data.status === 'success' && data.message ? [data.message] : []);

    list.forEach((row) => {
        const id = parseInt(row.id, 10);
        const text = getMessageText(row);
        if (!text) return;
        if (!Number.isNaN(id) && id > 0 && messagesDisplayed.includes(id)) return;
        displayMessage({ message: text, id: Number.isNaN(id) ? Date.now() : id }, row.image || data.image);
    });
}

// Función mejorada para mostrar mensajes estilo redes sociales
function displayMessage(messageData, imageUrl) {
    const display = $id("message-display");
    if (!display) return;
    
    limitMessages();

    const bubble = createMessageBubble(
        getMessageText(messageData),
        imageUrl || imagePath || 'default-avatar.png'
    );
    
    // Insertar al principio para que aparezca abajo (ya que usamos column-reverse)
    display.insertBefore(bubble, display.firstChild);
    
    if (messageData.id) {
        const msgId = parseInt(messageData.id, 10);
        if (!Number.isNaN(msgId) && !messagesDisplayed.includes(msgId)) {
            messagesDisplayed.push(msgId);
        }
    }
    
    // Programar eliminación automática
    setTimeout(() => removeMessageWithFade(bubble), CONFIG.MESSAGE_LIFETIME);
}

// Función mejorada para enviar mensajes
function sendMessage(content, id) {
    if (!content || !content.trim()) return;
    
    const trimmedContent = content.trim();
    const messageId = id || Date.now();
    
    // Mostrar mensaje inmediatamente en la interfaz
    displayMessage({ message: trimmedContent, id: messageId }, imagePath);
    
    // Enviar al servidor
    $.post(site_url + 'playings/messageSubmit', { message: trimmedContent })
        .done((data) => {
            if (data.status === 'success') {
                $('#message-send-new').val('');
                if (data.id) {
                    const tempIndex = messagesDisplayed.indexOf(messageId);
                    if (tempIndex >= 0) {
                        messagesDisplayed[tempIndex] = data.id;
                    } else {
                        messagesDisplayed.push(data.id);
                    }
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
    if (!inputField) return;
    const raw = inputField.value || '';
    const trimmed = raw.trim();
    if (!trimmed) return;

    // No permitir emoticones/pictogramas al escribir. Las reacciones se envían con los botones.
    let emojiRegex = null;
    try {
        emojiRegex = /\p{Extended_Pictographic}/u;
    } catch (e) {
        // Fallback para navegadores sin soporte de propiedades unicode.
        emojiRegex = /[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/u;
    }

    const sanitized = trimmed.replace(emojiRegex, '').trim();
    if (!sanitized) {
        inputField.value = '';
        return;
    }

    sendMessage(sanitized);
    inputField.value = '';
}

// ==========================================
// AUTO-BINGO: si el jugador completa cartón lleno
// ==========================================
let lastAutoSingBall = null;
let autoSingInFlight = false;

function getLastBoardNumber() {
    const el = document.querySelector('#last-number span');
    if (!el) return null;
    const val = parseInt((el.textContent || '').trim(), 10);
    return Number.isNaN(val) ? null : val;
}

function isFullCardMarked(cartonEl) {
    if (!cartonEl) return false;
    const cells = Array.from(cartonEl.querySelectorAll('.bingo-carton-number'));
    if (cells.length < 24) return false;
    return cells.every((cell) => {
        // Centro estrella y/o marcado
        if (cell.classList.contains('modality') || cell.classList.contains('data-position-13')) return true;
        return cell.classList.contains('marked');
    });
}

function autoSingIfComplete() {
    if (autoSingInFlight) return;
    // Solo cuando el juego ya arrancó
    const total = parseInt(window.totalNumbersGenerated || '0', 10);
    if (Number.isNaN(total) || total <= 0) return;

    const lastBall = getLastBoardNumber();
    if (!lastBall) return;
    if (lastAutoSingBall === lastBall) return;

    const cartons = Array.from(document.querySelectorAll('.bingo-carton'));
    if (!cartons.length) return;

    const anyFull = cartons.some(isFullCardMarked);
    if (!anyFull) return;

    autoSingInFlight = true;
    $.post(site_url + 'playings/singBingo', {})
        .done((data) => {
            if (data && data.status === 'success') {
                // Esto actualiza ganador + slider + marca modalidad en panel
                showCountdown(data, () => {});
            }
            lastAutoSingBall = lastBall;
        })
        .fail(() => {
            // Si falla, permitimos reintentar con la misma bola solo si cambia
            lastAutoSingBall = lastBall;
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

    // Agregar ganador si no existe
    if (!winners.some(w => w.player === data.player && w.modality === data.modality)) {
        winners.push({ player: data.player, modality: data.modality });
    }

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

function handleNewNumber(newNumber, totalNumbersGenerated) {
    if (numbersgenerated.includes(newNumber)) return;

    updateBallsCounter(totalNumbersGenerated);
    numbersgenerated.push(newNumber);
     
    if (typeof autoMarkEnabled !== 'undefined' && autoMarkEnabled) {
        const el = $id('number-' + newNumber);
        if (el) el.removeAttribute('onclick');
    }

    if (!lastNumbers.includes(newNumber)) {
        setTimeout(() => {
            setTimeout(() => {
                lastNumbers.push(newNumber);
                if (lastNumbers.length > 5) lastNumbers.shift();
                
                const container = $("#last-five-numbers");
                if (container.length) {
                    const newBall = $(`<div class="bingo-ball ${getColumnClass(newNumber)} size-40 ball-slide-in"><span>${newNumber}</span></div>`);
                    container.prepend(newBall);
                    while (container.children().length > 5) {
                        container.children().last().remove();
                    }
                }
            }, 1000);
            
            const lastNumberEl = $('#last-number');
            if (lastNumberEl.length) {
                lastNumberEl.html(`<small style="position: absolute; top: -13px; font-size: 1.2rem; z-index: 1;">${getColumnClass(newNumber)}</small><span>${newNumber}</span>`)
                    .removeClass()
                    .addClass(`bingo-ball ${getColumnClass(newNumber)} size-100`);
            }
        }, 1500);
    }

    // Reproducir narración si está activada
    if (typeof narrationPlaying !== 'undefined' && narrationPlaying) {
        audioManager.play(audioPath + newNumber + '.mp3');
    }

    setTimeout(() => {
        const lastNumberEl = $('#last-number');
        if (lastNumberEl.length) {
            lastNumberEl.removeClass("move-number");
        }
    }, 1000);

    // Auto-marcar si está habilitado
    /*if (typeof autoMarkEnabled !== 'undefined' && autoMarkEnabled) {
        autoDialNumber(newNumber);
    }*/

    const numberEl = $("#board-number-" + newNumber);
    if (numberEl.length) {
        numberEl.addClass(getColumnClass(newNumber));
    }
}

function lastNumberGet() {
    // Si hay un bingo en progreso, no solicitar nuevos números
    if (bingoInProgress) return;
    
    $.get(site_url + 'playings/numberGet')
        .done((data) => {
            // Auto-marcar si está habilitado
            if (typeof autoMarkEnabled !== 'undefined' && autoMarkEnabled) {
                autoDialNumber(data.number);
            }

            if (data.status === 'pause') {
                // Funcionamiento normal - mostrar último número
                handleNewNumber(data.number, data.totalNumbersGenerated);
            } else if (data.status === 'success') {
                // Funcionamiento normal - mostrar último número
                handleNewNumber(data.number, data.totalNumbersGenerated);
            } else if (data.status === 'completed') {
                // Funcionamiento normal - mostrar último número
                handleNewNumber(data.number, data.totalNumbersGenerated);
                
                // No hay notificación, ir directo a finalizar
                setTimeout(showGameFinalized, typeof timeBallGet !== 'undefined' ? timeBallGet : 1000);
            }
        })
        .fail((xhr, status, error) => {
            console.warn('Failed to get last number:', error);
        });
}

function startAutomaticLast() {
    intervalManager.clear('lastNumber');
    if (typeof timeBallLast !== 'undefined') {
        intervalManager.set('lastNumber', lastNumberGet, timeBallLast);
    }
}

function stopAutomaticLast() {
    intervalManager.clear('lastNumber');
}

function showGameFinalized() {
    if (isGameFinishedShown) return;
    isGameFinishedShown = true;
    window.gameIsFinished = true;
    window.allowGameUnload = true;

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
        text.innerHTML = __['game finished!'] || 'JUEGO FINALIZADO!';
        
        setTimeout(() => {
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

                    if (elementNumber.hasClass('marked')) return;

                    const originalContent = elementNumber.text();
                    elementNumber.text('⭐️').addClass('explosive-effect');

                    setTimeout(function() {
                        elementNumber.text(originalContent); 
                        elementNumber.removeClass('explosive-effect');
                        elementNumber.addClass('marked'); 
                    }, 1000);
                });
            } else {
                console.warn("Respuesta no exitosa:", data.message || data);
            }
        },
        error: function(xhr, status, error) {
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
                // Marcar que hay un bingo en progreso
                bingoInProgress = true;
                
                // Detener el intervalo de obtención de números
                intervalManager.clear('lastNumber');

                sendMessage((__['bingo!'] || 'BINGO!') + ' 🥳', 21);

                // Marcar números ganadores en el cartón
                const cartonElement = document.getElementById(`carton-${data.carton}`);
                if (cartonElement && data.numbers) {
                    data.numbers.forEach(num => {
                        const numberElement = cartonElement.querySelector(`.bingo-carton-number.number-${num}`);
                        if (numberElement) {
                            numberElement.classList.add('carton-sing');
                        }
                    });
                }

                // Mostrar countdown de victoria
                showCountdown({
                    player: data.player,
                    modality: data.modality,
                    modalityId: data.modalityId,
                    image: data.image
                }, startAutomaticLast);
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
                console.log("automarked disabled successfully");
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

    // Control de auto-marcado
    $('.btn-binary').on('click', function() {
        if (typeof autoMarkEnabled !== 'undefined') {
            autoMarkEnabled = !autoMarkEnabled;
            $(this).html(autoMarkEnabled ? 
                '<i class="fa-duotone fa-solid fa-binary-circle-check"></i>' : 
                '<i class="fa-duotone fa-solid fa-binary-slash"></i>'
            );
        }
    });

    // Click en números del cartón
    $(".bingo-carton-number").on('click', function() {
        if (typeof autoMarkEnabled === 'undefined' || !autoMarkEnabled) {
            const number = $(this).data('number'); 
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

        if (timeDiff <= 0) {
            clearInterval(intervalNextGame);

            if (window.gameIsFinished || isGameFinishedShown) {
                if (winners.length > 0) {
                    startWinnerSlider();
                } else {
                    nextGameSpan.textContent = (__['game finished!'] || 'JUEGO FINALIZADO').toUpperCase();
                }
            } else if (typeof totalNumbersGenerated !== 'undefined' && totalNumbersGenerated > 0) {
                if (winners.length > 0) {
                    startWinnerSlider();
                } else {
                    nextGameSpan.textContent = '¡EL JUEGO HA INICIADO!';
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
        } else if (typeof totalNumbersGenerated !== 'undefined' && totalNumbersGenerated > 0) {
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
    
    // Iniciar polling de mensajes
    messagePoller.lastCallback = pollMessagesOptimized;
    messagePoller.poll(pollMessagesOptimized);
    
    // Iniciar contador de usuarios
    /*intervalManager.set('userCount', updateUserCount, CONFIG.USER_COUNT_INTERVAL);
    updateUserCount();*/

    // Iniciar contador de acumulado
    intervalManager.set('gameAccumulated', updateGameAccumulated, CONFIG.ACCUMULATED_COUNT_INTERVAL);
    updateGameAccumulated();
    
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
