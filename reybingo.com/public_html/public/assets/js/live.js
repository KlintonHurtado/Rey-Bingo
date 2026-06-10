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
    MESSAGE_POOL_SIZE: 15
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
            .fail((error) => {
                console.warn('Error en polling de mensajes:', error);
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
                    if (callback) callback();
                }
            }, 1000);
        }, 3000);
    }, 2000);

    //AppcreateConfetti();
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
    
    const el = $id('number-' + newNumber);
    if (el) el.removeAttribute('onclick');

    const centerBlock = $id('block-number');
    const centerBall = $id('last-number-center');
    
    if (!centerBlock || !centerBall) return;

    centerBlock.style.display = 'flex';
    centerBall.innerHTML = `<small style="position: absolute; top: -1px; font-size: 2.5rem; z-index: 1;">${getColumnClass(newNumber)}</small><span>${newNumber}</span>`;
    centerBall.className = `bingo-ball-200 ${getColumnClass(newNumber)} size-200`;
    centerBall.style.display = 'flex';

    setTimeout(() => {
        centerBall.style.transform = 'translate(-50%, -50%) scale(0)';
        centerBall.style.opacity = '0';
        
        setTimeout(() => {
            centerBall.removeAttribute('style');
            centerBall.className = '';
            centerBlock.style.display = 'none';
            
            lastNumbers.push(newNumber);
            if (lastNumbers.length > 5) lastNumbers.shift();
            
            const latestUncurrent = lastNumbers.slice(0, -1);
            const container = $("#last-five-numbers");
            if (container.length) {
                container.empty();
                latestUncurrent.forEach(num => {
                    container.append(`<div class="bingo-ball ${getColumnClass(num)} size-40"><span>${num}</span></div>`);
                });
            }
        }, 1000);
        
        const lastNumberEl = $('#last-number');
        if (lastNumberEl.length) {
            lastNumberEl.html(`<small style="position: absolute; top: -13px; font-size: 1.2rem; z-index: 1;">${getColumnClass(newNumber)}</small><span>${newNumber}</span>`)
                .removeClass()
                .addClass(`bingo-ball ${getColumnClass(newNumber)} size-130`);
        }
    }, 7000);

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

    const numberEl = $("#number-" + newNumber);
    if (numberEl.length) {
        numberEl.addClass(`bingo-ball ${getColumnClass(newNumber)} size-50`);
    }
}

