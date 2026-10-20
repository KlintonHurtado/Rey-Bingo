/**
 * Pruebas de regresión con DOM real, sin dependencias.
 * Ejecutar: node tests/test_modality_winners.js
 * Abrir: http://127.0.0.1:8765 y comprobar el resultado.
 */
const http = require('node:http');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const clients = {};

function extractFunction(source, name) {
    const match = source.match(new RegExp('^([ \\t]*)function ' + name + '\\([^]*?^\\1}', 'm'));
    if (!match) throw new Error('No se encontró la función ' + name);
    return match[0];
}

for (const name of ['board', 'live', 'playing']) {
    const source = fs.readFileSync(path.join(root, 'public/assets/js', name + '.js'), 'utf8');
    const functions = ['mergeWinnersFromServer', 'showOtherPlayerBingoNotice'];
    functions.push(name === 'live' ? 'lastNumberGet' : 'processNumberGetResponse');
    if (name === 'live') functions.push('getBingoDedupeKey');
    const finished = { board: 'handleAdminGameFinished', live: 'handleLiveGameFinished', playing: 'handlePlayingGameFinished' }[name];
    functions.push(finished);
    const accepted = source.match(/^([ \t]*)(?:channel\.bind|pusherHelper\.on)\('game:bingo_accepted', (function[^]*?)^\1}\);/m);
    if (!accepted) throw new Error('No se encontró bingo_accepted en ' + name);
    clients[name] = functions.map(fn => extractFunction(source, fn)).join('\n')
        + '\nconst accept = ' + accepted[2] + '};'
        + '\nreturn { mergeWinnersFromServer, showOtherPlayerBingoNotice, poll: '
        + (name === 'live' ? 'lastNumberGet' : 'processNumberGetResponse')
        + ', finish: ' + finished + ', accept };';
}

// Usar los estilos de producción para comprobar que la clase realmente pinta gris.
const styles = ['boards/board', 'boards/live', 'playings/playing'].map(view => {
    const source = fs.readFileSync(path.join(root, 'app/Views', view + '.php'), 'utf8');
    return source.match(/[^{}\n]*\.border-carton\.modality-won\s*\{[^}]*}/g).join('\n');
}).join('\n');

