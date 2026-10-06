// ==========================================
// CONFIGURACIÓN Y CONSTANTES
// ==========================================
const CONFIG = {
    MAX_MESSAGES: 50,
    MAX_CONFETTI: 100,
    BASE_POLL_INTERVAL: 3500,
    CHAT_POLL_INTERVAL: 3500,
    MAX_POLL_INTERVAL: 20000,
    LIVE_STATUS_INTERVAL: 20000,
    USER_COUNT_INTERVAL: 20000,
    ACCUMULATED_COUNT_INTERVAL: 20000,
    MESSAGE_LIFETIME: 30000,
    FADE_OUT_TIME: 500,
    DEBOUNCE_DELAY: 100,
    AUDIO_POOL_SIZE: 10,
    MESSAGE_POOL_SIZE: 15,
    WINNER_SLIDER_INTERVAL: 5000,
    COUNTDOWN_INTERVAL: 1000,
    // Con WebSocket (Soketi/Pusher) las balotas llegan por push.
    // Este poll es de sincronización y recuperación al ritmo configurado.
    BALL_POLL_FALLBACK_MS: 10000,
    // Cooldown corto para chat/status.
    WAF_COOLDOWN_MS: 10000,
    BALL_WAF_BACKOFF_MS: 2000
};

// Cooldown secundario (chat/acumulado). NO debe frenar el sync de bolas.
window.__bingoWafCooldownUntil = window.__bingoWafCooldownUntil || 0;
window.__bingoBallBackoffUntil = window.__bingoBallBackoffUntil || 0;

function bingoIsWafCooling() {
    return Date.now() < (window.__bingoWafCooldownUntil || 0);
}

function bingoIsBallBackingOff() {
    return Date.now() < (window.__bingoBallBackoffUntil || 0);
}

function bingoTripWafCooldown(ms) {
    const wait = ms || CONFIG.WAF_COOLDOWN_MS || 10000;
    window.__bingoWafCooldownUntil = Date.now() + wait;
    console.warn('CDN/WAF 403: pausando polls secundarios ~' + Math.round(wait / 1000) + 's (bolas siguen)');
}

function bingoTripBallBackoff(ms) {
    const wait = ms || CONFIG.BALL_WAF_BACKOFF_MS || 2000;
    window.__bingoBallBackoffUntil = Math.max(window.__bingoBallBackoffUntil || 0, Date.now() + wait);
    console.warn('403 en bolas: reintento en ~' + Math.round(wait / 1000) + 's');
}

if (typeof $ !== 'undefined' && !window.__bingoAjaxWafHook) {
    window.__bingoAjaxWafHook = true;
    $(document).ajaxComplete(function (_event, xhr, settings) {
        if (!xhr || xhr.status !== 403) {
            return;
        }
        const url = String((settings && settings.url) || '');
        // Bolas: backoff corto. Resto: cooldown secundario.
        if (/numberGet|numberSubmit|numberAutoSubmit|dialNumber/i.test(url)) {
            bingoTripBallBackoff();
        } else {
            bingoTripWafCooldown();
        }
    });
}

// ==========================================
// VARIABLES GLOBALES
// ==========================================
// Silenciar y detener de inmediato cualquier música de fondo al jugar
try {
    if (window.__bingoSoundtrack) {
        window.__bingoSoundtrack.pause();
        window.__bingoSoundtrack.currentTime = 0;
        window.__bingoSoundtrack.src = '';
        window.__bingoSoundtrack = null;
    }
    window.startBingoSoundtrack = function () { };
    if (typeof window.stopBingoSoundtrack === 'function') {
        window.stopBingoSoundtrack();
    }
} catch (e) { /* ignore */ }

let numbersgenerated = [];
let lastNumbers = (typeof fiveNumbers !== 'undefined' && Array.isArray(fiveNumbers)) ? fiveNumbers : (Array.isArray(window.fiveNumbers) ? window.fiveNumbers : []);

// Única fuente de verdad para el audio en el cliente, persistente en móviles y sincronizada con el servidor
window.audioSettings = window.audioSettings || {
    soundEnabled: (function () {
        try {
            const local = localStorage.getItem('reybingo_sound');
            if (local !== null) return local === '1';
        } catch (e) { }
        if (typeof window.soundPlaying !== 'undefined') return window.soundPlaying;
        const input = document.getElementById('sounds');
        if (input && input.value !== '') return input.value === '1';
        return true;
    })(),
    narrationEnabled: (function () {
        try {
            const local = localStorage.getItem('reybingo_narration');
            if (local !== null) return local === '1';
        } catch (e) { }
        if (typeof window.narrationPlaying !== 'undefined') return window.narrationPlaying;
        const input = document.getElementById('narration');
        if (input && input.value !== '') return input.value === '1';
        return true;
    })(),
    unlocked: false
};

window.soundPlaying = window.audioSettings.soundEnabled;
window.narrationPlaying = window.audioSettings.narrationEnabled;
var narrationPlaying = window.narrationPlaying;
var soundPlaying = window.soundPlaying;
var audioPath = (typeof window.audioPath !== 'undefined' && window.audioPath) ? window.audioPath : (typeof audioPath !== 'undefined' && audioPath ? audioPath : '/assets/sounds/');
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

// Gestor inteligente de audio robusto para móviles y conexiones inestables
class AudioManager {
    constructor() {
        this.audioCache = new Map();
        this.preloadedAudios = new Set();
        // Doble canal de locución (ping-pong) para que balotas consecutivas no se aborten
        this.voiceIndex = 0;
        this.voiceAudios = [null, null];
        this.effectAudio = null;
        this.audioCtx = null;
        this._unlocked = false;
        this._promptShown = false;
        if ('speechSynthesis' in window) {
            try {
                window.speechSynthesis.onvoiceschanged = () => {
                    try { window.speechSynthesis.getVoices(); } catch (e) {}
                };
                window.speechSynthesis.getVoices();
            } catch (e) {}
        }
        try {
            this.unlockAudio();
        } catch (e) {}
    }

