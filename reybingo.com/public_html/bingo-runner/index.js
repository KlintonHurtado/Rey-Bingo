/**
 * Bingo Runner Daemon - WebSocket Edition (Node.js)
 *
 * Microservicio en segundo plano que:
 * 1. Sincroniza qué juegos automáticos están activos consultando PHP
 * 2. Hace el tick de cada balota llamando al endpoint PHP
 *    (PHP valida unicidad, guarda en MySQL y emite el evento a Soketi)
 * 3. Mantiene timers precisos por juego sin ningún polling en el frontend
 *
 * Flujo: Runner tick → PHP → MySQL + Soketi → Push a todos los jugadores (<50ms)
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
const SYNC_INTERVAL_MS = parseInt(process.env.SYNC_INTERVAL_MS || '8000', 10);
const MIN_TICK_MS      = parseInt(process.env.MIN_TICK_MS      || '3000', 10);
const STATUS_PORT      = parseInt(process.env.STATUS_PORT      || '9999', 10);

console.log('=======================================================');
console.log('BINGO RUNNER DAEMON - WebSocket Edition');
console.log('App Target : ' + APP_URL);
console.log('Sync cada  : ' + SYNC_INTERVAL_MS + ' ms');
console.log('Tick min   : ' + MIN_TICK_MS + ' ms');
console.log('=======================================================');

// Mapa de timers activos: gameId -> { timerId, intervalMs }
const activeTimers = new Map();

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
            'User-Agent'   : 'BingoRunner/2.0-WS',
            'X-Cron-Token' : CRON_TOKEN
        };
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
// Tick de 1 balota para un juego
// PHP valida, guarda en MySQL y emite a Soketi
// ─────────────────────────────────────────
async function tickGame(gameId) {
    var tag = '[' + new Date().toLocaleTimeString() + '] Juego #' + gameId;
    try {
        var res = await requestApi(APP_URL + '/cron/tick-auto-game', {
            method : 'POST',
            body   : { game_id: gameId }
        });

        if (!res.data) {
            console.warn(tag + ' | Respuesta vacia');
            return;
        }

        var d = res.data;

        if (d.ok) {
            if (d.number) {
                console.log(tag + ' | Balota: ' + d.number);
            } else if (d.completed) {
                console.log(tag + ' | Partida finalizada');
                stopGameTimer(gameId);
            }
            // d.paused = pausa por bingo reciente, es normal, no logear
        } else {
            // ok:false con HTTP 200 = juego ya no activo
            if (res.statusCode === 200) {
                console.log(tag + ' | Juego inactivo (' + (d.message || 'sin detalle') + '). Deteniendo.');
                stopGameTimer(gameId);
            } else {
                console.warn(tag + ' | HTTP ' + res.statusCode + ': ' + (d.message || JSON.stringify(d)));
            }
        }
    } catch (err) {
        console.error(tag + ' | ERROR: ' + err.message);
    }
}

// ─────────────────────────────────────────
// Inicia o actualiza el timer de un juego
// ─────────────────────────────────────────
function startGameTimer(gameId, intervalMs) {
    var safeInterval = Math.max(MIN_TICK_MS, parseInt(intervalMs, 10) || 15000);

    if (activeTimers.has(gameId)) {
        var existing = activeTimers.get(gameId);
        if (existing.intervalMs === safeInterval) return;
        clearInterval(existing.timerId);
        console.log('[' + new Date().toLocaleTimeString() + '] Juego #' + gameId + ': intervalo actualizado a ' + safeInterval + 'ms');
    } else {
        console.log('[' + new Date().toLocaleTimeString() + '] Juego #' + gameId + ': iniciando (cada ' + safeInterval + 'ms)');
    }

    tickGame(gameId); // Primer tick inmediato
    var timerId = setInterval(function() { tickGame(gameId); }, safeInterval);
    activeTimers.set(gameId, { timerId: timerId, intervalMs: safeInterval });
}

// ─────────────────────────────────────────
// Detiene el timer de un juego
// ─────────────────────────────────────────
function stopGameTimer(gameId) {
    if (activeTimers.has(gameId)) {
        clearInterval(activeTimers.get(gameId).timerId);
        activeTimers.delete(gameId);
        console.log('[' + new Date().toLocaleTimeString() + '] Juego #' + gameId + ': timer detenido');
    }
}

// ─────────────────────────────────────────
// Sincroniza juegos activos desde PHP
// ─────────────────────────────────────────
async function syncActiveGames() {
    try {
        var res = await requestApi(APP_URL + '/cron/active-auto-games');

        if (!res.data || !res.data.ok || !Array.isArray(res.data.activeGames)) {
            return; // Sin juegos activos o error transitorio
        }

        var serverGames   = res.data.activeGames;
        var serverGameIds = new Set(serverGames.map(function(g) { return g.id; }));

        // Arrancar / actualizar juegos activos
        serverGames.forEach(function(game) {
            startGameTimer(game.id, game.intervalMs);
        });

        // Detener juegos que ya no están activos
        activeTimers.forEach(function(_, gameId) {
            if (!serverGameIds.has(gameId)) {
                stopGameTimer(gameId);
            }
        });
    } catch (err) {
        console.error('[' + new Date().toLocaleTimeString() + '] ERROR sync: ' + err.message);
    }
}

// ─────────────────────────────────────────
// Health-check interno (solo localhost)
// curl http://127.0.0.1:9999/health
// ─────────────────────────────────────────
http.createServer(function(req, res) {
    if (req.url === '/health' || req.url === '/status') {
        var status = {
            ok          : true,
            uptime      : Math.round(process.uptime()),
            activeGames : Array.from(activeTimers.keys()),
            activeCount : activeTimers.size,
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
// Apagado limpio (PM2 / systemd SIGTERM)
// ─────────────────────────────────────────
function gracefulShutdown(signal) {
    console.log('\nSenal ' + signal + ' recibida. Deteniendo runner...');
    activeTimers.forEach(function(_, gameId) { stopGameTimer(gameId); });
    process.exit(0);
}
process.on('SIGINT',  function() { gracefulShutdown('SIGINT'); });
process.on('SIGTERM', function() { gracefulShutdown('SIGTERM'); });
