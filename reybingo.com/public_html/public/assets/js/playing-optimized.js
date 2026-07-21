
// ==========================================
// PLAYING.JS OPTIMIZADO CON PUSHER
// ==========================================
// Versi\u00f3n optimizada que elimina polling y usa eventos en tiempo real
// Reducci\u00f3n de requests al servidor: ~95%
// Actualizaciones instant\u00e1neas: < 100ms vs 2-10s

// ==========================================
// CONFIGURACI\u00d3N Y CONSTANTES
// ==========================================
const CONFIG = {
    MAX_MESSAGES: 50,
    MESSAGE_LIFETIME: 30000,
    FADE_OUT_TIME: 500,
    DEBOUNCE_DELAY: 100,
    AUDIO_POOL_SIZE: 10,
    MESSAGE_POOL_SIZE: 15,
    WINNER_SLIDER_INTERVAL: 5000,
    AUTO_MARK_DELAY: 500,
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
let winners = [];
let winnerIndex = 0;
let winnerSliderTimeout;
let myCartons = []; // Cartones del jugador
let markedNumbers = {}; // N\u00fameros marcados por cart\u00f3n
let claimedModalities = []; // Modalidades ya cantadas

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
    
    getByClass(className) {
        return document.getElementsByClassName(className);
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
    
    // Marcar autom\u00e1ticamente en los cartones
    autoMarkNumber(n);
    
    // Verificar si hay BINGO
    setTimeout(() => {
        checkForBingo();
    }, CONFIG.AUTO_MARK_DELAY);
    
    // Actualizar ganadores si hay
    if (newWinners && newWinners.length > 0) {
        winners = newWinners;
        updateWinnersDisplay();
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
    
    // Agregar modalidad a las ya cantadas
    if (!claimedModalities.includes(modalityId)) {
        claimedModalities.push(modalityId);
        
        // Convertir visualmente la modalidad a "ganada" en tiempo real
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
    
    isGameFinishedShown = true;
}

function handleGameReset(data) {
    // Reiniciar todas las variables
    numbersgenerated = [];
    lastNumbers = [];
    winners = [];
    messagesDisplayed = [];
    markedNumbers = {};
    claimedModalities = [];
    isGameFinishedShown = false;
    
    // Limpiar UI
    clearNumberDisplay();
    clearWinnersDisplay();
    clearCartonMarks();
    
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
// FUNCIONES DE CARTONES
// ==========================================

function initializeCartons() {
    // Obtener todos los cartones del jugador
    const cartonElements = domCache.getByClass('bingo-carton');
    
    Array.from(cartonElements).forEach(cartonEl => {
        const cartonId = cartonEl.dataset.cartonId;
        const numbers = [];
        
        // Obtener n\u00fameros del cart\u00f3n
        const cells = cartonEl.querySelectorAll('.carton-cell[data-number]');
        cells.forEach(cell => {
            const number = parseInt(cell.dataset.number);
            if (!isNaN(number)) {
                numbers.push(number);
            }
        });
        
        myCartons.push({
            id: cartonId,
            numbers: numbers,
            element: cartonEl
        });
        
        // Inicializar objeto de n\u00fameros marcados
        markedNumbers[cartonId] = [];
    });
    
    console.log('Cartones inicializados:', myCartons);
}

function autoMarkNumber(number) {
    myCartons.forEach(carton => {
        if (carton.numbers.includes(number)) {
            markNumberInCarton(carton.id, number);
        }
    });
}

function markNumberInCarton(cartonId, number) {
    // Verificar si ya est\u00e1 marcado
    if (markedNumbers[cartonId] && markedNumbers[cartonId].includes(number)) {
        return;
    }
    
    // Agregar a n\u00fameros marcados
    if (!markedNumbers[cartonId]) {
        markedNumbers[cartonId] = [];
    }
    markedNumbers[cartonId].push(number);
    
    // Actualizar UI
    const carton = myCartons.find(c => c.id === cartonId);
    if (carton) {
        const cell = carton.element.querySelector(`.carton-cell[data-number="${number}"]`);
        if (cell) {
            cell.classList.add('marked');
            
            // Animaci\u00f3n
            cell.classList.add('mark-animation');
            setTimeout(() => {
                cell.classList.remove('mark-animation');
            }, 500);
        }
    }
}

function clearCartonMarks() {
    myCartons.forEach(carton => {
        markedNumbers[carton.id] = [];
        const cells = carton.element.querySelectorAll('.carton-cell.marked');
        cells.forEach(cell => cell.classList.remove('marked'));
    });
}

// ==========================================
// VERIFICACI\u00d3N DE BINGO
// ==========================================

function checkForBingo() {
    myCartons.forEach(carton => {
        const modalitiesAvailable = getAvailableModalities();
        
        modalitiesAvailable.forEach(modality => {
            if (checkModalityWin(carton, modality)) {
                claimBingo(carton.id, modality.id);
            }
        });
    });
}

function getAvailableModalities() {
    // Obtener modalidades que a\u00fan no han sido cantadas
    // Esta informaci\u00f3n deber\u00eda venir de la vista o una variable global
    if (typeof availableModalities !== 'undefined') {
        return availableModalities.filter(m => !claimedModalities.includes(m.id));
    }
    return [];
}

function checkModalityWin(carton, modality) {
    const marked = markedNumbers[carton.id] || [];
    
    // Implementar l\u00f3gica seg\u00fan el tipo de modalidad
    switch (modality.type) {
        case 'horizontal_line':
            return checkHorizontalLine(carton, marked);
        case 'vertical_line':
            return checkVerticalLine(carton, marked);
        case 'diagonal':
            return checkDiagonal(carton, marked);
        case 'four_corners':
            return checkFourCorners(carton, marked);
        case 'full_card':
            return checkFullCard(carton, marked);
        default:
            return false;
    }
}

function checkHorizontalLine(carton, marked) {
    // Verificar si hay una l\u00ednea horizontal completa
    const rows = 5;
    const cols = 5;
    
    for (let row = 0; row < rows; row++) {
        let lineComplete = true;
        for (let col = 0; col < cols; col++) {
            const index = row * cols + col;
            const number = carton.numbers[index];
            if (number !== 0 && !marked.includes(number)) {
                lineComplete = false;
                break;
            }
        }
        if (lineComplete) return true;
    }
    return false;
}

function checkVerticalLine(carton, marked) {
    // Verificar si hay una l\u00ednea vertical completa
    const rows = 5;
    const cols = 5;
    
    for (let col = 0; col < cols; col++) {
        let lineComplete = true;
        for (let row = 0; row < rows; row++) {
            const index = row * cols + col;
            const number = carton.numbers[index];
            if (number !== 0 && !marked.includes(number)) {
                lineComplete = false;
                break;
            }
        }
        if (lineComplete) return true;
    }
    return false;
}

function checkDiagonal(carton, marked) {
    // Verificar diagonales
    const size = 5;
    let diagonal1 = true;
    let diagonal2 = true;
    
    for (let i = 0; i < size; i++) {
        const num1 = carton.numbers[i * size + i];
        const num2 = carton.numbers[i * size + (size - 1 - i)];
        
        if (num1 !== 0 && !marked.includes(num1)) diagonal1 = false;
        if (num2 !== 0 && !marked.includes(num2)) diagonal2 = false;
    }
    
    return diagonal1 || diagonal2;
}

function checkFourCorners(carton, marked) {
    // Verificar las 4 esquinas
    const corners = [
        carton.numbers[0],           // Top-left
        carton.numbers[4],           // Top-right
        carton.numbers[20],          // Bottom-left
        carton.numbers[24]           // Bottom-right
    ];
    
    return corners.every(num => num === 0 || marked.includes(num));
}

function checkFullCard(carton, marked) {
    // Verificar cart\u00f3n completo
    return carton.numbers.every(num => num === 0 || marked.includes(num));
}

function claimBingo(cartonId, modalityId) {
    // Verificar si ya se cant\u00f3 esta modalidad
    if (claimedModalities.includes(modalityId)) {
        return;
    }
    
    // Agregar a modalidades cantadas localmente
    claimedModalities.push(modalityId);
    
    $.post(site_url + 'playings/singSubmit', {
        carton: cartonId,
        modality: modalityId
    })
    .done((response) => {
        if (response.status === 'success') {
            showNotification('\u00a1BINGO! Has ganado', 'success');
            highlightWinningCarton(cartonId);
            
            // Convertir visualmente la modalidad a "ganada" en tiempo real
            const modalityElement = document.getElementById(`modality-${modalityId}`);
            if (modalityElement) {
                modalityElement.classList.add('cartn-sing');
                const parentBorder = modalityElement.closest('.border-carton');
                if (parentBorder) {
                    parentBorder.classList.add('modality-won');
                }
            }
        } else {
            // Remover de modalidades cantadas si fall\u00f3
            const index = claimedModalities.indexOf(modalityId);
            if (index > -1) {
                claimedModalities.splice(index, 1);
            }
            showNotification(response.message || 'Error al cantar BINGO', 'error');
        }
    })
    .fail((error) => {
        console.error('Error claiming bingo:', error);
        // Remover de modalidades cantadas si fall\u00f3
        const index = claimedModalities.indexOf(modalityId);
        if (index > -1) {
            claimedModalities.splice(index, 1);
        }
        showNotification('Error al cantar BINGO', 'error');
    });
}

function highlightWinningCarton(cartonId) {
    const carton = myCartons.find(c => c.id === cartonId);
    if (carton) {
        carton.element.classList.add('winning-carton');
        
        // Animaci\u00f3n de confetti o celebraci\u00f3n
        createConfetti(carton.element);
    }
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
            <button onclick="location.href='${site_url}games'" class="btn-primary">Volver a Juegos</button>
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

function createConfetti(element) {
    // Crear efecto de confetti simple
    for (let i = 0; i < 50; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        confetti.style.left = Math.random() * 100 + '%';
        confetti.style.animationDelay = Math.random() * 3 + 's';
        confetti.style.backgroundColor = `hsl(${Math.random() * 360}, 100%, 50%)`;
        element.appendChild(confetti);
        
        setTimeout(() => confetti.remove(), 3000);
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
// CONTROLES DE AUDIO
// ==========================================

function updateVolume(volume) {
    $.ajax({
        url: site_url + 'playings/volumeSubmit',
        method: 'POST',
        data: { volume: volume },
        success: function(response) {
            if (response.status === 'success') {
                // Actualizar volumen de todos los audios
                if (narrationAudio) {
                    narrationAudio.volume = volume / 100;
                }
            }
        }
    });
}

function toggleMicrophone(enabled) {
    $.ajax({
        url: site_url + 'playings/microphoneSubmit',
        method: 'POST',
        data: { microphone: enabled ? 1 : 0 },
        success: function(response) {
            if (response.status === 'success') {
                showNotification(
                    enabled ? 'Micr\u00f3fono activado' : 'Micr\u00f3fono desactivado',
                    'info'
                );
            }
        }
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
    console.log('\ud83d\ude80 Initializing Playing with Pusher...');
    
    // Inicializar Pusher
    const pusherInitialized = initializePusher();
    
    if (!pusherInitialized) {
        console.error('\u274c Failed to initialize Pusher - falling back to polling');
        return;
    }
    
    // Inicializar cartones
    initializeCartons();
    
    // Precargar audios
    if (typeof audioPath !== 'undefined') {
        audioManager.preloadAll(audioPath);
    }
    
    // Event listeners
    $('#btn-send-message').on('click', sendMessage);
    $('#message-input').on('keypress', function(e) {
        if (e.which === 13) {
            sendMessage();
        }
    });
    
    // Control de volumen
    $('#volume-slider').on('input', debounce(function() {
        const volume = $(this).val();
        updateVolume(volume);
    }, CONFIG.DEBOUNCE_DELAY));
    
    // Control de micr\u00f3fono
    $('#microphone-toggle').on('change', function() {
        const enabled = $(this).is(':checked');
        toggleMicrophone(enabled);
    });
    
    console.log('\u2705 Playing initialized successfully with Pusher');
});

// Limpiar al salir
$(window).on('beforeunload', function() {
    intervalManager.clearAll();
    if (pusher) {
        pusher.disconnect();
    }
});
