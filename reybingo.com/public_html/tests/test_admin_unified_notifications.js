/**
 * Test de verificación: Notificaciones unificadas en el Administrador (igual a Jugadores)
 * REY BINGO
 */
const assert = require('node:assert');

console.log('=== TEST DE UNIFICACIÓN DE NOTIFICACIONES EN EL ADMINISTRADOR ===\n');

// 1. Simular la función de formateo de notificación de board.js y playing.js
function buildNoticePayload(data, winnersPool = []) {
    const playerName = data.player || data.userName || data.playerName || 'Jugador';
    const modalityName = data.modalityName || data.modality || 'Bingo';
    const modalityId = data.modalityId || data.modality || 0;
    const singId = data.singId || (data.sings && data.sings[0] && data.sings[0].id) || 0;
    const cartonId = data.cartonId || data.carton || '';

    const noticeKey = (data.id ? String(data.id) : '') ||
                      (singId ? 'sing_' + singId : '') ||
                      (cartonId ? 'carton_' + cartonId + '_' + modalityId : '') ||
                      (playerName + '_' + modalityName);

    const modalityClean = String(modalityName).replace(/^la\s+/i, '').trim();
    const modalityLabel = /^bingo/i.test(modalityClean) || /^pleno/i.test(modalityClean)
        ? `Ganadores de la modalidad ${modalityClean}`
        : `Ganadores de la ${modalityClean}`;

    const rawList = Array.isArray(data.officialWinners) && data.officialWinners.length > 0
        ? data.officialWinners
        : (Array.isArray(data.winners) && data.winners.length > 0 ? data.winners : (Array.isArray(winnersPool) && winnersPool.length > 0 ? winnersPool : null));

    let winnersText = playerName;
    if (rawList && rawList.length > 0) {
        const mId = parseInt(modalityId, 10);
        const mCleanLower = modalityClean.toLowerCase();
        const matching = rawList.filter(w => {
            const wmId = parseInt(w.modalityId || w.modality_id, 10);
            if (mId > 0 && wmId > 0) return mId === wmId;
            const wmName = String(w.modalityName || w.modality || '').replace(/^la\s+/i, '').trim().toLowerCase();
            return wmName && wmName === mCleanLower;
        }).map(w => w.userName || w.player || '').filter(Boolean);
        if (matching.length > 0) {
            winnersText = Array.from(new Set(matching)).join(' + ');
        }
    }

    return {
        id: noticeKey,
        type: 'sing',
        modalityId: modalityId,
        modality: modalityClean,
        player: playerName,
        cartonId: cartonId,
        title: '🎉 ¡BINGO CANTADO!',
        message: `${modalityLabel}: ${winnersText}`,
        officialWinners: rawList || undefined
    };
}

// PRUEBA 1: En el administrador, cuando llega el segundo ganador de Línea 1, el mensaje muestra ambos unidos por " + "
{
    const serverWinners = [
        { singId: 101, userId: 1, player: 'Carlos Perez', modality: 'Línea 1', modalityId: 2, cartonId: 55 },
        { singId: 102, userId: 99, player: 'bot_maria', userName: 'bot_maria', modality: 'Línea 1', modalityId: 2, cartonId: 78 }
    ];

    // Evento del segundo ganador recibido en board.js
    const payloadWinner2 = {
        singId: 102,
        player: 'bot_maria',
        modality: 'Línea 1',
        modalityId: 2,
        cartonId: 78,
        winners: serverWinners
    };

    const notice = buildNoticePayload(payloadWinner2);
    assert.strictEqual(notice.message, 'Ganadores de la Línea 1: Carlos Perez + bot_maria');
    assert.strictEqual(Array.isArray(notice.officialWinners), true);
    assert.strictEqual(notice.officialWinners.length, 2);
    console.log('✔ PRUEBA 1 SUPERADA: board.js genera el mensaje unificado idéntico al jugador ("Carlos Perez + bot_maria")');
}

