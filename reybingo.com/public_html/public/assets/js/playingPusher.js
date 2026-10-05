// ==========================================
// CONFIGURACIÓN Y CONSTANTES
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
    COUNTDOWN_INTERVAL: 1000,
    PUSHER_RECONNECT_DELAY: 5000
};

// ==========================================
// VARIABLES GLOBALES
// ==========================================
let numbersgenerated = [];
let lastNumbers = fiveNumbers || [];
let isGameFinishedShown = false;
let messagesDisplayed = [];
let intervalNextGame;
let winners = [];
let winnerIndex = 0;
let winnerSliderTimeout;
let bingoInProgress = false;
let simultaneousBingos = [];
let pusherClient = null;
let pusherChannel = null;

// ==========================================
// GESTORES DE RECURSOS
// ==========================================

// Gestor centralizado de intervalos
class IntervalManager {
    constructor() { this.intervals = new Map(); }
    set(name, callback, delay) { this.clear(name); this.intervals.set(name, setInterval(callback, delay)); }
    clear(name) { if (this.intervals.has(name)) { clearInterval(this.intervals.get(name)); this.intervals.delete(name); } }
    clearAll() { this.intervals.forEach(interval => clearInterval(interval)); this.intervals.clear(); }
}

// Cache de elementos DOM
class DOMCache {
    constructor() { this.cache = new Map(); }
    get(id) { if (!this.cache.has(id)) { const element = document.getElementById(id); if (element) this.cache.set(id, element); } return this.cache.get(id); }
    clear() { this.cache.clear(); }
}

// Pool de elementos de mensajes para reutilización
class MessagePool {
    constructor(maxSize = CONFIG.MESSAGE_POOL_SIZE) { this.pool = []; this.maxSize = maxSize; }
    get() { if (this.pool.length > 0) { const bubble = this.pool.pop(); bubble.style.display = "flex"; return bubble; } return this.createNew(); }
    release(element) { if (this.pool.length < this.maxSize) { element.className = 'message-bubble'; element.style.cssText = ''; element.innerHTML = ''; this.pool.push(element); } }
    createNew() { const bubble = document.createElement("div"); bubble.classList.add("message-bubble"); return bubble; }
}

// Gestor inteligente de audio
class AudioManager {
    constructor() { this.audioCache = new Map(); this.preloadedAudios = new Set(); }
    preload(src) { if (this.preloadedAudios.has(src)) return; const audio = new Audio(); audio.preload = 'auto'; audio.src = src; this.audioCache.set(src, audio); this.preloadedAudios.add(src); }
    play(src) {
        if (!soundEnabled) return;
        let audio = this.audioCache.get(src);
        if (!audio) { audio = new Audio(); audio.src = src; this.audioCache.set(src, audio); }
        const audioClone = audio.cloneNode();
        audioClone.play().catch(e => console.warn('Audio play failed:', e));
        return audioClone;
    }
    preloadNumberAudios() {
        for (let i = 1; i <= 75; i++) { this.preload(audioPath + i + '.mp3'); }
        this.preload(audioPath + 'winner.mp3');
    }
}

// Confetti optimizado con Canvas
class CanvasConfetti {
    constructor() { this.particles = []; this.isActive = false; this.activeElements = new Set(); }
    createParticles() {
        // Implementación existente de createParticles
        const emojis = ['🎉', '🎊', '✨', '🌟', '🥳', '🍾', '💥', '🔥', '💫', '🍬', '🎈'];
        this.particles = [];
        this.cleanup();
        
        for (let i = 0; i < CONFIG.MAX_CONFETTI; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.textContent = emojis[Math.floor(Math.random() * emojis.length)];
            
            const particle = {
                element: confetti,
                emoji: confetti.textContent,
                x: Math.random() * 100,
                y: Math.random() * -100,
                vx: (Math.random() - 0.5) * 2,
                vy: Math.random() * 1.5 + 0.5,
                rotation: Math.random() * 360,
                rotationSpeed: (Math.random() - 0.5) * 3,
                size: Math.random() * 30 + 10,
                alpha: 1,
                decay: Math.random() * 0.02 + 0.01,
                animationDuration: Math.random() * 6 + 4,
                animationDelay: Math.random()
            };
            
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
            
            document.body.appendChild(confetti);
            this.activeElements.add(confetti);
            
            confetti.addEventListener('animationend', () => {
                if (confetti.parentNode) {
                    confetti.parentNode.removeChild(confetti);
                }
                this.activeElements.delete(confetti);
            });
            
            this.particles.push(particle);
        }
    }
    cleanup() { this.activeElements.forEach(element => { if (element.parentNode) element.parentNode.removeChild(element); }); this.activeElements.clear(); this.particles = []; }
    start() { if (this.isActive) return; this.isActive = true; this.createParticles(); setTimeout(() => this.stop(), 6000); }
    stop() { this.isActive = false; }
    forceStop() { this.isActive = false; this.cleanup(); }
    resize() { if (this.isActive) { this.forceStop(); setTimeout(() => this.start(), 100); } }
}