    initAudioContext() {
        if (!this.audioCtx) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                try {
                    this.audioCtx = new AudioContext();
                } catch (e) {}
            }
        }
    }

    resumeContext() {
        if (this.audioCtx && this.audioCtx.state === 'suspended') {
            this.audioCtx.resume().catch(() => {});
        }
    }

    unlockAudio() {
        if (this._unlocked) return;
        this.initAudioContext();
        this.resumeContext();

        const silentWav = 'data:audio/wav;base64,UklGRigAAABXQVZFZm10IBIAAAABAAEARKwAAIhYAQACABAAAABkYXRhAgAAAAEA';

        try {
            if (this.audioCtx && this.audioCtx.state === 'suspended') {
                this.audioCtx.resume().catch(() => {});
            }
            if (this.audioCtx) {
                const buffer = this.audioCtx.createBuffer(1, 1, 22050);
                const source = this.audioCtx.createBufferSource();
                source.buffer = buffer;
                source.connect(this.audioCtx.destination);
                source.start(0);
            }
        } catch (e) {}

        try {
            for (let i = 0; i < 2; i++) {
                if (!this.voiceAudios[i]) {
                    this.voiceAudios[i] = new Audio();
                    this.voiceAudios[i].preload = 'auto';
                }
                this.voiceAudios[i].src = silentWav;
                const p = this.voiceAudios[i].play();
                if (p && typeof p.then === 'function') {
                    p.then(() => {
                        this.voiceAudios[i].pause();
                        this.voiceAudios[i].currentTime = 0;
                    }).catch(() => {});
                }
            }

            if (!this.effectAudio) {
                this.effectAudio = new Audio();
            }
            this.effectAudio.src = silentWav;
            const pe = this.effectAudio.play();
            if (pe && typeof pe.then === 'function') {
                pe.then(() => {
                    this.effectAudio.pause();
                    this.effectAudio.currentTime = 0;
                }).catch(() => {});
            }

            this._unlocked = true;
            this.hideAudioPrompt();
        } catch (e) {}
    }

    showAudioPrompt() {
        // Desactivado prompt visual; el sonido se activa automáticamente
        this.unlockAudio();
    }

    hideAudioPrompt() {
        const el = document.getElementById('bingo-audio-unlock-prompt');
        if (el && el.parentNode) {
            el.parentNode.removeChild(el);
        }
        this._promptShown = false;
    }

    preload(src) {
        if (this.preloadedAudios.has(src)) return;

        const audio = new Audio();
        audio.preload = 'auto';
        audio.src = src;
        this.audioCache.set(src, audio);
        this.preloadedAudios.add(src);
    }

    stopCurrentVoice() {
        for (let i = 0; i < 2; i++) {
            if (this.voiceAudios[i]) {
                try {
                    this.voiceAudios[i].pause();
                    this.voiceAudios[i].currentTime = 0;
                    this.voiceAudios[i].onplay = null;
                    this.voiceAudios[i].onended = null;
                    this.voiceAudios[i].onerror = null;
                } catch (e) {}
            }
        }
        if ('speechSynthesis' in window) {
            try { window.speechSynthesis.cancel(); } catch (e) {}
        }
    }

    playVoice(src, ballNumber) {
        // Respetar estado unificado y persistente de locución
        const isNarrationOn = (window.audioSettings && typeof window.audioSettings.narrationEnabled !== 'undefined')
            ? window.audioSettings.narrationEnabled
            : ((typeof narrationPlaying !== 'undefined') ? narrationPlaying : true);
        if (!isNarrationOn) {
            return null;
        }

        const num = ballNumber || (src.match(/\/(\d+)\.mp3/i) ? src.match(/\/(\d+)\.mp3/i)[1] : null);

        this.initAudioContext();
        this.resumeContext();

        // Detener de inmediato cualquier locución previa (MP3 o síntesis) para evitar voces encimadas (doble audio)
        this.stopCurrentVoice();

        // Alternar entre voiceAudios[0] y voiceAudios[1] para evitar AbortError si balotas se suceden rápido
        this.voiceIndex = (this.voiceIndex + 1) % 2;
        if (!this.voiceAudios[this.voiceIndex]) {
            this.voiceAudios[this.voiceIndex] = new Audio();
            this.voiceAudios[this.voiceIndex].preload = 'auto';
        }

        const audio = this.voiceAudios[this.voiceIndex];
        let hasSpokenFallback = false;

        const speakFallback = () => {
            if (hasSpokenFallback) return;
            hasSpokenFallback = true;
            clearTimeout(networkTimeout);
            try {
                audio.pause();
                audio.currentTime = 0;
            } catch (e) {}
            if (num) {
                const spoken = this.speakOfflineNumber(num);
                // Si no se pudo sintetizar voz femenina (porque no hay voz de mujer instalada en el dispositivo),
                // NUNCA hablar con voz de hombre: reintentar la reproducción del audio grabado femenino original
                if (!spoken && src) {
                    try {
                        const retryAudio = new Audio(src);
                        retryAudio.volume = 1.0;
                        retryAudio.onended = () => { retryAudio.src = ''; };
                        const rp = retryAudio.play();
                        if (rp && typeof rp.catch === 'function') {
                            rp.catch(() => {});
                        }
                    } catch (e) {}
                }
            }
        };

        // Watchdog de red: dar tiempo prudencial para descargar el MP3 grabado original (voz femenina)
        // antes de recurrir a la síntesis de voz femenina.
        const networkTimeout = setTimeout(() => {
            if (audio.readyState < 2 && (audio.paused || audio.currentTime === 0)) {
                speakFallback();
            }
        }, 3500);

        audio.onplay = () => {
            clearTimeout(networkTimeout);
            hasSpokenFallback = true;
            this._unlocked = true;
            if (window.audioSettings) window.audioSettings.unlocked = true;
            if ('speechSynthesis' in window) {
                try {
                    window.speechSynthesis.cancel();
                } catch (e) {}
            }
        };

        audio.onended = () => {
            clearTimeout(networkTimeout);
            audio.onplay = null;
            audio.onended = null;
            audio.onerror = null;
        };

        audio.onerror = (err) => {
            clearTimeout(networkTimeout);
            audio.onplay = null;
            audio.onerror = null;
            if (!hasSpokenFallback) {
                speakFallback();
            }
        };

        try {
            audio.src = src;
            audio.volume = 1.0;
            const p = audio.play();
            if (p && typeof p.catch === 'function') {
                p.catch(e => {
                    clearTimeout(networkTimeout);
                    if (e.name === 'NotAllowedError') {
                        // El navegador móvil bloqueó el autoplay sin interacción previa:
                        // Guardar la balota pendiente para reproducirla al primer toque, SIN apagar la configuración del usuario
                        window.pendingBallAudioSrc = src;
                        window.pendingBallNumber = num;
                        this._unlocked = false;
                        if (window.audioSettings) window.audioSettings.unlocked = false;
                        updateVolumeButtonIcon(window.audioSettings.soundEnabled);
                        updateMicrophoneButtonIcon(window.audioSettings.narrationEnabled);
                    } else if (e.name !== 'AbortError') {
                        speakFallback();
                    }
                });
            }
        } catch (e) {
            clearTimeout(networkTimeout);
            speakFallback();
        }

        return audio;
    }

    getFemaleSpanishVoice() {
        if (!('speechSynthesis' in window)) return null;

        const voices = window.speechSynthesis.getVoices() || [];
        if (!Array.isArray(voices) || voices.length === 0) return null;

        // Lista estricta de nombres/patrones masculinos que NUNCA deben usarse
        const malePatterns = [
            'pablo', 'raul', 'raúl', 'david', 'jorge', 'diego', 'carlos', 'miguel',
            'alvaro', 'álvaro', 'gonzalo', 'enrique', 'mateo', 'alejandro', 'antonio',
            'manuel', 'pedro', 'javier', 'andres', 'andrés', 'fernando', 'jose',
            'josé', 'juan', 'luis', 'sergio', 'victor', 'víctor', 'julio', 'cesar',
            'césar', 'mario', 'guillermo', 'rodrigo', 'ricardo', 'eduardo', 'tomas',
            'tomás', 'ignacio', 'esteban', 'santiago', 'felipe', 'marcos', 'lucas',
            'hugo', 'martin', 'martín', 'alberto', 'emilio', 'male', 'hombre',
            'guy', 'boy', 'man', 'masculin', 'varon', 'varón'
        ];

        // Lista de nombres/patrones explícitamente femeninos
        const femalePatterns = [
            'helena', 'elena', 'sabina', 'laura', 'monica', 'mónica', 'paulina',
            'lucia', 'lucía', 'carmen', 'rosa', 'sofia', 'sofía', 'marisol',
            'luciana', 'zira', 'dalia', 'elvira', 'paloma', 'conchita', 'lupe',
            'ines', 'inés', 'soledad', 'maria', 'maría', 'victoria', 'valeria',
            'claudia', 'camila', 'mia', 'mía', 'martina', 'catalina', 'isabella',
            'abril', 'alicia', 'ana', 'eva', 'juana', 'francisca', 'ximena',
            'jimena', 'penelope', 'penélope', 'sandra', 'patricia', 'silvia',
            'teresa', 'irene', 'raquel', 'esther', 'rocio', 'rocío', 'alba',
            'angelica', 'angélica', 'carlota', 'salome', 'salomé', 'lola', 'pilar',
            'female', 'mujer', 'chica', 'feminin', 'femenin', 'woman', 'girl'
        ];

        const isMale = (v) => {
            if (!v) return true;
            if (v.gender && String(v.gender).toLowerCase() === 'male') return true;
            const fullStr = ((v.name || '') + ' ' + (v.voiceURI || '')).toLowerCase();
            return malePatterns.some(p => {
                const regex = new RegExp('(?:^|[^a-záéíóúñ])' + p + '(?:$|[^a-záéíóúñ])', 'i');
                return regex.test(fullStr);
            });
        };

        const isFemale = (v) => {
            if (!v) return false;
            if (v.gender && String(v.gender).toLowerCase() === 'female') return true;
            const fullStr = ((v.name || '') + ' ' + (v.voiceURI || '')).toLowerCase();
            return femalePatterns.some(p => {
                const regex = new RegExp('(?:^|[^a-záéíóúñ])' + p + '(?:$|[^a-záéíóúñ])', 'i');
                return regex.test(fullStr);
            });
        };

        // Filtrar voces en español (es, es-ES, es-MX, es-US, es-CO, etc.)
        const esVoices = voices.filter(v => v && v.lang && v.lang.toLowerCase().startsWith('es'));
        if (esVoices.length === 0) return null;

        // 1. Prioridad: Voz en español confirmada como femenina y libre de nombres masculinos
        const explicitFemale = esVoices.find(v => isFemale(v) && !isMale(v));
        if (explicitFemale) {
            return { voice: explicitFemale, isExplicit: true };
        }

        // 2. Segunda opción: Voz en español neutra (NO masculina bajo ninguna circunstancia)
        const neutralEs = esVoices.find(v => !isMale(v));
        if (neutralEs) {
            return { voice: neutralEs, isExplicit: false };
        }

        // 3. Si todas las voces en español son de hombre (ej. sólo Pablo o Raúl instalados):
        // NUNCA DEVOLVER VOZ MASCULINA.
        return null;
    }

    speakOfflineNumber(number) {
        try {
            if (!('speechSynthesis' in window)) return false;

            const n = parseInt(number, 10);
            if (!n || isNaN(n)) return false;

            const femaleVoiceData = this.getFemaleSpanishVoice();
            // REGLA ESTRICTA: El sistema de Rey Bingo SOLO utiliza locución femenina.
            // Si el dispositivo/navegador no dispone de una voz femenina verificada,
            // se cancela cualquier síntesis para evitar que el sistema operativo reproduzca
            // una voz masculina por defecto.
            if (!femaleVoiceData || !femaleVoiceData.voice) {
                console.warn('Dispositivo sin voz femenina en español disponible. Se omite síntesis para evitar voz masculina.');
                return false;
            }

            const letter = typeof getColumnClass === 'function' ? getColumnClass(n) : '';
            const textToSay = letter ? `${letter}, ${n}` : `${n}`;

            window.speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(textToSay);
            utterance.voice = femaleVoiceData.voice;
            utterance.lang = femaleVoiceData.voice.lang || 'es-ES';
            // Ajustar tono: si es explícitamente femenina, tono 1.1; si es neutra, elevar a 1.28 para asegurar timbre femenino
            utterance.pitch = femaleVoiceData.isExplicit ? 1.1 : 1.28;
            utterance.rate = 1.05;
            utterance.volume = 1.0;

            window.speechSynthesis.speak(utterance);
            return true;
        } catch (e) {
            console.warn('speechSynthesis fallback error:', e);
            return false;
        }
    }

    playSoft(src) {
        const isSoundOn = (window.audioSettings && typeof window.audioSettings.soundEnabled !== 'undefined')
            ? window.audioSettings.soundEnabled
            : ((typeof soundPlaying !== 'undefined') ? soundPlaying : true);
        if (!isSoundOn) return null;

        try {
            if (!this.effectAudio) {
                this.effectAudio = new Audio();
            }
            this.effectAudio.src = src;
            this.effectAudio.currentTime = 0;
            this.effectAudio.volume = 0.35; // Volumen suave
            const p = this.effectAudio.play();
            if (p && typeof p.catch === 'function') {
                p.catch(() => {});
            }
            return this.effectAudio;
        } catch (e) {
            console.warn('Soft audio play failed:', e);
        }
    }

    play(src, ballNumber) {
        // Para balotas cantadas (/assets/sounds/XX.mp3), usar reproductor de voz con fallback inteligente
        if (/\/\d+\.mp3/i.test(src) || ballNumber) {
            return this.playVoice(src, ballNumber);
        }

        // Para efectos de sonido (botones, ganador, etc.), respetar soundEnabled
        const isSoundOn = (window.audioSettings && typeof window.audioSettings.soundEnabled !== 'undefined')
            ? window.audioSettings.soundEnabled
            : ((typeof soundPlaying !== 'undefined') ? soundPlaying : true);
        if (!isSoundOn) {
            return null;
        }

        let audio = this.audioCache.get(src);
        if (!audio) {
            audio = new Audio();
            audio.src = src;
            audio.preload = 'auto';
            this.audioCache.set(src, audio);
        }

        if (audio.paused || audio.ended) {
            audio.currentTime = 0;
            audio.play().catch(e => {
                if (e.name === 'NotAllowedError') {
                    window.pendingBallAudioSrc = src;
                    this.unlockAudio();
                }
            });
            return audio;
        }

        const audioClone = audio.cloneNode();
        audioClone.play().catch(() => {});
        audioClone.onended = function () {
            audioClone.src = '';
            audioClone.onended = null;
        };
        audioClone.onerror = function () {
            audioClone.src = '';
            audioClone.onerror = null;
        };

        return audioClone;
    }

    stopAll() {
        try {
            for (let i = 0; i < 2; i++) {
                if (this.voiceAudios[i] && !this.voiceAudios[i].paused) {
                    this.voiceAudios[i].pause();
                    this.voiceAudios[i].currentTime = 0;
                }
            }
            if (this.effectAudio && !this.effectAudio.paused) {
                this.effectAudio.pause();
                this.effectAudio.currentTime = 0;
            }
            this.audioCache.forEach(audio => {
                if (audio && !audio.paused) {
                    audio.pause();
                    audio.currentTime = 0;
                }
            });
            window.pendingBallAudioSrc = null;
            window.pendingBallNumber = null;
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
            }
        } catch (e) {}
    }

    preloadNumberAudios() {
        const basePath = (typeof audioPath !== 'undefined' && audioPath) ? audioPath : (window.audioPath || '/assets/sounds/');
        this.preload(basePath + 'winner.mp3');
    }
}

// Desbloqueo proactivo de audio en el primer clic o toque en cualquier parte de la pantalla
window.pendingBallAudioSrc = null;
window.pendingBallNumber = null;
function unlockUserAudioGesture() {
    if (typeof audioManager !== 'undefined' && audioManager.unlockAudio) {
        audioManager.unlockAudio();
    }
    const isNarrationOn = (window.audioSettings && typeof window.audioSettings.narrationEnabled !== 'undefined')
        ? window.audioSettings.narrationEnabled
        : ((typeof narrationPlaying !== 'undefined') ? narrationPlaying : true);
    if (window.pendingBallAudioSrc && isNarrationOn) {
        const pendingSrc = window.pendingBallAudioSrc;
        const pendingNum = window.pendingBallNumber;
        window.pendingBallAudioSrc = null;
        window.pendingBallNumber = null;
        if (typeof audioManager !== 'undefined' && audioManager.play) {
            audioManager.play(pendingSrc, pendingNum);
        }
    }
}
['click', 'touchstart', 'touchend', 'mousedown', 'mouseup', 'pointerdown', 'pointerup', 'keydown'].forEach(function (eventName) {
    document.addEventListener(eventName, unlockUserAudioGesture, { capture: true, passive: true });
});

// Desbloquear audio automáticamente al ingresar a la partida
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', unlockUserAudioGesture);
} else {
    unlockUserAudioGesture();
}
window.addEventListener('load', unlockUserAudioGesture);

// Carga asíncrona robusta de voces para síntesis en móviles (Android Chrome / iOS Safari)
if ('speechSynthesis' in window) {
    try {
        window.speechSynthesis.getVoices();
        window.speechSynthesis.onvoiceschanged = function () {
            try { window.speechSynthesis.getVoices(); } catch (e) {}
        };
    } catch (e) {}
}

// ==========================================
// TELEMETRÍA Y DIAGNÓSTICO DE LATENCIA DE BALOTAS
// Activable via URL con ?debug=1 o localStorage.getItem('reybingo_debug') === '1'
// ==========================================
window.ballTelemetry = {
    enabled: (function () {
        try {
            return location.search.includes('debug=1') || localStorage.getItem('reybingo_debug') === '1';
        } catch (e) { return false; }
    })(),
    logBallTiming: function (stage, ballNumber, extra) {
        if (!this.enabled) return;
        const now = performance.now();
        const wallTime = Date.now();
        console.log(`[BALL-TIMING] Ball #${ballNumber} | Stage: ${stage} | ClientPerfMs: ${now.toFixed(1)} | Epoch: ${wallTime}`, extra || {});
    }
};

