/**
 * Bingo Runner Daemon (Node.js)
 * 
 * Microservicio en segundo plano para coordinar el ritmo de extracción de balotas
 * en partidas automáticas sin bloquear workers de PHP (0% CPU/RAM bloqueado en PHP).
 */

const fs = require('fs');
const path = require('path');
const http = require('http');
const https = require('https');
const { URL } = require('url');

// Cargar variables de entorno desde .env local si existe
function loadEnv() {
    const envPath = path.join(__dirname, '.env');
    if (fs.existsSync(envPath)) {
        const content = fs.readFileSync(envPath, 'utf8');
        content.split('\n').forEach(line => {
            const trimmed = line.trim();
            if (trimmed && !trimmed.startsWith('#')) {
                const [key, ...vals] = trimmed.split('=');
                if (key && vals.length > 0) {
                    process.env[key.trim()] = vals.join('=').trim();
                }
            }
        });
    }
}

loadEnv();

const APP_URL = process.env.APP_URL || 'https://bingo.reybingo.com';
const CRON_TOKEN = process.env.CRON_TOKEN || 'reybingo_cron_secret_key_2026';
const POLL_INTERVAL_MS = parseInt(process.env.POLL_INTERVAL_MS || '5000', 10);

console.log('====================================================');
console.log('🚀 BINGO RUNNER DAEMON (Node.js) Iniciado');
console.log(`📌 App Target: ${APP_URL}`);
console.log(`📌 Poll Interval: ${POLL_INTERVAL_MS} ms`);
console.log('====================================================');

// Mapa de temporizadores activos por gameId: gameId -> { intervalId, intervalMs }
const activeTimers = new Map();

/**
 * Cliente HTTP/HTTPS nativo simple y ligero
 */
function requestApi(urlStr, options = {}) {
    return new Promise((resolve, reject) => {
        const parsedUrl = new URL(urlStr);
        const transport = parsedUrl.protocol === 'https:' ? https : http;

        const headers = {
            'User-Agent': 'BingoRunner/1.0',
            'X-Cron-Token': CRON_TOKEN,
            ...(options.headers || {})
        };

        const reqOptions = {
            hostname: parsedUrl.hostname,
            port: parsedUrl.port || (parsedUrl.protocol === 'https:' ? 443 : 80),
            path: parsedUrl.pathname + parsedUrl.search,
            method: options.method || 'GET',
            headers
        };

        let postData = '';
        if (options.body) {
            if (typeof options.body === 'object') {
                postData = new URLSearchParams(options.body).toString();
                headers['Content-Type'] = 'application/x-www-form-urlencoded';
                headers['Content-Length'] = Buffer.byteLength(postData);
            } else {
                postData = String(options.body);
            }
        }

        const req = transport.request(reqOptions, (res) => {
            let body = '';
            res.on('data', chunk => { body += chunk; });
            res.on('end', () => {
                try {
                    const json = JSON.parse(body);
                    resolve({ statusCode: res.statusCode, data: json });
                } catch (e) {
                    resolve({ statusCode: res.statusCode, raw: body });
                }
            });
        });

        req.on('error', (err) => {
            reject(err);
        });

        if (postData) {
            req.write(postData);
        }
        req.end();
    });
}

/**
 * Ejecuta 1 tick de bola sub-segundo en PHP para un juego específico
 */
async function tickGame(gameId) {
    try {
        const endpoint = `${APP_URL}/cron/tick-auto-game`;
        const res = await requestApi(endpoint, {
            method: 'POST',
            body: { game_id: gameId }
        });

        if (res.data && res.data.ok) {
            if (res.data.number) {
                console.log(`[${new Date().toLocaleTimeString()}] 🎲 Juego #${gameId}: Bola cantada ${res.data.number}`);
            } else if (res.data.paused) {
                console.log(`[${new Date().toLocaleTimeString()}] ⏸️ Juego #${gameId}: Pausado (sing reciente)`);
            } else if (res.data.completed) {
                console.log(`[${new Date().toLocaleTimeString()}] 🏁 Juego #${gameId}: Finalizado`);
                stopGameTimer(gameId);
            }
        } else {
            console.warn(`[${new Date().toLocaleTimeString()}] ⚠️ Juego #${gameId} tick warning:`, res.data || res.raw);
        }
    } catch (err) {
        console.error(`[${new Date().toLocaleTimeString()}] ❌ Error en tick de juego #${gameId}:`, err.message);
    }
}

/**
 * Inicia o actualiza el temporizador de un juego
 */
function startGameTimer(gameId, intervalMs) {
    const safeInterval = Math.max(2500, parseInt(intervalMs, 10) || 15000);

    if (activeTimers.has(gameId)) {
        const existing = activeTimers.get(gameId);
        if (existing.intervalMs === safeInterval) {
            return; // Ya configurado con el mismo intervalo
        }
        clearInterval(existing.intervalId);
    }

    console.log(`[${new Date().toLocaleTimeString()}] ▶️ Iniciando reloj para Juego #${gameId} (cada ${safeInterval} ms)`);
    
    // Primer tick inmediato
    tickGame(gameId);

    const intervalId = setInterval(() => {
        tickGame(gameId);
    }, safeInterval);

    activeTimers.set(gameId, { intervalId, intervalMs: safeInterval });
}

/**
 * Detiene el temporizador de un juego
 */
function stopGameTimer(gameId) {
    if (activeTimers.has(gameId)) {
        const existing = activeTimers.get(gameId);
        clearInterval(existing.intervalId);
        activeTimers.delete(gameId);
        console.log(`[${new Date().toLocaleTimeString()}] ⏹️ Reloj detenido para Juego #${gameId}`);
    }
}

/**
 * Sincroniza juegos automáticos activos desde la API de CodeIgniter
 */
async function syncActiveGames() {
    try {
        const endpoint = `${APP_URL}/cron/active-auto-games`;
        const res = await requestApi(endpoint);

        if (res.data && res.data.ok && Array.isArray(res.data.activeGames)) {
            const serverGames = res.data.activeGames;
            const serverGameIds = new Set(serverGames.map(g => g.id));

            // Arrancar o actualizar juegos que están activos en el servidor
            for (const game of serverGames) {
                startGameTimer(game.id, game.intervalMs);
            }

            // Detener juegos en memoria que ya no están activos en el servidor
            for (const [gameId] of activeTimers.entries()) {
                if (!serverGameIds.has(gameId)) {
                    stopGameTimer(gameId);
                }
            }
        }
    } catch (err) {
        console.error(`[${new Date().toLocaleTimeString()}] ❌ Error al sincronizar juegos activos:`, err.message);
    }
}

// Iniciar bucle principal de sincronización
syncActiveGames();
setInterval(syncActiveGames, POLL_INTERVAL_MS);

// Capturar señales de salida limpia
process.on('SIGINT', () => {
    console.log('\n🛑 Deteniendo Runner Daemon...');
    for (const [gameId] of activeTimers.entries()) {
        stopGameTimer(gameId);
    }
    process.exit(0);
});