// ==========================================
// INSTANCIAS GLOBALES
// ==========================================
const intervalManager = new IntervalManager();
const domCache = new DOMCache();
const messagePool = new MessagePool();
const audioManager = new AudioManager();
const confettiManager = new CanvasConfetti();

// ==========================================
// UTILIDADES
// ==========================================
const $id = (id) => domCache.get(id);

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => { clearTimeout(timeout); func(...args); };
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
// INICIALIZACIÓN DE PUSHER
// ==========================================
function initPusher() {
    if (!window.PUSHER_KEY || !window.PUSHER_CLUSTER || !window.GAME_ID) {
        console.error('Faltan las credenciales de Pusher o el ID del juego');
        return;
    }
    
    try {
        // Inicializar cliente Pusher
        pusherClient = new Pusher(window.PUSHER_KEY, {
            cluster: window.PUSHER_CLUSTER,
            channelAuthorization: {
                endpoint: window.AUTH_URL || (site_url + 'pusher/auth'),
                transport: 'ajax',
            },
        });
        
        // Suscribirse al canal del juego
        const channelName = 'private-game-' + window.GAME_ID;
        pusherChannel = pusherClient.subscribe(channelName);
        
        console.log('Conectando a canal Pusher:', channelName);
        
        // Eventos de Pusher
        pusherChannel.bind('pusher:subscription_succeeded', () => {
            console.log('✅ Suscripción exitosa a ' + channelName);
        });
        
        pusherChannel.bind('pusher:subscription_error', (error) => {
            console.error('❌ Error de suscripción a Pusher:', error);
            
            // Reintentar después de un tiempo
            setTimeout(() => {
                console.log('Reintentando conexión a Pusher...');
                initPusher();
            }, CONFIG.PUSHER_RECONNECT_DELAY);
        });
        
        // Evento cuando se genera un nuevo número
        pusherChannel.bind('game:number_drawn', (data) => {
            console.log('📡 Evento Pusher: número generado', data);
            
            // Actualizar el tablero con el nuevo número
            handleNewNumber(data.n, data.totalNumbersGenerated);
        });
        
        // Evento cuando alguien canta bingo
        pusherChannel.bind('game:bingo_claimed', (data) => {
            console.log('📡 Reclamo de BINGO recibido:', data);
            
            // No mostrar notificación si es nuestro propio bingo
            if (data.playerId !== window.session_id) {
                // Marcar que hay un bingo en progreso
                bingoInProgress = true;
                
                // Si ya hay un bingo mostrándose, agregar este a la cola
                const countdownContainer = $id('countdown-container');
                if (countdownContainer && countdownContainer.style.display === 'block') {
                    simultaneousBingos.push(data);
                } else {
                    showCountdown(data, () => {
                        bingoInProgress = false;
                    });
                }
            }
        });
        
        // Evento cuando se acepta un bingo
        pusherChannel.bind('game:bingo_accepted', (data) => {
            console.log('📡 BINGO ACEPTADO:', data);
            
            // Agregar ganador si no existe
            if (!winners.some(w => w.player === data.player && w.modality === data.modality)) {
                winners.push({ player: data.player, modality: data.modality });
                startWinnerSlider();
            }
            
            // Si el juego se detuvo, mostrar pantalla de finalización
            if (data.stopped) {
                showGameFinalized(data.message || 'Juego finalizado');
            }
        });
        
        // Evento cuando el juego termina
        pusherChannel.bind('game:completed', (data) => {
            console.log('📡 Juego completado:', data);
            showGameFinalized(data.message || 'Juego finalizado');
        });
        
        // Evento para mensajes de chat
        pusherChannel.bind('client-message', (data) => {
            console.log('📡 Mensaje recibido:', data);
            
            // Solo mostrar mensajes de otros usuarios
            if (data.userId !== window.session_id) {
                displayMessage({
                    message: data.message,
                    id: Date.now()
                }, data.userImage);
            }
        });
        
    } catch (error) {
        console.error('Error al inicializar Pusher:', error);
        
        // Reintentar después de un tiempo
        setTimeout(() => {
            console.log('Reintentando conexión a Pusher...');
            initPusher();
        }, CONFIG.PUSHER_RECONNECT_DELAY);
    }
}

