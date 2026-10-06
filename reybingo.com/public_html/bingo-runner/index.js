/**
 * Bingo Runner Daemon - Precision Scheduler Edition (Node.js)
 *
 * Microservicio en segundo plano de alta precisión:
 * 1. Sincroniza partidas automáticas activas consultando /cron/active-auto-games
 * 2. Mantiene un scheduler por timestamp objetivo (nextBallAt) con setTimeout auto-correctivo
 * 3. Cero deriva acumulativa: nextBallAt = target_previo + intervalMs
 * 4. Manejo inteligente de "waiting": espera únicamente remainingMs (ej. 137ms), no un ciclo entero
 * 5. Manejo inteligente de "paused": espera únicamente el tiempo de pausa por bingo (~2000ms)
 * 6. Telemetría temporal obligatoria: [BALL-SCHEDULER] y [BALL-DELAY] (>250ms)
 * 7. Resiliente a reinicios: reconstruye nextBallAt desde la base de datos/servidor
 * 8. Soporta cambios de configuración de intervalo en caliente de forma atómica
 */

const fs    = require('fs');
const path  = require('path');
const http  = require('http');
const https = require('https');
const { URL } = require('url');

// ─────────────────────────────────────────
// Carga de variables de entorno desde .env
// ─────────────────────────────────────────
function loadEnv() {
    const envPath = path.join(__dirname, '.env');
    if (!fs.existsSync(envPath)) return;
    fs.readFileSync(envPath, 'utf8').split('\n').forEach(line => {
        const t = line.trim();
        if (!t || t.startsWith('#')) return;
        const eqIdx = t.indexOf('=');
        if (eqIdx < 1) return;
        const key = t.slice(0, eqIdx).trim();
        const val = t.slice(eqIdx + 1).trim();
        if (key) process.env[key] = val;
    });
}
loadEnv();

// ─────────────────────────────────────────
// Configuración
// ─────────────────────────────────────────
const APP_URL          = process.env.APP_URL          || 'https://bingo.reybingo.com';
const CRON_TOKEN       = process.env.CRON_TOKEN       || 'reybingo_cron_secret_key_2026';
const SYNC_INTERVAL_MS = parseInt(process.env.SYNC_INTERVAL_MS || '3000', 10);
const MIN_TICK_MS      = parseInt(process.env.MIN_TICK_MS      || '1000', 10);
const STATUS_PORT      = parseInt(process.env.STATUS_PORT      || '9999', 10);

console.log('=======================================================');
console.log('BINGO RUNNER DAEMON - Precision Scheduler Edition');
console.log('App Target  : ' + APP_URL);
console.log('Sync cada   : ' + SYNC_INTERVAL_MS + ' ms');
console.log('Tick min    : ' + MIN_TICK_MS + ' ms');
console.log('Architecture: nextBallAt + Auto-corrective setTimeout');
console.log('=======================================================');

// Mapa de schedulers activos: gameId -> GameScheduler
const activeSchedulers = new Map();

// ─────────────────────────────────────────
// Cliente HTTP/HTTPS ligero (0 dependencias)
// ─────────────────────────────────────────
function requestApi(urlStr, options) {
    options = options || {};
    return new Promise(function(resolve, reject) {
        var parsedUrl;
        try { parsedUrl = new URL(urlStr); }
        catch(e) { return reject(new Error('URL invalida: ' + urlStr)); }

        var transport = parsedUrl.protocol === 'https:' ? https : http;
        var headers = {
            'User-Agent'   : 'BingoRunner/3.0-Precision',
            'X-Cron-Token' : CRON_TOKEN
        };
        if (process.env.APP_HOST_HEADER) {
            headers['Host'] = process.env.APP_HOST_HEADER;
        }
        Object.assign(headers, options.headers || {});

        var postData = '';
        if (options.body && typeof options.body === 'object') {
            postData = new URLSearchParams(options.body).toString();
            headers['Content-Type']   = 'application/x-www-form-urlencoded';
            headers['Content-Length'] = Buffer.byteLength(postData);
        }

        var reqOptions = {
            hostname : parsedUrl.hostname,
            port     : parsedUrl.port || (parsedUrl.protocol === 'https:' ? 443 : 80),
            path     : parsedUrl.pathname + parsedUrl.search,
            method   : options.method || 'GET',
            headers  : headers,
            timeout  : 12000
        };

        var req = transport.request(reqOptions, function(res) {
            var body = '';
            res.on('data', function(chunk) { body += chunk; });
            res.on('end', function() {
                try   { resolve({ statusCode: res.statusCode, data: JSON.parse(body) }); }
                catch { resolve({ statusCode: res.statusCode, raw: body }); }
            });
        });

        req.on('timeout', function() {
            req.destroy();
            reject(new Error('Request timeout: ' + urlStr));
        });
        req.on('error', reject);
        if (postData) req.write(postData);
        req.end();
    });
}