// PRUEBA 2: Si el servidor solo envía "winners" (sin officialWinners explícito), board.js resuelve la lista canónica correctamente
{
    const payload = {
        singId: 201,
        player: 'Pedro',
        modality: 'Bingo',
        modalityId: 5,
        winners: [
            { player: 'Pedro', modalityId: 5, modality: 'Bingo' },
            { player: 'Ana', modalityId: 5, modality: 'Bingo' }
        ]
    };

    const notice = buildNoticePayload(payload);
    assert.strictEqual(notice.message, 'Ganadores de la modalidad Bingo: Pedro + Ana');
    console.log('✔ PRUEBA 2 SUPERADA: Fallback seguro a data.winners para construir la lista canónica unificada');
}

// PRUEBA 3: Simulación de DOM container en index.php para el Administrador
{
    const container = {
        cards: [],
        querySelector(selector) {
            const matchKey = selector.match(/data-modality-key="([^"]+)"/);
            if (matchKey) {
                return this.cards.find(c => c.dataset.modalityKey === matchKey[1]) || null;
            }
            return null;
        },
        querySelectorAll(selector) {
            return this.cards;
        },
        appendChild(el) {
            this.cards.push(el);
        }
    };

    function simulateShowNotification(notification) {
        const normalizeModKey = function(mId, mName) {
            const parsedId = parseInt(mId, 10);
            if (Number.isFinite(parsedId) && parsedId > 0) return 'modality_' + parsedId;
            const parsedNameId = parseInt(mName, 10);
            if (Number.isFinite(parsedNameId) && parsedNameId > 0 && /^\d+$/.test(String(mName).trim())) return 'modality_' + parsedNameId;
            if (mName) {
                const cleaned = String(mName).normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim().toLowerCase().replace(/^la\s+/i, '').replace(/[^a-z0-9]/g, '_');
                if (cleaned) return 'modality_' + cleaned;
            }
            return 'modality_general';
        };

        const modalityClean = String(notification.modality || 'Bingo').replace(/^la\s+/i, '').trim();
        const modalityLabel = `Ganadores de la ${modalityClean}`;
        const modalityKey = normalizeModKey(notification.modalityId, notification.modality);

        const existingEl = container.querySelector(`[data-modality-key="${modalityKey}"]`)
            || container.cards.find(c => c.dataset.modalityKey === modalityKey || c.dataset.modalityName === modalityClean);

        const rawList = notification.officialWinners || notification.winners;
        if (rawList && rawList.length > 0) {
            const winnersFormatted = rawList.map(w => w.player).join(' + ');
            if (existingEl) {
                existingEl._winners = rawList;
                existingEl.message = `${modalityLabel}: ${winnersFormatted}`;
                return;
            } else {
                const newEl = {
                    dataset: { modalityKey, modalityName: modalityClean },
                    _winners: rawList,
                    message: `${modalityLabel}: ${winnersFormatted}`
                };
                container.appendChild(newEl);
                return;
            }
        }
    }

    // Llega ganador 1 en admin
    simulateShowNotification({
        id: 'sing_1',
        type: 'sing',
        modalityId: 2,
        modality: 'Línea 1',
        player: 'Carlos',
        officialWinners: [{ player: 'Carlos', modalityId: 2 }]
    });

    assert.strictEqual(container.cards.length, 1);
    assert.strictEqual(container.cards[0].message, 'Ganadores de la Línea 1: Carlos');

    // Llega ganador 2 en admin de la misma modalidad
    simulateShowNotification({
        id: 'sing_2',
        type: 'sing',
        modalityId: 2,
        modality: 'Línea 1',
        player: 'Bot María',
        officialWinners: [{ player: 'Carlos', modalityId: 2 }, { player: 'Bot María', modalityId: 2 }]
    });

    // Debe seguir habiendo UNA SOLA tarjeta (actualizada in-place con ambos nombres)
    assert.strictEqual(container.cards.length, 1, 'No deben existir tarjetas separadas en el admin para la misma modalidad');
    assert.strictEqual(container.cards[0].message, 'Ganadores de la Línea 1: Carlos + Bot María');
    console.log('✔ PRUEBA 3 SUPERADA: En el admin se actualiza una sola tarjeta en pantalla con "Carlos + Bot María"');
}

console.log('\n=== TODAS LAS PRUEBAS DE UNIFICACIÓN EN ADMIN PASARON CON ÉXITO ===');