// Reactivación de audio y sincronización inteligente al volver de pestañas en segundo plano o bloqueo de pantalla
document.addEventListener('visibilitychange', function () {
    if (!document.hidden) {
        if (typeof audioManager !== 'undefined') {
            audioManager.resumeContext();
        }
        if (typeof syncGameState === 'function') {
            syncGameState('visibility_visible');
        }
    } else {
        // En background: cancelar locución y detener audio para no consumir batería ni quedar desfasado
        if (typeof audioManager !== 'undefined' && audioManager.stopCurrentVoice) {
            audioManager.stopCurrentVoice();
        }
    }
});

// Resincronización al recuperar foco o conectividad en navegadores móviles
window.addEventListener('pageshow', function () {
    if (typeof syncGameState === 'function') {
        syncGameState('pageshow');
    }
});
window.addEventListener('focus', function () {
    if (typeof syncGameState === 'function') {
        syncGameState('focus');
    }
});
window.addEventListener('online', function () {
    if (typeof syncGameState === 'function') {
        syncGameState('online');
    }
});

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
    return function () {
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

    const winnerUserId = parseInt(data.winnerUserId || data.userId || data.playerId, 10);
    const currentUserId = getCurrentUserId();

    if (winnerUserId > 0 && currentUserId > 0) {
        return winnerUserId === currentUserId;
    }

    return data.isOwnBingo === true;
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
        if (messagesDisplayed.length > 200) {
            messagesDisplayed.splice(0, messagesDisplayed.length - 200);
        }
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
let lastAutoSingNoNew = false;
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

    const lines = winners.map(function (w) {
        return `🎉 ${w.player} — ${w.modality}`;
    }).join('<br>');

    return `${finishedLabel}<br><br>${lines}`;
}