function runTests(clients) {
    const fixture = document.getElementById('fixture');
    const results = document.getElementById('results');
    let passed = 0;
    let failed = 0;

    function assert(condition, message) {
        if (!condition) throw new Error(message);
    }

    function card(id, name, label = 'span') {
        const cells = Array.from({ length: 25 }, (_, i) =>
            `<div class="card-number ${i < 5 ? 'modality-sing' : ''}" data-position="${i + 1}">${i === 12 ? '⭐️' : ''}</div>`
        ).join('');
        return `<div class="border-carton"><${label} class="${label === 'span' ? 'modality-name' : ''}">${name}</${label}>
            <div class="carton" id="modality-${id}">${'BINGO'.split('').map(letter =>
                `<div class="card-letter"><span>${letter}</span></div>`).join('')}${cells}</div>
            <${label} id="modality-amount-${id}">10</${label}></div>`;
    }

    function reset(label = 'span', extra = '') {
        fixture.innerHTML = `<div class="modalities-display"><div class="container-cartons-modalities">
            ${card(1, 'Bingo', label)}${card(2, 'Línea', label)}${card(3, 'Línea doble', label)}
            ${card(4, 'La X', label)}${extra}</div></div>
            <div id="unrelated">${card(99, 'Bingo', label)}</div>`;
    }

    function wonIds() {
        return Array.from(fixture.querySelectorAll('.modality-won .carton')).map(el => el.id).sort();
    }

    function expectWon(ids) {
        const expected = ids.map(id => 'modality-' + id).sort();
        assert(JSON.stringify(wonIds()) === JSON.stringify(expected),
            `Esperado ${expected}; recibido ${wonIds()}`);
        for (const id of ids) {
            fixture.querySelectorAll(`[id="modality-${id}"]`).forEach(el => {
                assert(el.classList.contains('cartn-sing'), 'Falta el estado del cartón ganador');
                assert(el.querySelectorAll('.modality-sing.sing').length === 5, 'Faltan posiciones ganadas');
                assert(Array.from(el.querySelectorAll('.modality-sing')).every(cell => cell.textContent === '⭐️'),
                    'Faltan estrellas');
                assert(getComputedStyle(el.closest('.border-carton')).filter.includes('grayscale(1)'),
                    'La tarjeta no se ve gris');
            });
        }
        assert(!fixture.querySelector('#unrelated .modality-won'), 'Se alteró un cartón ajeno');
        assert(!fixture.querySelector('[id^="modality-amount-"].cartn-sing'), 'Se marcó el premio como cartón');
    }

    function test(name, callback) {
        try {
            reset();
            callback();
            passed++;
            results.textContent += 'OK ' + name + '\n';
        } catch (error) {
            failed++;
            results.textContent += 'ERROR ' + name + ': ' + error.message + '\n';
        }
    }

    for (const label of ['span', 'small']) {
        test(label + ': ID numérico y texto; solo la ganadora', () => {
            reset(label);
            markModalityCardWon(2, 'Bingo');
            markModalityCardWon('2', 'Bingo');
            expectWon([2]);
        });
        test(label + ': Bingo por nombre no coincide con todas las letras B', () => {
            reset(label);
            markModalityCardWon(null, 'Bingo');
            expectWon([1]);
        });
        test(label + ': nombres parecidos no se confunden', () => {
            reset(label);
            markModalityCardWon(null, '  LA   LINEA  ');
            expectWon([2]);
        });
        test(label + ': no se usa el nombre si el ID pertenece a otra modalidad', () => {
            reset(label);
            markModalityCardWon(100, 'Bingo');
            expectWon([]);
        });
    }

    test('Nombres vacíos, parciales o caracteres de selector no marcan tarjetas', () => {
        for (const name of ['', ' ', 'B', 'Líne', '⭐️', '10', '"] , .carton']) {
            markModalityCardWon(undefined, name);
        }
        expectWon([]);
    });
    test('Nombres ambiguos entre modalidades no marcan ninguna', () => {
        reset('span', card(5, 'Línea'));
        markModalityCardWon(null, 'Línea');
        expectWon([]);
        markModalityCardWon(5, 'Línea');
        expectWon([5]);
    });
    test('Copias de la misma modalidad se actualizan juntas', () => {
        reset('span', card(2, 'Línea'));
        markModalityCardWon(null, 'Línea');
        expectWon([2, 2]);
    });

    function client(name, response = {}) {
        const win = { numberSingsLimit: 2, seenBingoNotices: new Set(['sing_7', 'game__sing_7']), gameIsFinished: false };
        const noop = () => {};
        const jquery = () => ({ hide: noop });
        jquery.get = () => ({
            done(callback) { callback(response); return this; },
            fail() { return this; }
        });
        const stubs = `
            let winners = [];
            let isGameFinishedShown = false, bingoPauseInProgress = false, bingoInProgress = false;
            let ballPlaybackQueue = [], isBallPlaybackActive = false, countdownTimeoutId = null;
            const site_url = '/', intervalManager = {clear: noop};
            const startAutomaticLast = noop, stopAutomaticLast = noop, stopAutomaticGeneration = noop;
            const applyNumberGetMeta = noop, applyAutoMarkPreferenceFromServer = noop;
            const showGameFinalized = noop, registerWinner = noop;
            const bingoIsBallBackingOff = () => false;
            const setTimeout = noop, console = {log: noop};
        `;
        return new Function('window', '$', 'noop', stubs + clients[name])(win, jquery, noop);
    }

    for (const name of Object.keys(clients)) {
        test(name + ': restaurar varios ganadores sin pintar las pendientes', () => {
            client(name).mergeWinnersFromServer([
                { player: 'Ana', modalityId: 1, modality: 'Bingo' },
                { player: 'Luis', modalityId: 3, modality: 'Línea doble' }
            ]);
            expectWon([1, 3]);
        });
        test(name + ': estado inicial con ID numérico y nombre separado', () => {
            client(name).mergeWinnersFromServer([{ player: 'Ana', modality: 2, modality_name: 'Línea' }]);
            expectWon([2]);
        });
        test(name + ': la modalidad se marca aunque falte el nombre del jugador', () => {
            client(name).mergeWinnersFromServer([null, { modalityId: 4 }]);
            expectWon([4]);
        });
        test(name + ': aviso repetido aún sincroniza todas las modalidades recibidas', () => {
            client(name).showOtherPlayerBingoNotice({
                singId: 7, modalityId: 2, modality: 'Línea',
                winners: [{ player: 'Ana', modalityId: 1, modality: 'Bingo' }]
            });
            expectWon([1, 2]);
        });
        test(name + ': polling normal recupera un evento perdido', () => {
            const response = { status: 'success', winners: [{ player: 'Ana', modalityId: 2, modality: 'Línea' }] };
            client(name, response).poll(response);
            expectWon([2]);
        });
        test(name + ': WebSocket repetido no omite el marcado', () => {
            client(name).accept({
                singId: 7, modalityId: 3, modality: 'Línea doble',
                winners: [{ player: 'Ana', modalityId: 2, modality: 'Línea' }]
            });
            expectWon([2, 3]);
        });
        test(name + ': fin de partida recupera todos los ganadores', () => {
            client(name).finish({
                winners: [{ modalityId: 1 }, { modalityId: 4 }]
            });
            expectWon([1, 4]);
        });
    }

    reset();
    markModalityCardWon(2, 'Línea');
    document.getElementById('summary').textContent = `${passed} correctas, ${failed} fallidas`;
    document.title = failed ? 'ERROR - Modalidades' : 'OK - Modalidades';
}

const html = `<!doctype html><html lang="es"><meta charset="utf-8"><title>Pruebas de modalidades</title>
<style>${styles}
body{font:16px Arial;padding:24px;background:#fff;color:#222}
.container-cartons-modalities{display:flex;gap:16px}.border-carton{border:2px solid #6236ff;padding:12px}
.carton{display:grid;grid-template-columns:repeat(5,24px)}
.card-letter,.modality-sing{background:#e2c6ff}.card-number{height:24px;border:1px solid #aaa}
#unrelated{display:none}#results{line-height:1.6}
</style><h1>Pruebas de modalidades ganadas</h1><h2 id="summary">Ejecutando...</h2>
<div id="fixture"></div><pre id="results"></pre>
<script src="/modality-winners.js"></script>
<script>(${runTests.toString()})(${JSON.stringify(clients).replace(/</g, '\\u003c')});</script></html>`;

http.createServer((request, response) => {
    if (request.url === '/modality-winners.js') {
        response.writeHead(200, { 'Content-Type': 'text/javascript; charset=utf-8', 'Cache-Control': 'no-store' });
        response.end(fs.readFileSync(path.join(root, 'public/assets/js/modality-winners.js')));
    } else if (request.url === '/') {
        response.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store' });
        response.end(html);
    } else {
        response.writeHead(404);
        response.end();
    }
}).listen(8765, '127.0.0.1', () => console.log('Pruebas: http://127.0.0.1:8765'));
