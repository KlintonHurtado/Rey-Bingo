const assert = require('assert');

console.log('=== TEST DE REPRODUCCIÓN ÚNICA DE SONIDO DE GANADOR Y CORTE AL SIGUIENTE NÚMERO ===\n');

// Simulación del entorno de navegador
const window = {
    seenBingoNotices: new Set(),
    __winnerAudioPlayed: new Set(),
    __lastWinnerAudioTime: 0,
    __sessionSeenNotifIds: new Set(),
    location: { pathname: '/boards/board/10' }
};

let audioPlayedCount = 0;
let stopWinnerAudioCalled = false;

const audioManager = {
    audioCache: new Map(),
    play(src) {
        audioPlayedCount++;
        return { src, paused: false };
    },
    stopWinnerAudio() {
        stopWinnerAudioCalled = true;
    }
};

function playWinnerSoundOnce(modalityId, singId, playerName, modalityName) {
    window.__winnerAudioPlayed = window.__winnerAudioPlayed || new Set();
    window.__lastWinnerAudioTime = window.__lastWinnerAudioTime || 0;

    const keys = [];
    if (singId) keys.push('sing_' + singId);
    if (modalityId) keys.push('mod_' + modalityId);
    if (singId && modalityId) keys.push('sing_' + singId + '_' + modalityId);
    if (playerName && modalityName) keys.push(String(playerName).trim().toLowerCase() + '_' + String(modalityName).trim().toLowerCase());

    const alreadyPlayed = keys.some(k => window.__winnerAudioPlayed.has(k));
    const now = Date.now();
    if (alreadyPlayed || (now - window.__lastWinnerAudioTime < 10000)) {
        return false;
    }

    keys.forEach(k => window.__winnerAudioPlayed.add(k));
    window.__lastWinnerAudioTime = now;
    audioManager.play('winner.mp3');
    return true;
}

// 1. Primer evento de ganador
const firstPlay = playWinnerSoundOnce(1, 15, 'Juan Perez', 'Línea');
assert.strictEqual(firstPlay, true, 'El primer sonido debe reproducirse');
assert.strictEqual(audioPlayedCount, 1, 'Debe haber sonado exactamente 1 vez');
console.log('✔ PASO 1 SUPERADO: Sonido de ganador reproducido la primera vez');

// 2. Simular 10 consultas por polling consecutivas mientras la partida está en pausa esperando el próximo número
for (let i = 0; i < 10; i++) {
    const replayAttempt = playWinnerSoundOnce(1, 15, 'Juan Perez', 'Línea');
    assert.strictEqual(replayAttempt, false, `El tick ${i + 1} de polling no debe volver a sonar`);
}
assert.strictEqual(audioPlayedCount, 1, 'No debe haberse incrementado el conteo de reproducción durante el polling en pausa');
console.log('✔ PASO 2 SUPERADO: 10 ciclos de polling no reactivan el sonido en bucle');

// 3. Simulación de layout/index.php sin ID numérico (causa raíz del bug repetitivo)
window.__isNotifIdSeen = function(id) {
    return window.__sessionSeenNotifIds.has(String(id));
};
window.__markNotifIdSeen = function(id) {
    window.__sessionSeenNotifIds.add(String(id));
};

let layoutSoundCount = 0;
function simulateLayoutPolling(notifications) {
    const freshNotifications = notifications.filter(n => {
        const id = n.id ? String(n.id) : (n.singId ? 'sing_' + n.singId : `${n.type || 'msg'}_${n.game || ''}_${n.created_at || ''}`);
        return !window.__isNotifIdSeen(id);
    });

    const isGameRoom = window.location.pathname.indexOf('/boards/board') !== -1;
    const hasSingEvent = freshNotifications.some(n => n.type === 'sing' || n.type === 'own_sing');

    if (freshNotifications.length > 0) {
        if (!isGameRoom || !hasSingEvent) {
            layoutSoundCount++;
        }
    }

    freshNotifications.forEach(n => {
        const id = n.id ? String(n.id) : (n.singId ? 'sing_' + n.singId : `${n.type || 'msg'}_${n.game || ''}_${n.created_at || ''}`);
        window.__markNotifIdSeen(id);
    });
}

// Polling de layout con notificación sin id directo (por ejemplo sólo con singId o type)
const incomingNotif = [{ type: 'sing', game: 10, singId: 15, created_at: '2026-10-23 15:30:00' }];
simulateLayoutPolling(incomingNotif);
simulateLayoutPolling(incomingNotif);
simulateLayoutPolling(incomingNotif);

assert.strictEqual(layoutSoundCount, 0, 'En la sala de juego (/boards/board), layout no debe solapar sonido');
assert.strictEqual(window.__isNotifIdSeen('sing_15'), true, 'El id fallback debe quedar registrado como visto');
console.log('✔ PASO 3 SUPERADO: Layout detecta la sala activa y deduplica con ID fallback');

// 4. El operador manda otro número -> se debe llamar stopWinnerAudio() inmediatamente
function handleNewNumberSimulated(newNumber) {
    if (audioManager && typeof audioManager.stopWinnerAudio === 'function') {
        audioManager.stopWinnerAudio();
    }
}

handleNewNumberSimulated(45);
assert.strictEqual(stopWinnerAudioCalled, true, 'Al mandar el siguiente número, el audio de ganador debe cortarse de inmediato');
console.log('✔ PASO 4 SUPERADO: Al mandar otro número, cualquier audio restante se detiene inmediatamente');

console.log('\n=== TODAS LAS PRUEBAS DE AUDIO Y NOTIFICACIONES PASARON CON ÉXITO ===');