// ==========================================
// FUNCIONES DE CHAT MEJORADAS
// ==========================================

// Función para crear burbujas de mensaje estilo redes sociales
function createMessageBubble(content, profilePicUrl) {
    const bubble = messagePool.get();
    
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
    
    // Determinar si es solo emoji o texto
    const isOnlyEmoji = /[\u1F600-\u1F6FF]/.test(content);
    span.textContent = content;
    
    // Aplicar estilos según el tipo de contenido
    if (isOnlyEmoji) {
        span.style.fontSize = '13px';
        bubble.style.background = 'rgba(255, 255, 255, 0.2)';
    } else {
        span.style.fontSize = '24px';
        bubble.style.background = 'rgba(98, 54, 255, 0.7)';
    }
    
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

// Función mejorada para enviar mensajes usando Pusher
function sendMessage(content, id) {
    if (!content || !content.trim()) return;
    
    const trimmedContent = content.trim();
    const messageId = id || Date.now();
    
    // Mostrar mensaje inmediatamente en la interfaz
    displayMessage({ message: trimmedContent, id: messageId }, imagePath);
    
    // Enviar a través de Pusher en lugar de AJAX
    if (pusherChannel && pusherChannel.subscribed) {
        try {
            pusherChannel.trigger('client-message', {
                userId: session_id || 'player',
                userName: user_name || 'Player',
                userImage: imagePath || 'default-avatar.png',
                message: trimmedContent,
                timestamp: new Date().toISOString()
            });
            
            // Limpiar campo de texto
            const inputField = document.getElementById('message-send-new');
            if (inputField) {
                inputField.value = '';
            }
        } catch (error) {
            console.error('Error al enviar mensaje por Pusher:', error);
            
            // Fallback a AJAX si Pusher falla
            $.post(site_url + 'playings/messageSubmit', { message: trimmedContent })
                .done((data) => {
                    if (data.status === 'success') {
                        $('#message-send-new').val('');
                    }
                })
                .fail(() => {
                    console.warn('Error al enviar mensaje');
                });
        }
    } else {
        // Fallback a AJAX si Pusher no está disponible
        $.post(site_url + 'playings/messageSubmit', { message: trimmedContent })
            .done((data) => {
                if (data.status === 'success') {
                    $('#message-send-new').val('');
                }
            })
            .fail(() => {
                console.warn('Error al enviar mensaje');
            });
    }
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

// ==========================================
// FUNCIONES PRINCIPALES DEL JUEGO
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

    if (typeof AppcreateConfetti === 'function') {
        AppcreateConfetti();
    } else {
        confettiManager.start();
    }
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
     
    // Actualizar tablero principal
    const boardNumberEl = $id("board-number-" + newNumber);
    if (boardNumberEl) {
        boardNumberEl.classList.add(getColumnClass(newNumber));
    }

    // Actualizar últimos números
    lastNumbers.push(newNumber);
    if (lastNumbers.length > 5) lastNumbers.shift();
    
    // Actualizar UI de últimos números
    const container = $("#last-five-numbers");
    if (container.length) {
        container.empty();
        const latestUncurrent = lastNumbers.slice(0, -1);
        latestUncurrent.forEach(num => {
            container.append(`<div class="bingo-ball ${getColumnClass(num)} size-40"><span>${num}</span></div>`);
        });
    }
    
    // Actualizar bola principal
    const lastNumberEl = $('#last-number');
    if (lastNumberEl.length) {
        lastNumberEl.html(`<small style="position: absolute; top: -13px; font-size: 1.2rem; z-index: 1;">${getColumnClass(newNumber)}</small><span>${newNumber}</span>`)
            .removeClass()
            .addClass(`bingo-ball ${getColumnClass(newNumber)} size-100`);
    }

    // Reproducir narración si está activada
    if (narrationPlaying) {
        audioManager.play(audioPath + newNumber + '.mp3');
    }

    // Auto-marcar si está habilitado
    if (autoMarkEnabled) {
        dialNumber(newNumber);
    }
}

function showGameFinalized(message = 'JUEGO FINALIZADO!') {
    if (isGameFinishedShown) return;
    isGameFinishedShown = true;
    
    const container = $id('game-finalized');
    const text = $id('finalized');
    
    if (container && text) {
        container.style.display = 'block';
        text.innerHTML = message || __['game finished!'] || 'JUEGO FINALIZADO!';
        
        setTimeout(() => {
            if (typeof awardsGet === 'function') {
                awardsGet();
            }
            container.style.display = 'none';
        }, 5000);
    }

    // Limpiar todos los intervalos y recursos
    intervalManager.clearAll();

    // Deshabilitar controles
    const controlsDiv = $id('controls');
    if (controlsDiv) {
        controlsDiv.remove();
    }
    
    // Deshabilitar botón de bingo
    $('.btn-bingooo').prop('disabled', true);
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
            }
        },
        error: function(xhr, status, error) {
            console.error("Error en AJAX al marcar número:", number, error);
        }
    });
}