// ─────────────────────────────────────────
// Estructura y lógica del Scheduler por Juego
// ─────────────────────────────────────────
function formatTime(ts) {
    if (!ts) return 'N/A';
    return new Date(ts).toISOString().slice(11, 23);
}

function scheduleNextTick(scheduler) {
    if (scheduler.isStopped) return;

    if (scheduler.timeoutRef) {
        clearTimeout(scheduler.timeoutRef);
        scheduler.timeoutRef = null;
    }

    var now = Date.now();
    var delay = Math.max(0, scheduler.nextBallAt - now);

    scheduler.timeoutRef = setTimeout(function() {
        executeTick(scheduler);
    }, delay);
}

async function executeTick(scheduler) {
    if (scheduler.isStopped || scheduler.isTicking) return;

    scheduler.isTicking = true;
    var tickStartTime = Date.now();
    var driftMs = tickStartTime - scheduler.nextBallAt;
    var tag = '[' + formatTime(tickStartTime) + '] Juego #' + scheduler.gameId;

    try {
        var res = await requestApi(APP_URL + '/cron/tick-auto-game', {
            method : 'POST',
            body   : {
                game_id: scheduler.gameId,
                expected_at: scheduler.nextBallAt
            }
        });

        var networkEndTime = Date.now();
        var networkDuration = networkEndTime - tickStartTime;

        if (!res.data) {
            console.warn(tag + ' | Respuesta vacia de PHP (' + res.statusCode + ')');
            scheduler.isTicking = false;
            // Reintento en 500ms
            scheduler.nextBallAt = Date.now() + 500;
            scheduleNextTick(scheduler);
            return;
        }

        var d = res.data;

        // ── CASO 1: Balota cantada con éxito ─────────────────────────
        if (d.ok && d.number) {
            var ballNumber = parseInt(d.number, 10);
            var ballTimestamp = d.ballTimestamp ? parseInt(d.ballTimestamp, 10) : tickStartTime;
            var prevBallTime = scheduler.lastBallAt;

            var driftStr = (driftMs >= 0 ? '+' : '') + driftMs + 'ms';
            var prevStr = prevBallTime ? formatTime(prevBallTime) : 'Inicio';
            var expStr  = formatTime(scheduler.nextBallAt);
            var actStr  = formatTime(tickStartTime);

            var phpMsStr = d.phpProcessingMs !== undefined ? (' | PHP: ' + d.phpProcessingMs + 'ms') : '';
            console.log(
                '[BALL-SCHEDULER] Game: ' + scheduler.gameId +
                ' | Ball: ' + ballNumber +
                ' | Configured: ' + scheduler.intervalMs + 'ms' +
                ' | Previous: ' + prevStr +
                ' | Expected: ' + expStr +
                ' | Actual: ' + actStr +
                ' | Drift: ' + driftStr +
                ' | Net: ' + networkDuration + 'ms' +
                phpMsStr
            );

            if (Math.abs(driftMs) > 250) {
                console.warn(
                    '[BALL-DELAY] Game: ' + scheduler.gameId +
                    ' | Ball: ' + ballNumber +
                    ' | Configured: ' + scheduler.intervalMs + 'ms' +
                    ' | Expected: ' + expStr +
                    ' | Actual: ' + actStr +
                    ' | Drift: ' + driftStr +
                    ' | schedulerDelay: ' + driftMs + 'ms' +
                    ' | phpProcessingTime: ' + (d.phpProcessingMs !== undefined ? d.phpProcessingMs + 'ms' : 'N/A') +
                    ' | networkDuration: ' + networkDuration + 'ms'
                );
            }

            scheduler.lastBallAt = ballTimestamp;
            scheduler.lastBallNumber = ballNumber;
            scheduler.numbersDrawn++;

            if (d.completed) {
                console.log(tag + ' | Partida completada.');
                stopGame(scheduler.gameId);
                return;
            }

            // Cálculo del próximo objetivo SIN deriva acumulativa
            // nextBallAt = target_previo + intervalMs
            var nextTarget = scheduler.nextBallAt + scheduler.intervalMs;

            // Si por alguna razón severa hubo un atraso excesivo (> 1.5 intervalos),
            // resincronizar a partir de ahora para no emitir ráfagas de balotas
            if (nextTarget < Date.now() - 500) {
                nextTarget = Date.now() + scheduler.intervalMs;
            }

            scheduler.nextBallAt = nextTarget;
            scheduler.isTicking = false;
            scheduleNextTick(scheduler);
            return;
        }

        // ── CASO 2: Intervalo no cumplido (waiting) ───────────────────
        if (d.ok && d.waiting) {
            var waitRemaining = Math.max(25, parseInt(d.remainingMs, 10) || 100);
            scheduler.nextBallAt = Date.now() + waitRemaining;
            scheduler.isTicking = false;
            scheduleNextTick(scheduler);
            return;
        }

        // ── CASO 3: Pausa por cante reciente de bingo (paused) ────────
        if (d.ok && d.paused) {
            var pauseRemaining = Math.max(50, parseInt(d.pauseRemainingMs, 10) || parseInt(d.remainingMs, 10) || 2000);
            console.log(tag + ' | Pausa por bingo en curso. Esperando ' + pauseRemaining + 'ms');
            scheduler.nextBallAt = Date.now() + pauseRemaining;
            scheduler.isTicking = false;
            scheduleNextTick(scheduler);
            return;
        }

        // ── CASO 4: Partida finalizada o inactiva ─────────────────────
        if (d.completed || d.inactive) {
            console.log(tag + ' | Juego completado o inactivo (' + (d.message || '') + '). Deteniendo scheduler.');
            stopGame(scheduler.gameId);
            return;
        }

        // Otros estados transitorios (ej: error en DB): reintentar en 500ms
        console.warn(tag + ' | Estado inesperado: ' + (d.message || JSON.stringify(d)) + '. Reintentando en 500ms.');
        scheduler.nextBallAt = Date.now() + 500;
        scheduler.isTicking = false;
        scheduleNextTick(scheduler);

    } catch (err) {
        console.error(tag + ' | ERROR en tick: ' + err.message + '. Reintentando en 1000ms.');
        scheduler.nextBallAt = Date.now() + 1000;
        scheduler.isTicking = false;
        scheduleNextTick(scheduler);
    }
}

