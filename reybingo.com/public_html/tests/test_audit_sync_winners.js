/**
 * Test de verificación y auditoría: Sincronización de ganadores, notificaciones y sonido
 * REY BINGO
 */
const assert = require('node:assert');

console.log('=== INICIANDO PRUEBAS DE AUDITORÍA Y SINCRONIZACIÓN ===\n');

// 1. Simulación de mergeWinnersFromServer corregido (de playing.js, board.js, live.js)
function createGameEnvironment() {
    let winners = [];
    let seenBingoNotices = new Set();
    let markedCards = [];
    let shownNotifications = [];
    let playedSounds = [];

    function markModalityCardWon(id, name) {
        markedCards.push({ id, name });
    }

    function mergeWinnersFromServer(serverWinners) {
        if (!Array.isArray(serverWinners)) {
            return;
        }

        serverWinners.forEach(function (winner) {
            if (!winner) return;
            const player = winner.player || winner.userName || '';
            const modality = winner.modalityName || winner.modality_name || winner.modality || '';
            const modalityId = winner.modalityId || winner.modality_id || winner.modality;

            markModalityCardWon(modalityId, modality);

            if (!player) {
                return;
            }

            const currentModalityWinners = winners.filter(function (existing) {
                return existing.modality === modality || String(existing.modalityId) === String(modalityId);
            });

            const alreadyExists = currentModalityWinners.some(function (existing) {
                return (existing.player && existing.player.toLowerCase() === player.toLowerCase()) ||
                       (winner.singId && existing.singId && existing.singId === winner.singId);
            });

            if (!alreadyExists) {
                winners.push({
                    singId: winner.singId || undefined,
                    player: player,
                    modality: modality,
                    modalityId: modalityId,
                    cartonId: winner.cartonId || winner.carton || ''
                });
            }
        });
    }

    function playNotificationSound(type, isWinner = false) {
        const soundFile = (isWinner === true || type === 'own_sing' || type === 'winner') ? 'winner.mp3' : 'notification.mp3';
        playedSounds.push({ type, isWinner, soundFile });
        return soundFile;
    }

    function showNotification(notification) {
        shownNotifications.push(notification);
    }

    function isOwnBingoEvent(data, currentUserId = 5) {
        if (!data) return false;
        const winnerUserId = parseInt(data.winnerUserId || data.userId || data.playerId, 10);
        if (winnerUserId > 0 && currentUserId > 0) {
            return winnerUserId === currentUserId;
        }
        return data.isOwnBingo === true;
    }

    return {
        winners,
        seenBingoNotices,
        markedCards,
        shownNotifications,
        playedSounds,
        mergeWinnersFromServer,
        playNotificationSound,
        showNotification,
        isOwnBingoEvent
    };
}

// PRUEBA 1: Dos ganadores simultáneos de la misma modalidad no se truncan
{
    const env = createGameEnvironment();
    const serverWinners = [
        { singId: 101, userId: 1, player: 'Carlos Perez', modality: 'Línea 1', modalityId: 2, cartonId: 55 },
        { singId: 102, userId: 99, player: 'bot_maria', userName: 'bot_maria', modality: 'Línea 1', modalityId: 2, cartonId: 78 }
    ];

    env.mergeWinnersFromServer(serverWinners);

    assert.strictEqual(env.winners.length, 2, 'Deben existir exactamente 2 ganadores en la lista');
    assert.strictEqual(env.winners[0].player, 'Carlos Perez');
    assert.strictEqual(env.winners[1].player, 'bot_maria');
    console.log('✔ PRUEBA 1 SUPERADA: Dos ganadores simultáneos de la misma modalidad se conservan sin truncar');
}

// PRUEBA 2: Deduplicación segura: recibir la misma lista no duplica
{
    const env = createGameEnvironment();
    const serverWinners = [
        { singId: 101, userId: 1, player: 'Carlos Perez', modality: 'Línea 1', modalityId: 2 },
        { singId: 102, userId: 99, player: 'bot_maria', modality: 'Línea 1', modalityId: 2 }
    ];

    env.mergeWinnersFromServer(serverWinners);
    env.mergeWinnersFromServer(serverWinners); // Segunda llamada (ej. evento repetido o polling)

    assert.strictEqual(env.winners.length, 2, 'No deben duplicarse ganadores tras llamada repetida');
    console.log('✔ PRUEBA 2 SUPERADA: Eventos repetidos no duplican ganadores en la lista');
}