// Función optimizada para cantar bingo usando Pusher
function singBingo() {
    // Si ya hay un bingo en progreso, no permitir cantar otro
    if (bingoInProgress) {
        alert(__['please wait until the current bingo is verified'] || 'Por favor espera mientras se verifica el bingo actual');
        return;
    }
    
    const bingoButton = document.querySelector('.btn-bingooo');
    if (bingoButton) {
        bingoButton.classList.remove('animate-click');
        void bingoButton.offsetWidth;
        bingoButton.classList.add('animate-click');
        bingoButton.disabled = true;
    }

    $.ajax({
        url: site_url + 'playings/singBingo',
        method: 'POST',
        success: function(data) {
            if (data.status === 'success') {
                // Marcar que hay un bingo en progreso
                bingoInProgress = true;
                
                // Enviar mensaje de bingo al chat
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
                }, () => {
                    bingoInProgress = false;
                    if (bingoButton) bingoButton.disabled = false;
                });
            } else {
                if (bingoButton) bingoButton.disabled = false;
                alert(data.message || 'No se pudo cantar bingo');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error al cantar bingo:", error);
            if (bingoButton) bingoButton.disabled = false;
        }
    });
}

// Funciones de audio y preferencias
function RemoveVolume() {
    soundEnabled = !soundEnabled;
    
    $.ajax({
        url: site_url + 'playings/volumeSubmit',
        method: 'POST',
        success: function(data) {
            if (data.status === 'success') {
                // Actualizar UI
                const volumeBtn = document.querySelector('.btn-volume i');
                if (volumeBtn) {
                    volumeBtn.classList.toggle('fa-volume');
                    volumeBtn.classList.toggle('fa-volume-slash');
                }
            }
        },
        error: function() {
            console.warn("Error al cambiar configuración de sonido");
        }
    });
}

function RemoveMicrophone() {
    narrationPlaying = !narrationPlaying;
    
    $.ajax({
        url: site_url + 'playings/microphoneSubmit',
        method: 'POST',
        success: function(data) {
            if (data.status === 'success') {
                // Actualizar UI
                const micBtn = document.querySelector('.btn-microphone i');
                if (micBtn) {
                    micBtn.classList.toggle('fa-microphone');
                    micBtn.classList.toggle('fa-microphone-slash');
                }
            }
        },
        error: function() {
            console.warn("Error al cambiar configuración de narración");
        }
    });
}