// ─────────────────────────────────────────
// Iniciar o actualizar un scheduler de juego
// ─────────────────────────────────────────
function startOrUpdateGame(game) {
    var gameId = parseInt(game.id, 10);
    var intervalMs = Math.max(MIN_TICK_MS, parseInt(game.intervalMs, 10) || 15000);
    var numbersDrawn = parseInt(game.numbersDrawn, 10) || 0;

    if (activeSchedulers.has(gameId)) {
        var existing = activeSchedulers.get(gameId);

        // Detectar cambio de configuración de intervalo en caliente
        if (existing.intervalMs !== intervalMs) {
            var oldInterval = existing.intervalMs;
            existing.intervalMs = intervalMs;
            existing.nextBallAt = (existing.lastBallAt || Date.now()) + intervalMs;
            console.log('[' + formatTime(Date.now()) + '] Juego #' + gameId + ': intervalo actualizado de ' + oldInterval + 'ms a ' + intervalMs + 'ms');
            scheduleNextTick(existing);
        }
        return;
    }

    // Inicialización de nuevo juego o recuperación tras reinicio
    var lastBallAt = null;
    if (game.lastBallTimestamp && parseInt(game.lastBallTimestamp, 10) > 0) {
        lastBallAt = parseInt(game.lastBallTimestamp, 10);
    }

    var nextBallAt = Date.now();
    if (numbersDrawn === 0) {
        // Primera balota de la partida: de inmediato (0ms)
        nextBallAt = Date.now();
    } else if (game.nextBallAt && parseInt(game.nextBallAt, 10) > 0) {
        nextBallAt = parseInt(game.nextBallAt, 10);
    } else if (lastBallAt) {
        nextBallAt = lastBallAt + intervalMs;
    }

    // Si ya está vencido en el pasado, ejecutar de inmediato
    if (nextBallAt < Date.now()) {
        nextBallAt = Date.now();
    }

    var scheduler = {
        gameId       : gameId,
        intervalMs   : intervalMs,
        nextBallAt   : nextBallAt,
        lastBallAt   : lastBallAt,
        lastBallNumber: null,
        numbersDrawn : numbersDrawn,
        timeoutRef   : null,
        isTicking    : false,
        isStopped    : false
    };

    activeSchedulers.set(gameId, scheduler);
    console.log(
        '[' + formatTime(Date.now()) + '] Juego #' + gameId +
        ': iniciado scheduler (intervalo: ' + intervalMs + 'ms, balotas previas: ' + numbersDrawn +
        ', proxima balota en: ' + Math.max(0, nextBallAt - Date.now()) + 'ms)'
    );

    scheduleNextTick(scheduler);
}