function fetchWinnersBeforeFinalize(callback) {
    const gid = (typeof GAME_ID !== 'undefined' && GAME_ID) ? GAME_ID : (window.gameId || '');
    const url = site_url + 'playings/winnersGet' + (gid ? ('?game_id=' + gid) : '');
    $.get(url)
        .done(function (data) {
            if (data && data.status === 'success' && Array.isArray(data.winners)) {
                mergeWinnersFromServer(data.winners);
            }
        })
        .always(function () {
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

    // Encolar bingos extras del mismo request (varios cartones / modalidades)
    if (Array.isArray(data.sings) && data.sings.length > 1) {
        data.sings.slice(1).forEach(function (extra) {
            simultaneousBingos.push(extra);
        });
    }

    registerWinner(data.player, data.modality);

    if (Array.isArray(data.winners) && data.winners.length) {
        mergeWinnersFromServer(data.winners);
    }

    bingoInProgress = true;
    intervalManager.clear('lastNumber');

    // 1. Reproducir sonido de ganador a todo volumen para el jugador ganador
    try {
        if (typeof audioManager !== 'undefined' && audioManager.play) {
            audioManager.play(audioPath + 'winner.mp3');
        } else if (typeof playNotificationSound === 'function') {
            playNotificationSound('sing', true);
        }
    } catch (e) {
        console.warn('Error al reproducir audio de ganador:', e);
    }

    // 2. Efecto de confeti para el ganador
    if (typeof window.AppcreateConfetti === 'function') {
        window.AppcreateConfetti();
    }

    // 3. Notificación toast visual de victoria propia agrupada por modalidad
    if (typeof window.showNotification === 'function') {
        const modalityClean = (data.modality || 'Bingo').replace(/^la\s+/i, '').trim();
        const modalityLabel = /^bingo/i.test(modalityClean) || /^pleno/i.test(modalityClean)
            ? `Ganadores de la modalidad ${modalityClean}`
            : `Ganadores de la ${modalityClean}`;
        const pName = data.player || 'Tú';

        window.showNotification({
            id: 'own_sing_' + (data.singId || (data.carton || '') + '_' + (data.modalityId || Date.now())),
            type: 'own_sing',
            modalityId: data.modalityId,
            modality: data.modality,
            player: pName,
            cartonId: data.carton,
            title: '🎉 ¡BINGO CANTADO!',
            message: `${modalityLabel}: ${pName}`,
            created_at: new Date().toISOString()
        });
    }

    if (typeof sendEmoji === 'function') {
        sendEmoji('🥳', 21);
    }

    const highlightSing = function (singData) {
        const cartonElement = document.getElementById(`carton-${singData.carton}`);
        if (cartonElement && Array.isArray(singData.numbers)) {
            singData.numbers.forEach((num) => {
                const numberElement = cartonElement.querySelector(`.bingo-carton-number.number-${num}`);
                if (numberElement) {
                    numberElement.classList.add('carton-sing');
                }
            });
        }
    };

    highlightSing(data);
    if (Array.isArray(data.sings)) {
        data.sings.forEach(highlightSing);
    }

    if (data.player && data.modality) {
        registerWinner(data.player, data.modality);
    }
    if (Array.isArray(data.winners)) {
        mergeWinnersFromServer(data.winners);
    }

    const isCompleted = (data.gameCompleted === true || window.gameIsFinished);
    if (isCompleted) {
        window.gameIsFinished = true;
        window.allowGameUnload = true;
    }

    const afterCountdown = function () {
        if (isCompleted || data.gameCompleted || window.gameIsFinished) {
            showGameFinalized();
            return;
        }

        // Tras el anuncio, reintentar auto-canto por si otro cartón también ganó
        scheduleAutoSingCheck(600);

        if (typeof resumeCallback === 'function') {
            resumeCallback();
        } else {
            startAutomaticLast();
        }

        // Si quedaron balotas en cola durante el cante, reanudar de inmediato
        if (ballPlaybackQueue.length > 0 && !isBallPlaybackActive) {
            playNextBallInQueue();
        }
    };

    showCountdown({
        player: data.player,
        modality: data.modality,
        modalityId: data.modalityId,
        image: data.image,
        isOwnBingo: true
    }, afterCountdown);
}

function mergeWinnersFromServer(serverWinners) {
    if (!Array.isArray(serverWinners)) {
        return;
    }

    const limit = parseInt(window.numberSingsLimit, 10) || 1;

    serverWinners.forEach(function (winner) {
        const player = winner.player || '';
        const modality = winner.modality || '';

        if (!player) {
            return;
        }

        const currentModalityWinners = winners.filter(function (existing) {
            return existing.modality === modality;
        });

        const alreadyExists = currentModalityWinners.some(function (existing) {
            return existing.player === player;
        });

        if (!alreadyExists && currentModalityWinners.length < limit) {
            winners.push({ player: player, modality: modality });
        }

        // Apply won styling to modality in real-time
        if (winner.modalityId) {
            const cartns = document.querySelectorAll(`[id="modality-${winner.modalityId}"]`);
            cartns.forEach(cartn => {
                cartn.classList.add('cartn-sing');

                let borderCarton = cartn.parentElement;
                while (borderCarton && !borderCarton.classList.contains('border-carton')) {
                    borderCarton = borderCarton.parentElement;
                }
                if (borderCarton) {
                    borderCarton.classList.add('modality-won');
                }

                cartn.querySelectorAll('.card-number.modality-sing').forEach(el => {
                    el.classList.add('sing');
                    el.innerText = '⭐️';
                });
            });
        }
    });
}

function scheduleAutoSingCheck(delay) {
    if (!isAutoMarkEnabled()) {
        return;
    }

    // Tras marcar números / anunciar bingo, permitir reintentar en la misma bola
    lastAutoSingNoNew = false;

    if (autoSingCheckTimer) {
        clearTimeout(autoSingCheckTimer);
    }

    autoSingCheckTimer = setTimeout(function () {
        autoSingCheckTimer = null;
        autoSingIfComplete();
    }, typeof delay === 'number' ? delay : 450);
}

function autoSingIfComplete() {
    // No bloquear por gameHasWinner: un segundo cartón / otra modalidad debe poder cantar
    if (autoSingInFlight || bingoInProgress || window.gameIsFinished) {
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

    const cartons = Array.from(document.querySelectorAll('.bingo-carton'));
    if (!cartons.length) {
        return;
    }

    const anyFull = cartons.some(isCardWinningPattern);
    if (!anyFull) {
        return;
    }

    // Evitar spam en la misma bola si el intento anterior no registró bingo nuevo
    if (lastAutoSingBall === lastBall && lastAutoSingNoNew) {
        return;
    }

    autoSingInFlight = true;
    lastAutoSingNoNew = false;
    $.post(site_url + 'playings/singBingo', {})
        .done((data) => {
            if (data && data.status === 'success') {
                lastAutoSingBall = lastBall;
                lastAutoSingNoNew = false;
                handleBingoSuccess(data, startAutomaticLast);
            } else {
                lastAutoSingBall = lastBall;
                lastAutoSingNoNew = true;
                if (data && data.gameHasWinner) {
                    window.gameHasWinner = true;
                } else if (data && data.message) {
                    console.warn('autoSingIfComplete:', data.message);
                }
            }
        })
        .always(() => {
            autoSingInFlight = false;
        });
}

// Polling de chat (mensajes de otros jugadores / admin en la misma partida)
function pollMessagesOptimized() {
    return new Promise((resolve) => {
        if (bingoIsWafCooling()) {
            return resolve({ status: 'success' });
        }

        $.get(site_url + 'playings/messageGet', { after_id: getLastChatMessageId() })
            .done((data) => {
                if (data.status === 'stop') {
                    messagePoller.stop();
                    return resolve(data);
                }
                processIncomingChatMessages(data);
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

// Ejecutar auto-sing periódicamente (sin spamear la CPU en móviles)
setInterval(() => {
    if (document.hidden) return;
    try { autoSingIfComplete(); } catch (e) { }
}, 4000);

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

    $(".number-" + number).each(function () {
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
            btn.html('<i class="fa-duotone fa-solid fa-wand-magic-sparkles"></i>');
        } else {
            btn.html('<i class="fa-duotone fa-solid fa-hand"></i>');
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

    // Reemplazar contenido completo para evitar bolas apiladas (7 encima de 7)
    lastNumberEl.empty()
        .html(`<small style="position: absolute; top: -13px; font-size: 1.2rem; z-index: 1;">${getColumnClass(parsed)}</small><span>${parsed}</span>`)
        .removeClass()
        .addClass(`bingo-ball ${getColumnClass(parsed)} size-100`);
}

function getHistoryBallSizeClass() {
    // Un poco más grandes en el historial lateral (live / playing)
    return 'size-50';
}

function renderBallHistory() {
    const container = $("#last-five-numbers");
    if (!container.length) {
        return;
    }

    const ordered = uniqueOrderedBalls(window.drawnNumbers || []);

    if (!ordered.length) {
        container.empty();
        return;
    }

    // Solo UI: mostrar máximo 4 balotas en el historial lateral (no afecta la lógica del juego).
    const historyLimit = 4;

    // En LIVE no hay #last-number: el historial debe incluir la bola actual
    // (si no, el jugador ve la bola recién cantada solo cuando sale la siguiente).
    const hasMainBall = $('#last-number').length > 0;
    const history = hasMainBall
        ? (ordered.length > 1 ? ordered.slice(Math.max(0, ordered.length - (historyLimit + 1)), -1) : [])
        : ordered.slice(Math.max(0, ordered.length - historyLimit));

    container.empty();
    history.slice(-historyLimit).forEach(function (num) {
        container.append(`<div class="bingo-ball ${getColumnClass(num)} ${getHistoryBallSizeClass()}"><span>${num}</span></div>`);
    });
}

function reconcileBallDisplay(orderedNumbers) {
    const ordered = uniqueOrderedBalls(orderedNumbers || window.drawnNumbers || []);

    if (!ordered.length) {
        return;
    }

    window.drawnNumbers = ordered.slice();
    if (!isBallPlaybackActive && !ballPlaybackQueue.length) {
        lastNumbers = ordered.slice(-5);
        updateMainBall(ordered[ordered.length - 1]);
        renderBallHistory();
    }
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
    $('.bingo-carton-number.marked').each(function () {
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
        nextGameSpan.textContent = '¡EL JUEGO HA INICIADO!';
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
        // Marca local inmediata: el servidor ya sincroniza marcas en cron/numberGet.
        // Evita 1 POST dialNumber por bola → menos 403 del WAF y sin desfase.
        markCartonNumberLocally(parsed, true);
        autoMarkedNumbers.add(parsed);
        scheduleAutoSingCheck(150);
    }
}

// ─────────────────────────────────────────
// Cola secuencial de reproducción de balotas
// Garantiza que cada balota se cante, se anime y se marque en orden sin solaparse
// ─────────────────────────────────────────
let ballPlaybackQueue = [];
let isBallPlaybackActive = false;

function enqueueBallsForPlayback(balls) {
    if (window.gameIsFinished || isGameFinishedShown) {
        ballPlaybackQueue = [];
        return;
    }
    if (!Array.isArray(balls) || !balls.length) return;

    balls.forEach(function (b) {
        const parsed = parseBallNumber(b);
        if (parsed && !ballPlaybackQueue.includes(parsed)) {
            ballPlaybackQueue.push(parsed);
        }
    });

    playNextBallInQueue();
}

function playNextBallInQueue() {
    if (window.gameIsFinished || isGameFinishedShown) {
        ballPlaybackQueue = [];
        isBallPlaybackActive = false;
        return;
    }

    // Auto-recuperación si bingoInProgress quedó activo indebidamente por más de 4.5 segundos
    if (bingoInProgress) {
        if (!window.__bingoInProgressTimestamp) {
            window.__bingoInProgressTimestamp = Date.now();
        } else if (Date.now() - window.__bingoInProgressTimestamp > 4500) {
            console.warn('Auto-recuperación: bingoInProgress activo por >4500ms, liberando bloqueo');
            bingoInProgress = false;
            window.__bingoInProgressTimestamp = null;
        } else {
            return;
        }
    } else {
        window.__bingoInProgressTimestamp = null;
    }

    if (isBallPlaybackActive || !ballPlaybackQueue.length) {
        return;
    }

    isBallPlaybackActive = true;

    // Watchdog inmediato (3.5s) que garantiza que el flag isBallPlaybackActive NUNCA quede congelado
    if (window.__ballPlaybackWatchdog) {
        clearTimeout(window.__ballPlaybackWatchdog);
    }
    window.__ballPlaybackWatchdog = setTimeout(function () {
        if (isBallPlaybackActive) {
            console.warn('Watchdog de balota: forzando liberación tras 3500ms');
            isBallPlaybackActive = false;
            if (ballPlaybackQueue.length > 0 && !bingoInProgress) {
                playNextBallInQueue();
            }
        }
    }, 3500);

    const waitMs = ballPlaybackQueue.length > 1 ? 1400 : 1800;

    try {
        const currentNumber = ballPlaybackQueue.shift();
        if (!currentNumber) {
            isBallPlaybackActive = false;
            return;
        }

        // 1. Mostrar la balota en el cabezal con animación visual
        updateMainBall(currentNumber);
        const lastNumberEl = $('#last-number');
        if (lastNumberEl.length) {
            lastNumberEl.addClass('move-number');
            if (ballRevealAfterTimer) {
                clearTimeout(ballRevealAfterTimer);
            }
            ballRevealAfterTimer = setTimeout(function () {
                lastNumberEl.removeClass('move-number');
                ballRevealAfterTimer = null;
            }, 500);
        }

        // 2. Cantar el audio de la balota inmediatamente si la narración está activa
        const isNarrationOn = (typeof narrationPlaying !== 'undefined') ? narrationPlaying : (window.narrationPlaying !== false);
        if (isNarrationOn && !window.gameIsFinished && !isGameFinishedShown) {
            const basePath = (typeof audioPath !== 'undefined' && audioPath) ? audioPath : (window.audioPath || '/assets/sounds/');
            audioManager.play(basePath + currentNumber + '.mp3', currentNumber);
        }

        // 3. Marcado INMEDIATO (0ms) en cartón y tablero al salir la balota
        applyMarksForNumber(currentNumber);

        // Actualizar el carrusel de últimas 5 balotas ordenadas hasta esta balota
        if (Array.isArray(window.drawnNumbers)) {
            const idx = window.drawnNumbers.indexOf(currentNumber);
            if (idx !== -1) {
                lastNumbers = window.drawnNumbers.slice(0, idx + 1).slice(-5);
                renderBallHistory();
            }
        }
    } catch (err) {
        console.error('Error al procesar balota en cola:', err);
    } finally {
        setTimeout(function () {
            isBallPlaybackActive = false;
            if (window.__ballPlaybackWatchdog) {
                clearTimeout(window.__ballPlaybackWatchdog);
                window.__ballPlaybackWatchdog = null;
            }
            if (window.gameIsFinished || isGameFinishedShown) {
                ballPlaybackQueue = [];
                return;
            }
            if (ballPlaybackQueue.length > 0 && !bingoInProgress) {
                playNextBallInQueue();
            }
        }, waitMs);
    }
}

function scheduleLatestBallMarks(latestNumber, options) {
    const parsed = parseBallNumber(latestNumber);
    if (!parsed) {
        return;
    }
    enqueueBallsForPlayback([parsed]);
}

function buildOrderedDrawnNumbers(newNumber, drawnNumbers) {
    if (Array.isArray(drawnNumbers) && drawnNumbers.length) {
        return uniqueOrderedBalls(drawnNumbers);
    }

    const ordered = numbersgenerated.slice();
    const parsed = parseBallNumber(newNumber);
    if (parsed && !ordered.includes(parsed)) {
        ordered.push(parsed);
    }

    return ordered;
}

function uniqueOrderedBalls(numbers) {
    const ordered = [];
    const seen = {};
    (numbers || []).forEach(function (value) {
        const parsed = parseBallNumber(value);
        if (!parsed || seen[parsed]) {
            return;
        }
        seen[parsed] = true;
        ordered.push(parsed);
    });
    return ordered;
}

function syncDrawnNumbersFromServer(drawnNumbers, totalNumbersGenerated, options) {
    const opts = options || {};
    const ordered = uniqueOrderedBalls(drawnNumbers);

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
    const missing = ordered.filter(function (num) {
        return !previous.includes(num);
    });

    // Deduplicación estricta: si no hay balotas nuevas y ya teníamos estado previo, no duplicar trabajo
    if (missing.length === 0 && previous.length > 0) {
        updateBallsCounter(ballsCount);
        return;
    }

    numbersgenerated = ordered.slice();
    window.drawnNumbers = ordered.slice();

    updateBallsCounter(ballsCount);
    markGameAsStartedFromServer(ballsCount);

    flushPendingMark();
    clearBallRevealTimers(false);

    // Caso A: Reconciliación múltiple / Desincronización (más de 1 bola nueva a la vez)
    // Ocurre tras reconexión de WebSocket, regresar de bloqueo de pantalla o cambio de red móvil
    if (missing.length > 1 || previous.length === 0 || opts.animate === false) {
        if (window.ballTelemetry) {
            window.ballTelemetry.logBallTiming('T4_MULTI_RECONCILE', ordered[ordered.length - 1], { missingCount: missing.length });
        }

        // 1. Marcar inmediatamente TODAS las bolas perdidas en tablero y cartones (0ms)
        ordered.forEach(function (num) {
            markBoardNumber(num);
        });
        if (isAutoMarkEnabled()) {
            syncAutoMarkedNumbers(ordered, { animate: false, persist: false });
        }

        // 2. Mostrar DIRECTAMENTE la última balota del servidor en pantalla (0ms)
        const latestBall = ordered[ordered.length - 1];
        lastNumbers = ordered.slice(-5);
        updateMainBall(latestBall);
        renderBallHistory();

        // 3. Vaciar cualquier cola antigua de reproducción acumulada para no retrasar el juego
        ballPlaybackQueue = [];
        isBallPlaybackActive = false;
        if (window.__ballPlaybackWatchdog) {
            clearTimeout(window.__ballPlaybackWatchdog);
            window.__ballPlaybackWatchdog = null;
        }

        // 4. Cantar ÚNICAMENTE la última balota si la partida está en vivo y no en pantalla final
        // NUNCA reproducir una ráfaga de balotas viejas que desfasaría al móvil varios segundos
        if (opts.animate !== false && missing.length > 0 && !window.gameIsFinished && !isGameFinishedShown && !bingoInProgress) {
            const isNarrationOn = (window.audioSettings && typeof window.audioSettings.narrationEnabled !== 'undefined')
                ? window.audioSettings.narrationEnabled
                : ((typeof narrationPlaying !== 'undefined') ? narrationPlaying : true);
            if (isNarrationOn) {
                const basePath = (typeof audioPath !== 'undefined' && audioPath) ? audioPath : (window.audioPath || '/assets/sounds/');
                audioManager.play(basePath + latestBall + '.mp3', latestBall);
            }
        }
        return;
    }

    // Caso B: Flujo en tiempo real normal (exactamente 1 balota nueva)
    const currentNumber = missing[0];

    if (window.ballTelemetry) {
        window.ballTelemetry.logBallTiming('T5_RENDER_START', currentNumber);
    }

    // 1. MOSTRAR BALOTA DE INMEDIATO (0ms, la visualización NUNCA depende del audio ni de animaciones)
    updateMainBall(currentNumber);

    if (window.ballTelemetry) {
        window.ballTelemetry.logBallTiming('T6_DOM_RENDERED', currentNumber);
    }

    // 2. Animación visual sutil no bloqueante
    const lastNumberEl = $('#last-number');
    if (lastNumberEl.length) {
        lastNumberEl.addClass('move-number');
        if (ballRevealAfterTimer) {
            clearTimeout(ballRevealAfterTimer);
        }
        ballRevealAfterTimer = setTimeout(function () {
            lastNumberEl.removeClass('move-number');
            ballRevealAfterTimer = null;
        }, 400);
    }

    // 3. Marcado instantáneo (0ms) en cartones y tablero
    applyMarksForNumber(currentNumber);

    // 4. Actualizar historial de balotas de inmediato (0ms)
    lastNumbers = ordered.slice(-5);
    renderBallHistory();

    // 5. Locución de audio asíncrona e independiente (no bloquea el flujo visual)
    const isNarrationOn = (window.audioSettings && typeof window.audioSettings.narrationEnabled !== 'undefined')
        ? window.audioSettings.narrationEnabled
        : ((typeof narrationPlaying !== 'undefined') ? narrationPlaying : true);
    if (isNarrationOn && !window.gameIsFinished && !isGameFinishedShown && !bingoInProgress) {
        if (window.ballTelemetry) {
            window.ballTelemetry.logBallTiming('T7_AUDIO_START', currentNumber);
        }
        const basePath = (typeof audioPath !== 'undefined' && audioPath) ? audioPath : (window.audioPath || '/assets/sounds/');
        audioManager.play(basePath + currentNumber + '.mp3', currentNumber);
    }
}

function getBallDisplayDelay() {
    // Sin retardo: marcado instantáneo (0ms) al cantar la balota
    return 0;
}

function applyMarksForDrawnNumber(number) {
    markBoardNumber(number);

    if (isAutoMarkEnabled()) {
        // Sin POST: numberGet ya sincroniza marcas en servidor
        markCartonNumberLocally(number, false);
        autoMarkedNumbers.add(parseInt(number, 10));
        scheduleAutoSingCheck();
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

    elementsNumber.each(function () {
        const elementNumber = $(this);

        if (elementNumber.hasClass('marked')) {
            return;
        }

        // Marcar de inmediato en el cartón (0ms de retraso)
        elementNumber.addClass('marked');

        if (animate) {
            elementNumber.addClass('explosive-effect');
            setTimeout(function () {
                elementNumber.removeClass('explosive-effect');
            }, 300);
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

    numbers.forEach(function (number) {
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

        // Por defecto también local (evita tormenta de dialNumber → 403)
        markCartonNumberLocally(parsed, animate);
        autoMarkedNumbers.add(parsed);
    });

    if (isAutoMarkEnabled()) {
        scheduleAutoSingCheck(animate ? 500 : 200);
    }
}

function startWinnerSlider() {
    // Ya no rotamos "GANADOR: ..." en el encabezado; solo se usa la notificación de ganador.
    return;
}

function resetWinnerNoticeUi(numberHe, container, textHe) {
    if (container) {
        container.style.display = 'none';
        container.style.backgroundColor = '';
    }
    if (numberHe) {
        numberHe.style.display = '';
        numberHe.style.backgroundImage = '';
        numberHe.style.background = 'linear-gradient(145deg, #6236ff, #8767fa)';
        numberHe.style.color = 'white';
        numberHe.textContent = '';
    }
    if (textHe) {
        textHe.innerHTML = '';
    }
}

function showCountdown(data, callback) {
    // Solo efectos de juego (marcar cartón / registrar). SIN círculo ni overlay.
    // La UI de ganador es únicamente la notificación toast "¡HAS CANTADO BINGO!".
    const container = $id('countdown-container');
    if (container) {
        container.style.display = 'none';
    }

    if (data && data.player) {
        registerWinner(data.player, data.modality);
    }
    if (data && Array.isArray(data.winners)) {
        mergeWinnersFromServer(data.winners);
    }

    if (data && data.modalityId) {
        const cartns = document.querySelectorAll(`[id="modality-${data.modalityId}"]`);
        cartns.forEach(cartn => {
            cartn.classList.add('cartn-sing');

            let borderCarton = cartn.parentElement;
            while (borderCarton && !borderCarton.classList.contains('border-carton')) {
                borderCarton = borderCarton.parentElement;
            }
            if (borderCarton) {
                borderCarton.classList.add('modality-won');
            }

            cartn.querySelectorAll('.card-number.modality-sing').forEach(el => {
                el.classList.add('sing');
                el.innerText = '⭐️';
            });
        });
    }

    // Si el juego finalizó, dar 3.5 segundos de celebración al ganador antes de pasar al fin de juego
    if ((data && data.gameCompleted === true) || window.gameIsFinished || isGameFinishedShown) {
        simultaneousBingos = [];
        setTimeout(function () {
            bingoInProgress = false;
            if (typeof callback === 'function') callback();
        }, 3500);
        return;
    }

    setTimeout(() => {
        if (simultaneousBingos.length > 0) {
            const nextBingo = simultaneousBingos.shift();
            showCountdown(nextBingo, callback);
        } else {
            bingoInProgress = false;
            if (callback) callback();
            if (ballPlaybackQueue.length > 0 && !isBallPlaybackActive) {
                playNextBallInQueue();
            }
        }
    }, 1200);
}

window.seenBingoNotices = window.seenBingoNotices || new Set();

function showOtherPlayerBingoNotice(data, callback) {
    if (!data) {
        return;
    }

    const noticeKey = (data.singId ? 'sing_' + data.singId : '') ||
                      (data.cartonId ? 'carton_' + data.cartonId + '_' + (data.modalityId || '') : '') ||
                      ((data.player || '') + '_' + (data.modality || ''));

    if (window.seenBingoNotices.has(noticeKey)) {
        if (typeof callback === 'function') {
            callback();
        }
        return;
    }
    window.seenBingoNotices.add(noticeKey);

    window.gameHasWinner = true;
    bingoInProgress = true;
    window.__lastWinnerNoticeTime = Date.now();
    // No limpiar lastNumber aquí para no congelar la pantalla de los demás jugadores

    if (Array.isArray(data.winners)) {
        mergeWinnersFromServer(data.winners);
    }

    if (data.player && data.modality) {
        registerWinner(data.player, data.modality);
    }

    // Convertir visualmente la modalidad a "ganada" en tiempo real para todos los jugadores
    if (data.modalityId) {
        const cartns = document.querySelectorAll(`[id="modality-${data.modalityId}"]`);
        cartns.forEach(cartn => {
            cartn.classList.add('cartn-sing');

            let borderCarton = cartn.parentElement;
            while (borderCarton && !borderCarton.classList.contains('border-carton')) {
                borderCarton = borderCarton.parentElement;
            }
            if (borderCarton) {
                borderCarton.classList.add('modality-won');
            }

            cartn.querySelectorAll('.card-number.modality-sing').forEach(el => {
                el.classList.add('sing');
                el.innerText = '⭐️';
            });
        });
    }

    // 1. Reproducir sonido de notificación con winner.mp3
    try {
        if (typeof playNotificationSound === 'function') {
            playNotificationSound('winner', true);
        } else if (typeof audioManager !== 'undefined') {
            audioManager.play(audioPath + 'winner.mp3');
        }
    } catch (e) {
        console.warn('Error al reproducir audio de notificación:', e);
    }

    // 2. Disparar notificación toast visual del sistema unificada por modalidad
    if (typeof window.showNotification === 'function') {
        const modalityClean = (data.modality || 'Bingo').replace(/^la\s+/i, '').trim();
        const modalityLabel = /^bingo/i.test(modalityClean) || /^pleno/i.test(modalityClean)
            ? `Ganadores de la modalidad ${modalityClean}`
            : `Ganadores de la ${modalityClean}`;

        window.showNotification({
            id: noticeKey,
            type: 'sing',
            modalityId: data.modalityId,
            modality: data.modality,
            player: data.player,
            cartonId: data.cartonId,
            title: '🎉 ¡BINGO CANTADO!',
            message: `${modalityLabel}: ${data.player}`,
            created_at: new Date().toISOString()
        });
    }

    // 3. Efectos visuales de confeti
    if (typeof window.AppcreateConfetti === 'function') {
        window.AppcreateConfetti();
    }

    if (data && data.isOwnBingo !== true) {
        data.isOwnBingo = false;
    }

    // Si la partida terminó con este cante, permitir que showCountdown corra la celebración y al terminar llame a showGameFinalized
    const isCompleted = (data && (data.gameCompleted === true || data.stopped === true));
    if (isCompleted) {
        window.gameIsFinished = true;
        window.allowGameUnload = true;
    }

    showCountdown(data, function () {
        if (isCompleted || data.gameCompleted === true || window.gameIsFinished || isGameFinishedShown) {
            showGameFinalized();
            return;
        }

        scheduleAutoSingCheck(400);
        if (typeof callback === 'function') {
            callback();
        } else {
            startAutomaticLast();
        }
    });
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

    // Partida finalizada: detener inmediatamente intervalos y vaciar cola de balotas
    if (window.gameIsFinished || isGameFinishedShown || data.status === 'completed' || data.gameCompleted === true) {
        window.gameIsFinished = true;
        window.allowGameUnload = true;
        ballPlaybackQueue = [];
        isBallPlaybackActive = false;
        intervalManager.clear('lastNumber');

        applyNumberGetMeta(data);
        applyAutoMarkPreferenceFromServer(data.autodial);

        if (Array.isArray(data.winners)) {
            mergeWinnersFromServer(data.winners);
        }

        if (data.player && data.modality) {
            registerWinner(data.player, data.modality);
        }

        // Sincronizar números en el tablero de forma estática sin animar ni cantar balota
        if (Array.isArray(data.drawnNumbers) && data.drawnNumbers.length) {
            syncDrawnNumbersFromServer(data.drawnNumbers, data.totalNumbersGenerated, { animate: false });
        }

        window.gameHasWinner = true;

        // Si hay una celebración en progreso, no cortar audio ni interrumpir
        if (bingoInProgress) {
            return;
        }

        // Si hay ganador que no se ha notificado aún, mostrar aviso antes de finalizar
        const noticeKey = ((data.player || '') + '_' + (data.modality || ''));
        if (data.player && data.modality && window.seenBingoNotices && !window.seenBingoNotices.has(noticeKey)) {
            showOtherPlayerBingoNotice(data, function () {
                showGameFinalized();
            });
            return;
        }

        if (typeof audioManager !== 'undefined' && audioManager.stopAll) {
            audioManager.stopAll();
        }
        showGameFinalized();
        return;
    }

    // Partida aún no inicia: mantener contador, no marcar como iniciada
    if (data.status === 'waiting') {
        if (data.postponed && data.new_time && typeof handleGamePostponed === 'function') {
            handleGamePostponed(data.new_time, data.message);
        }
        // Por si el admin ya cantó y el backend aún reporta waiting con bolas
        if (Array.isArray(data.drawnNumbers) && data.drawnNumbers.length) {
            applyNumberGetMeta(data);
            applyAutoMarkPreferenceFromServer(data.autodial);
            syncDrawnNumbersFromServer(data.drawnNumbers, data.totalNumbersGenerated, { animate: false });
        }
        return;
    }

    applyNumberGetMeta(data);
    applyAutoMarkPreferenceFromServer(data.autodial);

    if (Array.isArray(data.drawnNumbers) && data.drawnNumbers.length) {
        const prevLen = numbersgenerated.length;
        const nextLen = uniqueOrderedBalls(data.drawnNumbers).length;
        // Animar y cantar siempre que haya nuevas balotas al inicio (hasta 5) o en flujo en vivo
        const isGameStart = prevLen === 0 && nextLen <= 5;
        const isLiveFlow = prevLen > 0 && nextLen > prevLen && (nextLen - prevLen) <= 5;
        const animateBalls = !bingoInProgress && (isGameStart || isLiveFlow || (nextLen > prevLen));
        syncDrawnNumbersFromServer(data.drawnNumbers, data.totalNumbersGenerated, { animate: animateBalls });
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
                        image: data.image,
                        isOwnBingo: true,
                        winnerUserId: data.winnerUserId
                    }, function () {
                        if (data.gameCompleted || window.gameIsFinished || isGameFinishedShown) {
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
    }
}

let isSyncInProgress = false;
let lastSyncTimestamp = 0;

function syncGameState(reason) {
    if (bingoInProgress || window.gameIsFinished || isGameFinishedShown) {
        return;
    }
    if (bingoIsBallBackingOff()) {
        return;
    }

    const now = Date.now();
    // Prevenir peticiones en ráfaga (mínimo 1200ms entre syncs a menos que sea forzado)
    if (isSyncInProgress || (now - lastSyncTimestamp < 1200 && reason !== 'forced')) {
        return;
    }
    isSyncInProgress = true;
    lastSyncTimestamp = now;

    if (typeof audioManager !== 'undefined' && audioManager.resumeContext) {
        audioManager.resumeContext();
    }

    // Asegurar que Pusher / Soketi esté conectado en móviles
    if (typeof pusherHelper !== 'undefined' && pusherHelper.ensureConnected) {
        pusherHelper.ensureConnected();
    }

    $.ajax({
        url: site_url + 'playings/numberGet',
        method: 'GET',
        cache: false,
        data: { _ts: now, r: reason || 'sync' }
    })
        .done((data) => {
            if (!data || data.status === 'error') {
                return;
            }
            processNumberGetResponse(data);
        })
        .fail((xhr, status, error) => {
            if (xhr && xhr.status === 403) {
                bingoTripBallBackoff();
            }
            console.warn('Failed to sync game state (' + (reason || 'unknown') + '):', error);
        })
        .always(() => {
            isSyncInProgress = false;
        });
}
window.syncGameState = syncGameState;

function lastNumberGet() {
    syncGameState('interval');
}

function getEffectiveBallIntervalMs() {
    var raw = parseInt(window.timeBallGet, 10);
    if (Number.isFinite(raw) && raw >= 1000) {
        return raw;
    }
    var rawLast = (typeof timeBallLast !== 'undefined') ? parseInt(timeBallLast, 10) : 0;
    if (Number.isFinite(rawLast) && rawLast >= 1000) {
        return rawLast;
    }
    return 10000;
}

function startAutomaticLast() {
    intervalManager.clear('lastNumber');
    if (window.gameIsFinished || isGameFinishedShown) {
        return;
    }
    if (typeof timeBallLast === 'undefined' && typeof window.timeBallGet === 'undefined') {
        return;
    }

    bingoInProgress = false;
    flushPendingMark();

    if (ballPlaybackQueue.length > 0 && !isBallPlaybackActive) {
        playNextBallInQueue();
    }

    // Primer sync inmediato para cargar estado al entrar o tras celebrar
    syncGameState('init');

    var wsActive = window.__bingoPusherRealtime === true;
    // Si WebSocket está activo, polling relajado (8s) como guardia de deriva sin sobrecargar CPU ni red
    // Si WebSocket NO está activo, respaldo HTTP cada 2.5s
    var fallbackMs = wsActive ? 8000 : 2500;

    intervalManager.set('lastNumber', lastNumberGet, fallbackMs);
}

function setBingoPusherRealtime(enabled) {
    var wasEnabled = window.__bingoPusherRealtime;
    window.__bingoPusherRealtime = enabled;

    if (window.gameIsFinished || isGameFinishedShown) {
        intervalManager.clear('lastNumber');
        return;
    }

    if (enabled && !wasEnabled) {
        // WebSocket conectado: polling relajado de 8s (solo guardia de seguridad, WebSocket es la fuente primaria)
        console.log('WS conectado: reduciendo polling HTTP a guardia relajada (8s)');
        intervalManager.clear('lastNumber');
        intervalManager.set('lastNumber', lastNumberGet, 8000);
        if (typeof messagePoller !== 'undefined' && messagePoller) {
            messagePoller.baseInterval = 15000;
            messagePoller.currentInterval = 15000;
        }
        // Resincronizar estado de inmediato para recuperar cualquier posible brecha durante la conexión
        syncGameState('ws_connected');
    } else if (!enabled && wasEnabled) {
        // WebSocket caído: volver al poll de respaldo ágil (2.5s)
        console.warn('WS desconectado: activando poll de respaldo HTTP (2.5s)');
        startAutomaticLast();
        if (typeof messagePoller !== 'undefined' && messagePoller) {
            messagePoller.baseInterval = CONFIG.CHAT_POLL_INTERVAL || 3500;
            messagePoller.currentInterval = CONFIG.CHAT_POLL_INTERVAL || 3500;
        }
    }
}

function stopAutomaticLast() {
    intervalManager.clear('lastNumber');
}

function showGameFinalized() {
    window.gameIsFinished = true;
    window.allowGameUnload = true;
    ballPlaybackQueue = [];
    isBallPlaybackActive = false;
    intervalManager.clear('lastNumber');

    // Control de idempotencia estricto: la finalización se ejecuta exactamente una sola vez
    if (isGameFinishedShown || window.__gameFinalizationInProgress) {
        return;
    }
    window.__gameFinalizationInProgress = true;
    isGameFinishedShown = true;
    bingoInProgress = false;

    if (typeof audioManager !== 'undefined' && audioManager.stopAll) {
        audioManager.stopAll();
    }

    stopAutomaticLast();
    stopUpdateUserCount();
    stopUpdateGameAccumulated();
    messagePoller.stop();

    const countdownContainer = $id('countdown-container');
    if (countdownContainer) {
        countdownContainer.style.display = 'none';
    }

    const nextGameSpan = document.querySelector('.next-game');
    if (nextGameSpan) {
        nextGameSpan.textContent = (__['game finished!'] || 'JUEGO FINALIZADO').toUpperCase();
    }

    const controlsDiv = $id('controls');
    if (controlsDiv) {
        controlsDiv.remove();
    }

    const exitToPlay = function () {
        window.__userLeavingGame = true;
        window.allowGameUnload = true;
        window.onbeforeunload = null;
        window.onpopstate = null;

        if (window.awardsModalTimeoutId) {
            clearTimeout(window.awardsModalTimeoutId);
            window.awardsModalTimeoutId = null;
        }

        const modalAwardsEl = document.getElementById('modalAwards');
        if (modalAwardsEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const bsAwardsModal = bootstrap.Modal.getInstance(modalAwardsEl);
            if (bsAwardsModal) {
                bsAwardsModal.hide();
            }
        }

        const modalGameFinalizedEl = document.getElementById('modalGameFinalized');
        if (modalGameFinalizedEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const bsFinModal = bootstrap.Modal.getInstance(modalGameFinalizedEl);
            if (bsFinModal) {
                bsFinModal.hide();
            }
        }

        if (typeof audioManager !== 'undefined' && audioManager.stopAll) {
            audioManager.stopAll();
        }

        const targetUrl = typeof site_url !== 'undefined' ? site_url + 'play' : '/play';
        window.location.replace(targetUrl);
    };

    // Actualizar ganadores en segundo plano sin bloquear la UI
    if (typeof fetchWinnersBeforeFinalize === 'function') {
        fetchWinnersBeforeFinalize();
    }

    const container = $id('game-finalized');
    const text = $id('finalized');

    // PASO 1: Mostrar claramente el mensaje "El juego ha terminado" en el overlay existente
    if (container && text) {
        container.style.display = 'block';
        text.innerHTML = (__['game finished!'] || '¡El juego ha terminado!').toUpperCase();
    }

    const openWinnersAwardsModal = function () {
        if (window.__userLeavingGame) {
            return;
        }
        if (container) {
            container.style.display = 'none';
        }

        let autoExitTimer = null;
        // Dar 60 segundos completos para revisar con calma la tabla de ganadores y pagos
        const autoExitDuration = 60000;

        const scheduleAutoExit = function () {
            if (autoExitTimer) clearTimeout(autoExitTimer);
            autoExitTimer = setTimeout(function () {
                exitToPlay();
            }, autoExitDuration);
        };

        const cancelAutoExit = function () {
            if (autoExitTimer) {
                clearTimeout(autoExitTimer);
                autoExitTimer = null;
            }
        };

        // Si el usuario interactúa con el modal o compra cartones para la siguiente partida, no expulsarlo
        $(document).on('click', '#modalAwards, #modalGameFinalized, .continue-button-buy, .card-button-buy', function () {
            cancelAutoExit();
        });

        // Al cerrar cualquiera de los modales, salir limpiamente a la sala principal
        $('#modalAwards, #modalGameFinalized').on('hidden.bs.modal', function () {
            cancelAutoExit();
            exitToPlay();
        });

        const gid = (typeof GAME_ID !== 'undefined' && GAME_ID) ? GAME_ID : (window.gameId || '');
        const queryParam = gid ? ('?game_id=' + gid) : '';
        const awardsUrl = (typeof window.playerGroup !== 'undefined' && parseInt(window.playerGroup, 10) === 0)
            ? (site_url + 'playings/awardsGet' + queryParam)
            : (site_url + 'boards/awardsGet' + queryParam);

        let modalAwardsEl = document.getElementById('modalAwards');
        if (!modalAwardsEl) {
            modalAwardsEl = document.createElement('div');
            modalAwardsEl.className = 'modal fade';
            modalAwardsEl.id = 'modalAwards';
            modalAwardsEl.tabIndex = -1;
            modalAwardsEl.setAttribute('role', 'dialog');
            modalAwardsEl.setAttribute('data-bs-backdrop', 'static');
            modalAwardsEl.setAttribute('data-bs-keyboard', 'false');
            document.body.appendChild(modalAwardsEl);
        }

        $("#modalAwards").load(awardsUrl, function (response, status, xhr) {
            if (window.__userLeavingGame) {
                return;
            }
            if (status === 'error') {
                console.error('Error cargando modalAwards:', xhr ? xhr.status : status);
                const bodyEl = document.getElementById('modalGameFinalizedBody');
                if (bodyEl) {
                    bodyEl.innerHTML = buildWinnersFinalText();
                }

                const modalEl = document.getElementById('modalGameFinalized');
                if (modalEl) {
                    const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: 'static', keyboard: false });
                    bsModal.show();

                    const btnVolver = document.getElementById('btnVolverInicio');
                    if (btnVolver) {
                        btnVolver.addEventListener('click', function () {
                            cancelAutoExit();
                            bsModal.hide();
                            exitToPlay();
                        }, { once: true });
                    }
                }
                scheduleAutoExit();
                return;
            }

            if (container) {
                container.style.display = 'none';
            }
            const countdownContainer = $id('countdown-container');
            if (countdownContainer) {
                countdownContainer.style.display = 'none';
            }

            // Mostrar la tabla blanca de ganadores (modalAwards)
            const bsAwardsModal = bootstrap.Modal.getOrCreateInstance(modalAwardsEl, { backdrop: 'static', keyboard: false });
            bsAwardsModal.show();

            $(modalAwardsEl).find('[data-bs-dismiss="modal"], .btn-volver-inicio, #btnVolverInicio, .btn-exit-game').off('click').on('click', function () {
                cancelAutoExit();
                bsAwardsModal.hide();
                exitToPlay();
            });

            scheduleAutoExit();
        });
    };

    // PASO 2: Mantener el mensaje "El juego ha terminado" visible durante 3.5 segundos antes de abrir la tabla blanca
    if (window.awardsModalTimeoutId) {
        clearTimeout(window.awardsModalTimeoutId);
    }
    window.awardsModalTimeoutId = setTimeout(openWinnersAwardsModal, 3500);
}

// Contador de usuarios optimizado
const updateUserCount = throttle(() => {
    updateLiveStatus();
}, 1000);

function stopUpdateUserCount() {
    stopUpdateLiveStatus();
}

function applyLiveStatusUi(data) {
    if (!data) {
        return;
    }

    const countEl = $('.count_notifications');
    if (countEl.length) {
        if (data.userCount && data.userCount > 0) {
            countEl.text(data.userCount).show();
        } else {
            countEl.hide();
        }
    }

    const accumulatedEl = $('#accumulated-counter');
    if (accumulatedEl.length && typeof data.gameAccumulated !== 'undefined') {
        accumulatedEl.text(currency + ' ' + data.gameAccumulated);
    }
    if (data.prizeLabel) {
        const labelEl = $('#accumulated-label');
        if (labelEl.length) {
            labelEl.text(data.prizeLabel);
        } else {
            accumulatedEl.closest('.total-accumulated').find('small').first().text(data.prizeLabel);
        }
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

const updateLiveStatus = throttle(() => {
    if (bingoIsWafCooling()) {
        return;
    }

    $.get(site_url + 'games/liveStatusGet')
        .done((data) => {
            applyLiveStatusUi(data);

            if (data.status === 'completed') {
                stopUpdateLiveStatus();
                if (!isGameFinishedShown) {
                    showGameFinalized();
                }
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
    intervalManager.clear('gameAccumulated');
}

// Contador de acumulado (usa el endpoint unificado)
const updateGameAccumulated = throttle(() => {
    updateLiveStatus();
}, 1000);

function stopUpdateGameAccumulated() {
    stopUpdateLiveStatus();
}

// Función optimizada para marcar números (modo manual)
function dialNumber(number) {
    const elementsNumber = $(".number-" + number);

    if (!elementsNumber.length) {
        console.warn("No se encontró el número en el DOM:", number);
        return;
    }

    // Marcado optimista inmediato: marcar en la UI al instante sin esperar la red ni animaciones bloqueantes
    elementsNumber.each(function () {
        const elementNumber = $(this);

        if (elementNumber.hasClass('marked')) {
            return;
        }

        elementNumber.addClass('marked explosive-effect');
        setTimeout(function () {
            elementNumber.removeClass('explosive-effect');
        }, 300);
    });

    $.ajax({
        url: site_url + 'playings/dialNumber',
        method: 'POST',
        data: { number: number },
        success: function (data) {
            if (data.status === 'success') {
                if (isAutoMarkEnabled()) {
                    scheduleAutoSingCheck(100);
                }
            } else {
                // Revertir marca si el servidor la rechaza
                elementsNumber.removeClass('marked');
                autoMarkedNumbers.delete(number);
                console.warn("Respuesta no exitosa al marcar número:", data.message || data);
            }
        },
        error: function (xhr, status, error) {
            elementsNumber.removeClass('marked');
            autoMarkedNumbers.delete(number);
            console.error("Error en AJAX al marcar número:", number, error);
        }
    });
}

// Función optimizada para marcar números
function autoDialNumber(number) {
    const elementsNumber = $(".number-" + number);
    elementsNumber.each(function () {
        const elementNumber = $(this);

        if (elementNumber.hasClass('marked')) return;

        elementNumber.addClass('marked explosive-effect');
        setTimeout(function () {
            elementNumber.removeClass('explosive-effect');
        }, 300);
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
        success: function (data) {
            if (data.status === 'success') {
                const lastBall = getLastBoardNumber();
                if (lastBall) {
                    lastAutoSingBall = lastBall;
                    lastAutoSingNoNew = false;
                }
                handleBingoSuccess(data, startAutomaticLast);
            } else if (data && data.gameHasWinner) {
                window.gameHasWinner = true;
            }
        },
        error: function (xhr, status, error) {
            console.error("Error al cantar bingo:", error);
        }
    });
}

// Función optimizada para cantar bingo
// NOTE: La implementación válida de singBingo ya está definida arriba (con control de bingoInProgress).
// Eliminamos la segunda definición duplicada para evitar comportamiento inesperado.

// Funciones de audio / preferencias
function updateVolumeButtonIcon(enabled) {
    const btn = document.querySelector('.btn-volume');
    if (!btn) {
        return;
    }
    btn.innerHTML = enabled
        ? '<i class="fa-duotone fa-solid fa-volume"></i>'
        : '<i class="fa-duotone fa-solid fa-volume-slash"></i>';
}

function updateMicrophoneButtonIcon(enabled) {
    const btn = document.querySelector('.btn-microphone');
    if (!btn) {
        return;
    }
    btn.innerHTML = enabled
        ? '<i class="fa-duotone fa-solid fa-microphone"></i>'
        : '<i class="fa-duotone fa-solid fa-microphone-slash"></i>';
}

function RemoveVolume() {
    const currentlyOn = (window.audioSettings && typeof window.audioSettings.soundEnabled !== 'undefined')
        ? window.audioSettings.soundEnabled
        : (typeof soundPlaying !== 'undefined' ? soundPlaying : true);
    const nextOn = !currentlyOn;

    if (window.audioSettings) {
        window.audioSettings.soundEnabled = nextOn;
    }
    window.soundPlaying = nextOn;
    soundPlaying = nextOn;

    try {
        localStorage.setItem('reybingo_sound', nextOn ? '1' : '0');
    } catch (e) {}

    const soundsInput = document.getElementById('sounds');
    if (soundsInput) {
        soundsInput.value = nextOn ? '1' : '0';
    }
    updateVolumeButtonIcon(nextOn);

    if (nextOn && typeof audioManager !== 'undefined' && audioManager.unlockAudio) {
        audioManager.unlockAudio();
    } else if (!nextOn && typeof audioManager !== 'undefined') {
        if (audioManager.effectAudio) {
            try { audioManager.effectAudio.pause(); } catch (e) {}
        }
    }

    // Música de fondo deshabilitada permanentemente
    try {
        if (window.__bingoSoundtrack) {
            window.__bingoSoundtrack.pause();
            window.__bingoSoundtrack.currentTime = 0;
            window.__bingoSoundtrack.src = '';
            window.__bingoSoundtrack = null;
        }
    } catch (e) { /* ignore */ }

    $.ajax({
        url: site_url + 'playings/volumeSubmit',
        method: 'POST',
        data: { state: nextOn ? 1 : 0 },
        error: function () {
            console.warn('Error saving volume state');
        }
    });
}

function RemoveMicrophone() {
    const currentlyOn = (window.audioSettings && typeof window.audioSettings.narrationEnabled !== 'undefined')
        ? window.audioSettings.narrationEnabled
        : (typeof narrationPlaying !== 'undefined' ? narrationPlaying : true);
    const nextOn = !currentlyOn;

    if (window.audioSettings) {
        window.audioSettings.narrationEnabled = nextOn;
    }
    window.narrationPlaying = nextOn;
    narrationPlaying = nextOn;

    try {
        localStorage.setItem('reybingo_narration', nextOn ? '1' : '0');
    } catch (e) {}

    const narrationInput = document.getElementById('narration');
    if (narrationInput) {
        narrationInput.value = nextOn ? '1' : '0';
    }
    updateMicrophoneButtonIcon(nextOn);

    if (nextOn && typeof audioManager !== 'undefined' && audioManager.unlockAudio) {
        audioManager.unlockAudio();
    } else if (!nextOn && typeof audioManager !== 'undefined' && audioManager.stopCurrentVoice) {
        audioManager.stopCurrentVoice();
    }

    $.ajax({
        url: site_url + 'playings/microphoneSubmit',
        method: 'POST',
        data: { state: nextOn ? 1 : 0 },
        error: function () {
            console.warn('Error saving narration state');
        }
    });
}

function RemoveCheck() {
    $.ajax({
        url: site_url + 'playings/checkSubmit',
        method: 'POST',
        success: function (data) {
            if (data.status === 'success') {
                window.autoMarkEnabled = parseInt(data.autodial, 10) === 1;

                const btn = $('#btn-auto-mark');
                if (btn.length) {
                    if (window.autoMarkEnabled) {
                        btn.html('<i class="fa-duotone fa-solid fa-wand-magic-sparkles"></i>');
                    } else {
                        btn.html('<i class="fa-duotone fa-solid fa-hand"></i>');
                    }
                }

                if (window.autoMarkEnabled) {
                    if (Array.isArray(data.drawnNumbers)) {
                        window.drawnNumbers = data.drawnNumbers;
                    }
                    syncAutoMarkedNumbers(window.drawnNumbers, { animate: true, persist: false });
                    seedAutoMarkedNumbers();
                    $(".bingo-carton-number[id^='number-']").each(function () {
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
        error: function (error) {
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
    $('.emoji-button').on('click', function () {
        const emoji = $(this).data('emoji') || $(this).text();
        sendEmoji(emoji);
    });

    // Click en números del cartón (solo en modo manual)
    // Nota: silencio/micrófono se manejan solo vía onclick (RemoveVolume/RemoveMicrophone)
    // para evitar doble toggle.
    $(".bingo-carton-number").on('click', function () {
        if (!isAutoMarkEnabled()) {
            const number = $(this).data('number') || parseInt($(this).attr('id')?.replace('number-', ''), 10);
            if (number) {
                dialNumber(number);
            }
        }
    });

    // Gestión de modales
    $('.modal').on("hidden.bs.modal", function (e) {
        if ($('.modal:visible').length) {
            $('.modal-backdrop').first().css('z-index', parseInt($('.modal:visible').last().css('z-index')) - 10);
            $('body').addClass('modal-open');
        }
    }).on("show.bs.modal", function (e) {
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
        modalitiesToggleBtn.addEventListener("click", function (event) {
            setModalitiesPanelOpen(!isModalitiesPanelOpen());
            event.stopPropagation();
        });
    }

    const closeModalitiesBtn = $id("modalities-panel-close");
    if (closeModalitiesBtn) {
        closeModalitiesBtn.addEventListener("click", function (event) {
            setModalitiesPanelOpen(false);
            event.stopPropagation();
        });
    }

    const toggleBtn = $id("toggle-messages-btn");
    if (toggleBtn) {
        toggleBtn.addEventListener("click", function (event) {
            setChatPanelOpen(!isChatPanelOpen());
            event.stopPropagation();
        });
    }

    const closeChatBtn = $id("message-display-close");
    if (closeChatBtn) {
        closeChatBtn.addEventListener("click", function (event) {
            setChatPanelOpen(false);
            event.stopPropagation();
        });
    }

    document.addEventListener("click", function (event) {
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

// Variable para controlar el intervalo del poller de estado del juego
let gameStatusPollInterval = null;
let lastPostponedTime = null;

function pollGameStatusBeforeStart() {
    if (hasGameStarted()) {
        if (gameStatusPollInterval) {
            clearInterval(gameStatusPollInterval);
            gameStatusPollInterval = null;
        }
        return;
    }

    if (gameStatusPollInterval) return;

    gameStatusPollInterval = setInterval(function () {
        if (hasGameStarted()) {
            clearInterval(gameStatusPollInterval);
            gameStatusPollInterval = null;
            return;
        }

        $.get(site_url + 'playings/getGameStatus')
            .done(function (data) {
                if (data && data.status === 'success') {
                    // Si la hora/fecha del juego cambió (pospuesta)
                    const serverTimeStr = data.date + ' ' + data.time;
                    const localTargetStr = typeof window.gameDate !== 'undefined' ? window.gameDate : '';

                    if (localTargetStr && serverTimeStr !== localTargetStr) {
                        const message = `La partida se ha pospuesto 5 minutos (nueva hora de inicio: ${data.time.substring(0, 5)}).`;
                        handleGamePostponed(serverTimeStr, message);
                    }
                }
            })
            .fail(function (err) {
                console.warn("Error polling game status:", err);
            });
    }, 10000); // Cada 10 segundos
}

function handleGamePostponed(newTimeStr, messageText) {
    console.log("Game postponed received:", newTimeStr);

    // Evitar notificar múltiples veces para el mismo horario
    if (lastPostponedTime === newTimeStr) return;
    lastPostponedTime = newTimeStr;

    // Actualizar la fecha/hora del juego (acepta "Y-m-d H:i:s" o ISO)
    let normalized = String(newTimeStr || '').trim();
    if (/^\d{4}-\d{2}-\d{2} /.test(normalized)) {
        normalized = normalized.replace(' ', 'T');
    }
    window.gameDate = normalized;

    // Si la función de actualizar el countdown existe, reiniciarla con la nueva fecha
    if (typeof setupGameCountdown === 'function') {
        setupGameCountdown();
    }

    // Mostrar una alerta/notificación en el chat
    const display = document.getElementById("message-display");
    if (display) {
        const bubble = document.createElement("div");
        bubble.className = 'message-bubble system-message-postponed';
        bubble.style.cssText = 'background: rgba(255, 107, 107, 0.15); border: 1px solid rgba(255, 107, 107, 0.3); border-left: 4px solid #ff6b6b; padding: 12px; margin: 10px 0; border-radius: 12px; color: #ffecec; font-size: 0.9rem; backdrop-filter: blur(5px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); display: flex; flex-direction: column; gap: 4px; animation: slideIn 0.3s ease;';
        bubble.innerHTML = `<strong>⚠️ Notificación:</strong> ${messageText || 'La partida se ha pospuesto 5 minutos.'}`;
        display.insertBefore(bubble, display.firstChild);
        display.scrollTop = 0;
    }
}

// ==========================================
// CONFIGURACIÓN DE COUNTDOWN Y GANADORES
// ==========================================
function setupGameCountdown() {
    if (typeof intervalNextGame !== 'undefined' && intervalNextGame) {
        clearInterval(intervalNextGame);
    }
    const nextGameSpan = document.querySelector('.next-game');
    if (!nextGameSpan || typeof gameDate === 'undefined') return;

    const targetDate = new Date(gameDate);
    let winnerIndex = 0;

    function updateCountdown() {
        const now = new Date();
        const timeDiff = targetDate - now;

        if (hasGameStarted()) {
            if (intervalNextGame) {
                clearInterval(intervalNextGame);
                intervalNextGame = null;
            }
            nextGameSpan.textContent = '¡EL JUEGO HA INICIADO!';
            lastNumberGet();
            return;
        }

        if (timeDiff <= 0) {
            if (intervalNextGame) {
                clearInterval(intervalNextGame);
                intervalNextGame = null;
            }

            if (window.gameIsFinished || isGameFinishedShown) {
                nextGameSpan.textContent = (__['game finished!'] || 'JUEGO FINALIZADO').toUpperCase();
            } else {
                nextGameSpan.textContent = '¡EL JUEGO HA INICIADO!';
                lastNumberGet();
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
        // Activar sonido automáticamente durante el conteo
        if (typeof audioManager !== 'undefined' && !audioManager._unlocked) {
            audioManager.unlockAudio();
        }
    } else {
        if (window.gameIsFinished || isGameFinishedShown) {
            nextGameSpan.textContent = (__['game finished!'] || 'JUEGO FINALIZADO').toUpperCase();
        } else if (hasGameStarted()) {
            nextGameSpan.textContent = '¡EL JUEGO HA INICIADO!';
            lastNumberGet();
        } else {
            nextGameSpan.textContent = 'ESPERE QUE INICIE LA PARTIDA...';
            lastNumberGet();
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
                // Al regresar al primer plano en móviles, sincronizar estado del juego de inmediato
                if (typeof syncGameState === 'function') {
                    syncGameState('tab_resumed');
                }
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

        CONFIG.BASE_POLL_INTERVAL = 5000;
        CONFIG.CHAT_POLL_INTERVAL = 5000;
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

    // Un solo poll: acumulado (+ jugadores si hay badge)
    intervalManager.set('liveStatus', updateLiveStatus, CONFIG.LIVE_STATUS_INTERVAL || 10000);
    updateLiveStatus();

    if (window.totalNumbersGenerated !== undefined) {
        updateBallsCounter(window.totalNumbersGenerated);
    }

    if (Array.isArray(window.drawnNumbers) && window.drawnNumbers.length) {
        const initialDrawn = window.drawnNumbers
            .map(parseBallNumber)
            .filter(Boolean);
        // Si hay hasta 5 balotas cantadas al entrar (inicio de partida), reproducirlas y cantarlas en orden
        if (initialDrawn.length >= 1 && initialDrawn.length <= 5 && !bingoInProgress) {
            syncDrawnNumbersFromServer(initialDrawn, window.totalNumbersGenerated || initialDrawn.length, { animate: true });
        } else {
            numbersgenerated = initialDrawn.slice();
            lastNumbers = numbersgenerated.slice(-5);
            reconcileBallDisplay(numbersgenerated);
            markGameAsStartedFromServer(numbersgenerated.length);
            updateBallsCounter(numbersgenerated.length);
            // Si la partida ya estaba avanzada pero sigue en curso, cantar la última balota cantada
            if (!window.gameIsFinished && !isGameFinishedShown && initialDrawn.length > 0) {
                const latestBall = initialDrawn[initialDrawn.length - 1];
                enqueueBallsForPlayback([latestBall]);
            }
        }
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
        $(".bingo-carton-number[id^='number-']").each(function () {
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
        // Siempre sincronizar bolas (live admin + automática).
        // Si esperamos solo a hasGameStarted(), con Pusher caído los jugadores
        // no ven las balotas que el admin ya cantó.
        startAutomaticLast();
    }

    // Limpiar mensajes antiguos periódicamente
    intervalManager.set('messageCleanup', cleanupOldMessages, 60000); // Cada minuto

    // Iniciar el poller de estado si el juego no ha empezado
    if (!hasGameStarted()) {
        pollGameStatusBeforeStart();
    }

    // Inicializar WebSocket (Soketi self-hosted o Pusher Cloud)
    if (typeof PusherClient !== 'undefined' && typeof PUSHER_KEY !== 'undefined' && PUSHER_KEY) {
        try {
            console.log('Iniciando cliente WebSocket...');
            const pusherHelper = new PusherClient(GAME_ID, USER_ID);
            window.__bingoPusherHelper = pusherHelper;

            // Si hay SOKETI_HOST definido, conectar a Soketi self-hosted en VPS
            // Si no, usar Pusher Cloud con el cluster configurado
            var soketiHost = (typeof SOKETI_HOST !== 'undefined' && SOKETI_HOST) ? SOKETI_HOST : null;
            var soketiPort = (typeof SOKETI_PORT !== 'undefined' && SOKETI_PORT) ? SOKETI_PORT : 443;

            pusherHelper.init(PUSHER_KEY, PUSHER_CLUSTER, AUTH_URL, soketiHost, soketiPort);

            pusherHelper.on('connection:success', function () {
                setBingoPusherRealtime(true);
                if (typeof syncGameState === 'function') {
                    syncGameState('pusher_connected');
                }
            });
            pusherHelper.on('connection:failed', function () {
                setBingoPusherRealtime(false);
            });
            pusherHelper.on('connection:error', function () {
                setBingoPusherRealtime(false);
            });
            pusherHelper.on('lifecycle:resume', function () {
                if (typeof syncGameState === 'function') {
                    syncGameState('pusher_lifecycle_resume');
                }
            });
            pusherHelper.on('lifecycle:online', function () {
                if (typeof syncGameState === 'function') {
                    syncGameState('pusher_lifecycle_online');
                }
            });

            pusherHelper.on('game:postponed', function (data) {
                console.log('Pusher game:postponed received', data);
                if (data && data.new_time) {
                    handleGamePostponed(data.new_time, data.message);
                }
            });
            pusherHelper.on('game:number_drawn', function (data) {
                if (!data || window.gameIsFinished || isGameFinishedShown) {
                    return;
                }

                // Marcamos realtime activo al recibir bolas (aunque subscription_succeeded fallara en logs)
                setBingoPusherRealtime(true);

                const number = data.n ?? data.number;
                const drawn = data.drawnNumbers || data.drawn || null;
                const total = data.totalNumbersGenerated;

                if (window.ballTelemetry) {
                    const srvTs = data.serverTimestamp || data.ts || null;
                    const wsLatency = srvTs ? (Date.now() - srvTs) : null;
                    window.ballTelemetry.logBallTiming('T3_WS_RECEIVED', number, {
                        wsLatencyMs: wsLatency,
                        serverTs: srvTs,
                        visibility: document.visibilityState
                    });
                }

                if (Array.isArray(drawn) && drawn.length) {
                    syncDrawnNumbersFromServer(drawn, total !== undefined ? total : drawn.length, { animate: !bingoInProgress });
                } else if (number) {
                    handleNewNumber(number, total, drawn);
                }
            });

            // Chat en tiempo real via WebSocket
            function handleIncomingChatMessage(data) {
                if (!data) return;
                const msgId = parseInt(data.id, 10);
                const text = getMessageText(data);
                if (!text) return;

                const currentUserId = getCurrentUserId();
                const senderId = parseInt(data.userId || data.user, 10);
                const isOwn = !Number.isNaN(senderId) && senderId > 0 ? senderId === currentUserId : false;

                if (!Number.isNaN(msgId) && msgId > 0) {
                    if (messagesDisplayed.includes(msgId)) {
                        return;
                    }
                    if (pendingOutgoingMessageIds.has(msgId)) {
                        pendingOutgoingMessageIds.delete(msgId);
                        registerChatMessageId(msgId);
                        return;
                    }
                    registerChatMessageId(msgId);
                }

                displayMessage(
                    { message: text, id: Number.isNaN(msgId) || msgId <= 0 ? undefined : msgId },
                    data.profile_pic || data.image || imagePath,
                    isOwn
                );
            }

            pusherHelper.on('game:chat_message', handleIncomingChatMessage);
            pusherHelper.on('game:message', handleIncomingChatMessage);

            // Bingos cantados y aceptados en tiempo real
            pusherHelper.on('game:bingo_accepted', function (data) {
                console.log('WS game:bingo_accepted received', data);
                if (!data) return;

                const noticeKey = (data.singId ? 'sing_' + data.singId : '') ||
                                  (data.cartonId ? 'carton_' + data.cartonId + '_' + (data.modalityId || '') : '') ||
                                  ((data.player || '') + '_' + (data.modality || ''));
                if (noticeKey && window.seenBingoNotices && window.seenBingoNotices.has(noticeKey)) {
                    return;
                }

                if (Array.isArray(data.winners)) {
                    mergeWinnersFromServer(data.winners);
                }
                if (data.player && data.modality) {
                    registerWinner(data.player, data.modality);
                }

                const isCompleted = (data.gameCompleted === true || data.stopped === true);
                if (isCompleted) {
                    window.gameIsFinished = true;
                    window.allowGameUnload = true;
                    ballPlaybackQueue = [];
                    isBallPlaybackActive = false;
                    intervalManager.clear('lastNumber');
                }

                if (data.player && data.modality) {
                    if (isOwnBingoEvent(data)) {
                        bingoInProgress = true;
                        window.__lastWinnerNoticeTime = Date.now();
                        intervalManager.clear('lastNumber');

                        try {
                            if (typeof audioManager !== 'undefined' && audioManager.play) {
                                audioManager.play(audioPath + 'winner.mp3');
                            } else if (typeof playNotificationSound === 'function') {
                                playNotificationSound('sing', true);
                            }
                        } catch (e) {
                            console.warn('Error al reproducir audio de ganador:', e);
                        }
                        if (typeof window.AppcreateConfetti === 'function') {
                            window.AppcreateConfetti();
                        }
                        if (typeof window.showNotification === 'function') {
                            const modClean = (data.modality || 'Bingo').replace(/^la\s+/i, '').trim();
                            window.showNotification({
                                id: noticeKey || ('own_' + Date.now()),
                                type: 'own_sing',
                                modalityId: data.modalityId,
                                modality: data.modality,
                                player: data.player || 'Tú',
                                cartonId: data.cartonId,
                                title: '🎉 ¡BINGO CANTADO!',
                                message: `Ganadores de la modalidad ${modClean}: ${data.player || 'Tú'}`,
                                created_at: new Date().toISOString()
                            });
                        }

                        showCountdown({
                            player: data.player,
                            modality: data.modality,
                            modalityId: data.modalityId,
                            image: data.image,
                            isOwnBingo: true,
                            winnerUserId: data.winnerUserId
                        }, function () {
                            if (isCompleted || window.gameIsFinished || isGameFinishedShown) {
                                showGameFinalized();
                                return;
                            }
                            startAutomaticLast();
                        });
                    } else {
                        showOtherPlayerBingoNotice(data, function () {
                            if (isCompleted || window.gameIsFinished || isGameFinishedShown) {
                                showGameFinalized();
                            }
                        });
                    }
                } else if (isCompleted) {
                    showGameFinalized();
                }
            });

            pusherHelper.on('game:bingo_claimed', function (data) {
                console.log('WS game:bingo_claimed received', data);
                if (!data) return;
                if (Array.isArray(data.winners)) {
                    mergeWinnersFromServer(data.winners);
                }
            });

            // Inicio de partida en tiempo real
            pusherHelper.on('game:started', function (data) {
                console.log('WS game:started received', data);
                markGameAsStartedFromServer((data && data.drawnCount) || 1);
                lastNumberGet();
            });

            // Fin de partida en tiempo real
            pusherHelper.on('game:game_finished', function (data) {
                console.log('WS game:game_finished received', data);
                window.gameIsFinished = true;
                window.allowGameUnload = true;
                ballPlaybackQueue = [];
                isBallPlaybackActive = false;
                intervalManager.clear('lastNumber');

                // Si hay un cante de bingo celebrándose en este momento, no cortar la celebración
                if (bingoInProgress || (window.__lastWinnerNoticeTime && (Date.now() - window.__lastWinnerNoticeTime < 4000))) {
                    return;
                }

                if (typeof audioManager !== 'undefined' && audioManager.stopAll) {
                    audioManager.stopAll();
                }
                showGameFinalized();
            });

            pusherHelper.on('game:completed', function (data) {
                console.log('WS game:completed received', data);
                window.gameIsFinished = true;
                window.allowGameUnload = true;
                ballPlaybackQueue = [];
                isBallPlaybackActive = false;
                intervalManager.clear('lastNumber');

                if (bingoInProgress || (window.__lastWinnerNoticeTime && (Date.now() - window.__lastWinnerNoticeTime < 4000))) {
                    return;
                }

                if (typeof audioManager !== 'undefined' && audioManager.stopAll) {
                    audioManager.stopAll();
                }
                showGameFinalized();
            });

            // Reinicio de partida
            pusherHelper.on('game:game_reset', function () {
                console.log('WS game:game_reset received');
                location.reload();
            });
        } catch (pe) {
            console.warn('Error inicializando PusherClient en juego:', pe);
            setBingoPusherRealtime(false);
        }
    } else {
        setBingoPusherRealtime(false);
    }

    console.log('Bingo App with Enhanced Chat initialized successfully');
}

// ==========================================
// EVENT LISTENERS PRINCIPALES
// ==========================================

// Inicialización cuando el DOM esté listo (también si el script carga tras DOMContentLoaded)
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
}

// Manejo prioritario del botón home (casita) para salir inmediatamente sin trabas ni cuadros de ganadores
$(document).on('click', '.btn-home', function (e) {
    window.__userLeavingGame = true;
    if (window.awardsModalTimeoutId) {
        clearTimeout(window.awardsModalTimeoutId);
        window.awardsModalTimeoutId = null;
    }
    const modalAwardsEl = document.getElementById('modalAwards');
    if (modalAwardsEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const bsModal = bootstrap.Modal.getInstance(modalAwardsEl);
        if (bsModal) {
            bsModal.hide();
        }
    }
    const container = document.getElementById('game-finalized');
    if (container) {
        container.style.display = 'none';
    }
    window.allowGameUnload = true;
    const playUrl = typeof site_url !== 'undefined' ? site_url + 'play' : '/play';
    const targetUrl = $(this).attr('href') || playUrl;
    window.location.replace(targetUrl);
    return false;
});

console.log('Bingo App script loaded successfully');