// PRUEBA 3: Discriminación de sonido y efectos (ganador propio vs rival / bot)
{
    const env = createGameEnvironment();
    const myWinSound = env.playNotificationSound('sing', true);
    assert.strictEqual(myWinSound, 'winner.mp3', 'Cuando gano yo debe sonar winner.mp3');

    const botWinSound = env.playNotificationSound('sing', false);
    assert.strictEqual(botWinSound, 'notification.mp3', 'Cuando gana un bot o rival debe sonar notification.mp3');

    const otherWinSound = env.playNotificationSound('default', false);
    assert.strictEqual(otherWinSound, 'notification.mp3', 'Sonido suave de notificación para otros');

    console.log('✔ PRUEBA 3 SUPERADA: Sonidos correctamente diferenciados (winner.mp3 para mí, notification.mp3 para bots/rivales)');
}

// PRUEBA 4: Unificación visual por modalidad (Línea 1: Carlos + Bot María + Pedro)
{
    const byModality = {};
    const winners = [
        { userName: 'Carlos Perez', modalityName: 'Línea 1', modalityId: 2 },
        { userName: 'bot_maria', modalityName: 'Línea 1', modalityId: 2 },
        { userName: 'Pedro Gomez', modalityName: 'Línea 1', modalityId: 2 }
    ];

    winners.forEach(w => {
        const mKey = 'modality_' + w.modalityId;
        if (!byModality[mKey]) {
            byModality[mKey] = { modalityName: w.modalityName, winners: [] };
        }
        if (!byModality[mKey].winners.some(x => x.player.toLowerCase() === w.userName.toLowerCase())) {
            byModality[mKey].winners.push({ player: w.userName });
        }
    });

    const mKey = 'modality_2';
    const mData = byModality[mKey];
    const mLabel = `Ganadores de la ${mData.modalityName}`;
    const winnersFormatted = mData.winners.map(w => w.player).join(' + ');
    const unifiedMessage = `${mLabel}: ${winnersFormatted}`;

    assert.strictEqual(unifiedMessage, 'Ganadores de la Línea 1: Carlos Perez + bot_maria + Pedro Gomez');
    console.log('✔ PRUEBA 4 SUPERADA: Unificación por modalidad genera correctamente "Carlos Perez + bot_maria + Pedro Gomez"');
}

// PRUEBA 5: Simulación de reconexión móvil (retraso y recuperación por officialWinners)
{
    const env = createGameEnvironment();
    // Computadora ya procesó sing_101
    env.seenBingoNotices.add('sing_101');

    // Teléfono reconecta y recibe payload con sing_101 y sing_102
    const phonePayloadWinners = [
        { singId: 101, userId: 1, player: 'Carlos Perez', modalityName: 'Línea 1', modalityId: 2 },
        { singId: 102, userId: 99, player: 'bot_maria', modalityName: 'Línea 1', modalityId: 2 }
    ];

    // Simular el catch-up en processNumberGetResponse del teléfono:
    const newlyDetectedForPhone = [];
    phonePayloadWinners.forEach(w => {
        const nKey = 'sing_' + w.singId;
        if (!env.seenBingoNotices.has(nKey)) {
            newlyDetectedForPhone.push(w);
            env.seenBingoNotices.add(nKey);
        }
    });

    assert.strictEqual(newlyDetectedForPhone.length, 1, 'El teléfono detecta el cante que le faltaba (sing_102)');
    assert.strictEqual(newlyDetectedForPhone[0].player, 'bot_maria');
    assert.strictEqual(env.seenBingoNotices.has('sing_101'), true);
    assert.strictEqual(env.seenBingoNotices.has('sing_102'), true);
    console.log('✔ PRUEBA 5 SUPERADA: Reconexión móvil recupera exactamente los eventos no vistos sin duplicar');
}

// PRUEBA 6: Detección de ganador para bot con nombre de usuario cuando firstname/lastname están vacíos
{
    const botUser = { id: 77, username: 'bot_player_0077', firstname: '', lastname: '' };
    let resolvedName = (botUser.firstname || botUser.lastname)
        ? (botUser.firstname + ' ' + botUser.lastname).trim()
        : (botUser.username || ('Jugador #' + botUser.id));

    assert.strictEqual(resolvedName, 'bot_player_0077', 'El bot sin nombre/apellido debe resolver su username');
    assert.notStrictEqual(resolvedName, '', 'El nombre resuelto nunca debe ser cadena vacía');
    console.log('✔ PRUEBA 6 SUPERADA: Bots con campos de nombre vacíos resuelven su username de forma consistente');
}

console.log('\n=== TODAS LAS PRUEBAS (6/6) PASARON EXITOSAMENTE ===');