function RemoveCheck() {
    autoMarkEnabled = !autoMarkEnabled;
    
    $.ajax({
        url: site_url + 'playings/checkSubmit',
        method: 'POST',
        success: function(data) {
            if (data.status === 'success') {
                // Actualizar UI
                const checkBtn = document.querySelector('.btn-binary i');
                if (checkBtn) {
                    checkBtn.classList.toggle('fa-binary-circle-check');
                    checkBtn.classList.toggle('fa-binary-slash');
                }
            }
        },
        error: function() {
            console.warn("Error al cambiar configuración de marcado automático");
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
    $('.btn-microphone').on('click', RemoveMicrophone);

    // Control de auto-marcado
    $('.btn-binary').on('click', RemoveCheck);

    // Control de sonido
    $('.btn-volume').on('click', RemoveVolume);

    // Click en números del cartón
    $(".bingo-carton-number").on('click', function() {
        if (!autoMarkEnabled) {
            const number = $(this).data('number'); 
            if (number) {
                dialNumber(number);
            }
        }
    });

    // Botón de cantar bingo
    $('.btn-bingooo').on('click', singBingo);

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
    const container = document.querySelector(".cartons-section");
    const cartons = document.querySelectorAll('.bingo-carton');

    if (!container || !cartons.length) return;

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

    function updateCountdown() {
        const now = new Date();
        const timeDiff = targetDate - now;

        if (timeDiff <= 0) {
            clearInterval(intervalNextGame);
            intervalNextGame = null;

            if (winners.length > 0) {
                startWinnerSlider();
            } else {
                nextGameSpan.textContent = '¡EL JUEGO HA INICIADO!';
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
        if (winners.length > 0) {
            startWinnerSlider();
        } else {
            nextGameSpan.textContent = '¡EL JUEGO HA INICIADO!';
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
        
        // Detener Pusher
        if (pusherClient) {
            if (pusherChannel) {
                pusherClient.unsubscribe('private-game-' + window.GAME_ID);
            }
            pusherClient.disconnect();
        }
        
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
        
        // Reiniciar Pusher si está desconectado
        if (pusherClient && pusherClient.connection.state !== 'connected') {
            console.log('Reiniciando conexión Pusher...');
            initPusher();
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
// INICIALIZACIÓN PRINCIPAL
// ==========================================
const resourceManager = new ResourceManager();

// Función de inicialización principal
function initializeApp() {
    console.log('Initializing Bingo App with Pusher...');
    
    // Inicializar gestor de recursos
    resourceManager.initialize();
    
    // Inicializar Pusher
    initPusher();
    
    // Configurar eventos
    setupEvents();
    
    // Configurar scroll mask
    setupScrollMask();
    
    // Configurar layout de cartones
    setupCartonLayout();
    
    // Configurar countdown del juego
    setupGameCountdown();
    
    // Sincronizar con el estado actual del juego
    $.get(site_url + 'playings/getInitialGameState')
        .done(data => {
            if (data.status === 'success') {
                console.log('Estado inicial del juego:', data);
                
                // Actualizar números generados
                if (data.drawnNumbers && data.drawnNumbers.length > 0) {
                    numbersgenerated = data.drawnNumbers;
                    lastNumbers = data.drawnNumbers.slice(-5);
                    
                    // Actualizar UI con los números ya generados
                    data.drawnNumbers.forEach(num => {
                        const boardNumberEl = $id("board-number-" + num);
                        if (boardNumberEl) {
                            boardNumberEl.classList.add(getColumnClass(num));
                        }
                        
                        // Marcar automáticamente si está habilitado
                        if (autoMarkEnabled) {
                            dialNumber(num);
                        }
                    });
                    
                    // Actualizar contador de bolas
                    updateBallsCounter(data.drawnNumbers.length);
                    
                    // Actualizar última bola
                    if (data.drawnNumbers.length > 0) {
                        const lastNumber = data.drawnNumbers[data.drawnNumbers.length - 1];
                        const lastNumberEl = $('#last-number');
                        if (lastNumberEl.length) {
                            lastNumberEl.html(`<small style="position: absolute; top: -13px; font-size: 1.2rem; z-index: 1;">${getColumnClass(lastNumber)}</small><span>${lastNumber}</span>`)
                                .removeClass()
                                .addClass(`bingo-ball ${getColumnClass(lastNumber)} size-100`);
                        }
                    }
                }
                
                // Actualizar ganadores
                if (data.winners && data.winners.length > 0) {
                    winners = data.winners;
                    startWinnerSlider();
                }
                
                // Verificar si el juego está finalizado
                if (data.isGameFinished) {
                    showGameFinalized(data.finishMessage);
                }
            }
        })
        .fail(error => {
            console.error('Error al obtener estado inicial del juego:', error);
        });
    
    console.log('Bingo App initialized successfully');
}

// ==========================================
// EVENT LISTENERS PRINCIPALES
// ==========================================

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', initializeApp);

// Manejo de errores globales
window.addEventListener('error', (event) => {
    console.error('Global error:', event.error);
});

// Manejo de promesas rechazadas
window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled promise rejection:', event.reason);
});

// Optimización para cambios de orientación en móviles
window.addEventListener('orientationchange', debounce(() => {
    // Recalcular elementos que dependen del viewport
    confettiManager.resize();
    
    // Forzar recálculo de máscaras de scroll
    setTimeout(() => {
        const container = document.querySelector(".cartons-section");
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
    singBingo,
    dialNumber,

    // Funciones del chat mejorado
    displayMessage,
    
    // Gestores
    intervalManager,
    audioManager,
    resourceManager,
    confettiManager,
    messagePool,
    
    // Estado
    get winners() { return winners; },
    get numbersGenerated() { return numbersgenerated; },
    get isGameFinished() { return isGameFinishedShown; },
    get messagesDisplayed() { return messagesDisplayed; }
};

console.log('Bingo App script loaded successfully');