function handleNewNumberCRON(newNumber, totalNumbersGenerated) {
    if (numbersgenerated.includes(newNumber)) return;

    updateBallsCounter(totalNumbersGenerated);
    numbersgenerated.push(newNumber);
    
    const el = $id('number-' + newNumber);
    if (el) el.removeAttribute('onclick');

    /*const centerBlock = $id('block-number');
    const centerBall = $id('last-number-center');
    
    if (!centerBlock || !centerBall) return;

    centerBlock.style.display = 'flex';
    centerBall.innerHTML = `<small style="position: absolute; top: -1px; font-size: 2.5rem; z-index: 1;">${getColumnClass(newNumber)}</small><span>${newNumber}</span>`;
    centerBall.className = `bingo-ball-200 ${getColumnClass(newNumber)} size-200`;
    centerBall.style.display = 'flex';*/

    // Marcar número en cartones si está inicializado el gestor
    if (bingoCardManager.initialized) {
        bingoCardManager.markNumber(newNumber);
    }

    setTimeout(() => {
        //centerBall.style.transform = 'translate(-50%, -50%) scale(0)';
        //centerBall.style.opacity = '0';
        
        setTimeout(() => {
            //centerBall.removeAttribute('style');
            //centerBall.className = '';
            //centerBlock.style.display = 'none';
            
            lastNumbers.push(newNumber);
            if (lastNumbers.length > 5) lastNumbers.shift();
            
            const latestUncurrent = lastNumbers.slice(0, -1);
            const container = $("#last-five-numbers");
            if (container.length) {
                container.empty();
                latestUncurrent.forEach(num => {
                    container.append(`<div class="bingo-ball ${getColumnClass(num)} size-40"><span>${num}</span></div>`);
                });
            }
        }, 1000);
        
        const lastNumberEl = $('#last-number');
        if (lastNumberEl.length) {
            lastNumberEl.html(`<small style="position: absolute; top: -13px; font-size: 1.2rem; z-index: 1;">${getColumnClass(newNumber)}</small><span>${newNumber}</span>`)
                .removeClass()
                .addClass(`bingo-ball ${getColumnClass(newNumber)} size-130`);
        }
    }, 1000);

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

    const numberEl = $("#number-" + newNumber);
    if (numberEl.length) {
        numberEl.addClass(`bingo-ball ${getColumnClass(newNumber)} size-50`);
    }
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
    $.get(site_url + 'boards/numberAutoSubmit')
        .done((data) => {
            if (data.status === 'pause') {
                showCountdown(data, startAutomaticGeneration);
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

function generateNumber(number) {
    // Iniciar conteo solo la primera vez que se llama manualmente
    if (!gameStarted) {
        gameStarted = true;
        startTime = new Date();
        updateGameTimer();
        gameTimerInterval = setInterval(updateGameTimer, 1000);
        sendMessage((__['game started!'] || '¡JUEGO INICIADO!') + ' 🎯', 26);
    }

    $.get(site_url + 'boards/numberSubmit/' + number)
        .done((data) => {
            if (data.status === 'pause') {
                showCountdown(data, startAutomaticGeneration);
            } else if (data.status === 'completed') {
                showGameFinalized();
            } else if (data.status === 'success') {
                handleNewNumber(data.number, data.totalNumbersGenerated);
            }
        })
        .fail(() => {
            console.warn('Error al generar el número:', number);
        });
}

function startAutomaticGeneration() {
    intervalManager.clear('generation');
    intervalManager.set('generation', generateAutoNumber, timeBallGet);
}

function stopAutomaticGeneration() {
    intervalManager.clear('generation');
}

function lastNumberGet() {
    $.get(site_url + 'boards/numberGet')
        .done((data) => {
            console.log('Admin Response:', data); // Para debug
            
            if (data.status === 'iscron') {

                handleNewNumberCRON(data.number, data.totalNumbersGenerated);
                
            } else if (data.status === 'pause') {
                // Hay un bingo - pausar y mostrar notificación
                intervalManager.clear('lastNumber');
                showCountdown(data, startAutomaticLast);
                
            } else if (data.status === 'success') {
                // Funcionamiento normal - continuar generando números
                // Actualizar interfaz con último número si es necesario
                if (data.number) {
                    updateLastNumber(data.number, data.totalNumbersGenerated);
                }
                // Continuar el ciclo automático
                startAutomaticLast();
                
            } else if (data.status === 'completed') {
                // Juego terminado
                intervalManager.clear('lastNumber');
                
                // Si hay información de jugador, mostrar notificación final
                if (data.player && data.player !== '') {
                    showCountdown(data, () => {
                        setTimeout(showGameFinalized, timeBallGet);
                    });
                } else {
                    // No hay notificación final, ir directo a finalizar
                    setTimeout(showGameFinalized, timeBallGet);
                }
            }
        })
        .fail((xhr, status, error) => {
            console.warn('Failed to get last number:', error);
        });
}

// Función auxiliar para actualizar el último número (si no existe, créala)
function updateLastNumber(number, total) {
    // Actualizar la interfaz con el último número generado
    // Implementar según tu interfaz específica
    console.log('Last number:', number, 'Total:', total);
}

function startAutomaticLast() {
    intervalManager.clear('lastNumber');
    intervalManager.set('lastNumber', lastNumberGet, timeBallLast);
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
            } else {
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
            }
        })
        .fail(() => {
            console.warn('Failed to update user count');
        });
}, 1000);

function stopUpdateGameAccumulated() {
    intervalManager.clear('gameAccumulated');
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

    // Eventos de control del juego
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

        setTimeout(() => {
            generateAutoNumber();
            startAutomaticGeneration();
        }, 2000);
    });

    $('#next-number-button').on('click', () => {
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
        startAutomaticGeneration();
        $('#play-button').hide();
        $('#stop-button, #next-number-button').show();
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

    // Toggle de mensajes mejorado
    const toggleBtn = $id("toggle-messages-btn");
    if (toggleBtn) {
        toggleBtn.addEventListener("click", function(event) {
            const messageContainer = $id("message-display-container");
            if (messageContainer) {
                const isVisible = messageContainer.style.display === "flex";
                messageContainer.style.display = isVisible ? "none" : "flex";
                
                // Actualizar icono del botón si existe
                const icon = toggleBtn.querySelector('i');
                if (icon) {
                    icon.className = isVisible ? 'fa fa-comments' : 'fa fa-times';
                }
            }
            event.stopPropagation();
        });
    }

    // Click fuera para cerrar mensajes
    document.addEventListener("click", function(event) {
        const messageContainer = $id("message-display-container");
        const toggleButton = $id("toggle-messages-btn");
        
        if (messageContainer && toggleButton && 
            messageContainer.style.display === "flex" && 
            !messageContainer.contains(event.target) && 
            !toggleButton.contains(event.target)) {
            messageContainer.style.display = "none";
            
            // Actualizar icono del botón
            const icon = toggleButton.querySelector('i');
            if (icon) {
                icon.className = 'fa fa-comments';
            }
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
    
    // Iniciar contador de usuarios
    intervalManager.set('userCount', updateUserCount, CONFIG.USER_COUNT_INTERVAL);
    updateUserCount();

    // Iniciar contador de acumulado
    intervalManager.set('gameAccumulated', updateGameAccumulated, CONFIG.ACCUMULATED_COUNT_INTERVAL);
    updateGameAccumulated();
    
    // Iniciar último número si es necesario
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