// ─────────────────────────────────────────
// Detener el scheduler de un juego
// ─────────────────────────────────────────
function stopGame(gameId) {
    if (activeSchedulers.has(gameId)) {
        var scheduler = activeSchedulers.get(gameId);
        scheduler.isStopped = true;
        if (scheduler.timeoutRef) {
            clearTimeout(scheduler.timeoutRef);
            scheduler.timeoutRef = null;
        }
        activeSchedulers.delete(gameId);
        console.log('[' + formatTime(Date.now()) + '] Juego #' + gameId + ': scheduler detenido');
    }
}

// ─────────────────────────────────────────
// Sincronización periódica con el servidor PHP
// ─────────────────────────────────────────
async function syncActiveGames() {
    try {
        var res = await requestApi(APP_URL + '/cron/active-auto-games');

        if (!res.data || !res.data.ok || !Array.isArray(res.data.activeGames)) {
            return;
        }

        var serverGames   = res.data.activeGames;
        var serverGameIds = new Set(serverGames.map(function(g) { return parseInt(g.id, 10); }));

        // Arrancar o actualizar cada juego activo
        serverGames.forEach(function(game) {
            startOrUpdateGame(game);
        });

        // Detener juegos que ya no figuren como activos en el servidor
        activeSchedulers.forEach(function(_, gameId) {
            if (!serverGameIds.has(gameId)) {
                stopGame(gameId);
            }
        });
    } catch (err) {
        console.error('[' + formatTime(Date.now()) + '] ERROR sync: ' + err.message);
    }
}

// ─────────────────────────────────────────
// Health-check interno (solo localhost)
// curl http://127.0.0.1:9999/health
// ─────────────────────────────────────────
http.createServer(function(req, res) {
    if (req.url === '/health' || req.url === '/status') {
        var schedulersInfo = [];
        activeSchedulers.forEach(function(s, gId) {
            schedulersInfo.push({
                gameId     : gId,
                intervalMs : s.intervalMs,
                nextBallAt : s.nextBallAt,
                remainingMs: Math.max(0, s.nextBallAt - Date.now()),
                lastBallAt : s.lastBallAt,
                numbersDrawn: s.numbersDrawn,
                isTicking  : s.isTicking
            });
        });

        var status = {
            ok          : true,
            uptime      : Math.round(process.uptime()),
            activeGames : Array.from(activeSchedulers.keys()),
            activeCount : activeSchedulers.size,
            schedulers  : schedulersInfo,
            timestamp   : new Date().toISOString()
        };
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify(status));
    } else {
        res.writeHead(404);
        res.end('Not found');
    }
}).listen(STATUS_PORT, '127.0.0.1', function() {
    console.log('Health-check en http://127.0.0.1:' + STATUS_PORT + '/health');
});

// ─────────────────────────────────────────
// Arranque principal
// ─────────────────────────────────────────
syncActiveGames();
setInterval(syncActiveGames, SYNC_INTERVAL_MS);

// ─────────────────────────────────────────
// Apagado limpio (PM2 / systemd / Docker SIGTERM)
// ─────────────────────────────────────────
function gracefulShutdown(signal) {
    console.log('\nSenal ' + signal + ' recibida. Deteniendo schedulers...');
    activeSchedulers.forEach(function(_, gameId) { stopGame(gameId); });
    process.exit(0);
}
process.on('SIGINT',  function() { gracefulShutdown('SIGINT'); });
process.on('SIGTERM', function() { gracefulShutdown('SIGTERM'); });
