<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Panel Admin – <?= esc($gameId) ?></title>
  <style>
    body { font-family: system-ui, sans-serif; margin: 20px; }
    .grid { display: grid; grid-template-columns: repeat(15, 40px); gap: 8px; }
    .num { width:40px; height:40px; display:flex; align-items:center; justify-content:center; border:1px solid #ccc; border-radius:6px; cursor:pointer; user-select:none; transition: all 0.3s ease; }
    .num.marked { background:#333; color:#fff; cursor:not-allowed; transform: scale(0.95); }
    .num:hover:not(.marked) { background:#f0f0f0; }
    .toolbar { margin: 16px 0; display:flex; gap:12px; align-items:center; }
    button { padding:8px 12px; border-radius:6px; border:1px solid #999; cursor:pointer; }
    .status { margin-left:auto; font-weight:600; }
    .log { font-family: ui-monospace, Menlo, Consolas, monospace; font-size:12px; white-space:pre-wrap; margin-top:12px; max-height: 200px; overflow-y: auto; background: #f5f5f5; padding: 8px; border-radius: 4px; }

    .play-controls { display: flex; gap: 12px; align-items: center; margin-bottom: 16px; }
    #btnPlay { background: #28a745; color: white; border: none; font-size: 16px; padding: 10px 20px; border-radius: 6px; }
    #btnPlay:disabled { background: #6c757d; cursor: not-allowed; }
    #btnStop { background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 6px; }
    .interval-control { display: flex; align-items: center; gap: 8px; }
    .interval-control input { width: 60px; padding: 4px; border: 1px solid #ccc; border-radius: 4px; }

    .alert-banner {
      position: fixed; right: 16px; top: 16px;
      background: #ffdd57; color: #222; padding: 10px 14px;
      border: 1px solid #e6c54f; border-radius: 8px;
      box-shadow: 0 4px 14px rgba(0,0,0,.15);
      font-weight: 600; z-index: 9999; display:none;
    }
    .flash { animation: flash-bg 0.8s ease-in-out 3; }
    @keyframes flash-bg {
      0% { background-color: #fff; }
      50% { background-color: #ffe9a8; }
      100% { background-color: #fff; }
    }

    .last-drawn {
      background: #28a745 !important;
      color: white !important;
      animation: highlight 2s ease-in-out;
    }

    @keyframes highlight {
      0% { transform: scale(1); }
      50% { transform: scale(1.2); background: #ffc107; }
      100% { transform: scale(0.95); }
    }
  </style>

  <script>
    const GAME_ID   = <?= json_encode($gameId) ?>;
    const API_DRAW  = <?= json_encode(site_url("api/games/{$gameId}/draw")) ?>;
    const API_RESET = <?= json_encode(site_url("api/games/{$gameId}/reset")) ?>;
    const API_STATE = <?= json_encode(site_url("api/games/{$gameId}/state")) ?>;
    const API_AUTO_DRAW = <?= json_encode(site_url("api/games/{$gameId}/auto-draw")) ?>;
    const AUTH_URL  = <?= json_encode(site_url('pusher/auth')) ?>;
    const PUSHER_KEY = <?= json_encode(env('PUSHER_KEY')) ?>;
    const PUSHER_CLUSTER = <?= json_encode(env('PUSHER_CLUSTER')) ?>;
  </script>
</head>
<body>
  <h1>Panel Admin – Juego <?= esc($gameId) ?></h1>
  
  <div class="play-controls">
    <button id="btnPlay" type="button">▶ Iniciar Sorteo</button>
    <button id="btnStop" type="button" disabled>⏹ Detener</button>
    <div class="interval-control">
      <label>Intervalo (seg):</label>
      <input type="number" id="intervalInput" value="3" min="1" max="10">
    </div>
  </div>

  <div class="toolbar">
    <button id="btnReset" type="button">🔄 Reiniciar</button>
    <span class="status" id="status">Listo</span>
  </div>

  <div class="grid" id="board"></div>
  <div id="alert" class="alert-banner"></div>
  <div class="log" id="log"></div>

  <script src="https://js.pusher.com/8.2/pusher.min.js"></script>
  <script>
    const board    = document.getElementById('board');
    const statusEl = document.getElementById('status');
    const logEl    = document.getElementById('log');
    const btnPlay  = document.getElementById('btnPlay');
    const btnStop  = document.getElementById('btnStop');
    const intervalInput = document.getElementById('intervalInput');
    
    let autoDrawInterval = null;
    let availableNumbers = [];
    let lastDrawnNumber = null;
    
    function log(x){ 
      console.log(x); 
      try{ 
        const timestamp = new Date().toLocaleTimeString();
        const message = typeof x === 'string' ? x : JSON.stringify(x);
        logEl.textContent += `[${timestamp}] ${message}\n`;
        logEl.scrollTop = logEl.scrollHeight;
      } catch{} 
    }

    // Render tablero 1..75
    const cells = [];
    for (let i=1;i<=75;i++){
      const d=document.createElement('div');
      d.className='num'; d.textContent=i; d.dataset.n=String(i);
      board.appendChild(d); cells[i]=d;
      availableNumbers.push(i);
    }
    
    function mark(n, isNew = false){ 
      if (cells[n]) {
        cells[n].classList.add('marked');
        
        if (isNew) {
          if (lastDrawnNumber && cells[lastDrawnNumber]) {
            cells[lastDrawnNumber].classList.remove('last-drawn');
          }
          
          cells[n].classList.add('last-drawn');
          lastDrawnNumber = n;
          
          setTimeout(() => {
            if (cells[n]) {
              cells[n].classList.remove('last-drawn');
            }
          }, 3000);
        }
        
        const index = availableNumbers.indexOf(n);
        if (index > -1) {
          availableNumbers.splice(index, 1);
        }
        
        log(`Número ${n} marcado`);
      }
    }
    
    function unmarkAll(){ 
      for(let i=1;i<=75;i++) {
        if (cells[i]) {
          cells[i].classList.remove('marked', 'last-drawn');
        }
      }
      availableNumbers = [];
      for(let i=1;i<=75;i++) availableNumbers.push(i);
      lastDrawnNumber = null;
      log('Tablero reiniciado');
    }

    async function post(url, data){
      try {
        const res = await fetch(url,{
          method:'POST', 
          headers:{'Content-Type':'application/json'}, 
          body: JSON.stringify(data||{})
        });
        const json = await res.json();
        log({POST:url, status:res.status, response: json});
        return json;
      } catch (error) {
        log({POST_ERROR: url, error: error.message});
        throw error;
      }
    }

    async function drawRandomNumber() {
      if (availableNumbers.length === 0) {
        stopAutoPlay();
        statusEl.textContent = 'Todos los números han sido sorteados';
        log('Sorteo completado - todos los números han sido cantados');
        return;
      }
      
      const randomIndex = Math.floor(Math.random() * availableNumbers.length);
      const n = availableNumbers[randomIndex];
      
      log(`Sorteando número automático: ${n}`);
      
      try {
        mark(n, true);
        const snap = await post(API_AUTO_DRAW, { n });
        
        if (snap?.stopped) {
          stopAutoPlay();
          statusEl.textContent = 'Detenido: hubo Bingo';
          log('Juego detenido por BINGO');
        }
      } catch (error) {
        log(`Error al sortear número ${n}: ${error.message}`);
        if (cells[n]) {
          cells[n].classList.remove('marked', 'last-drawn');
          availableNumbers.push(n);
        }
      }
    }

    function startAutoPlay() {
      const interval = parseInt(intervalInput.value) * 1000;
      autoDrawInterval = setInterval(drawRandomNumber, interval);
      btnPlay.disabled = true;
      btnStop.disabled = false;
      intervalInput.disabled = true;
      statusEl.textContent = 'Sorteando automáticamente...';
      log(`Sorteo automático iniciado (intervalo: ${intervalInput.value}s)`);
    }

    function stopAutoPlay() {
      if (autoDrawInterval) {
        clearInterval(autoDrawInterval);
        autoDrawInterval = null;
      }
      btnPlay.disabled = false;
      btnStop.disabled = true;
      intervalInput.disabled = false;
      if (statusEl.textContent === 'Sorteando automáticamente...') {
        statusEl.textContent = 'Listo';
      }
      log('Sorteo automático detenido');
    }

    btnPlay.addEventListener('click', startAutoPlay);
    btnStop.addEventListener('click', stopAutoPlay);

    board.addEventListener('click', async ev=>{
      const el = ev.target.closest('.num'); 
      if (!el) return;
      
      const n = parseInt(el.dataset.n,10);
      if (el.classList.contains('marked')) {
        log(`Número ${n} ya está marcado`);
        return;
      }
      
      if (autoDrawInterval) {
        stopAutoPlay();
      }
      
      log(`Sorteando número manual: ${n}`);
      
      try {
        mark(n, true);
        const snap = await post(API_DRAW, { n });
        
        if (snap?.stopped) {
          statusEl.textContent = 'Detenido: hubo Bingo';
          log('Juego detenido por BINGO');
        }
      } catch (error) {
        log(`Error al sortear número ${n}: ${error.message}`);
        el.classList.remove('marked', 'last-drawn');
        availableNumbers.push(n);
      }
    });

    document.getElementById('btnReset').addEventListener('click', async()=>{
      stopAutoPlay();
      log('Reiniciando juego...');
      
      try {
        await post(API_RESET, {});
        unmarkAll(); 
        statusEl.textContent = 'Reiniciado';
      } catch (error) {
        log(`Error al reiniciar: ${error.message}`);
      }
    });

    function beep() {
      try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const o = ctx.createOscillator(); const g = ctx.createGain();
        o.type = 'square'; o.frequency.value = 880;
        o.connect(g); g.connect(ctx.destination);
        g.gain.setValueAtTime(0.001, ctx.currentTime);
        g.gain.exponentialRampToValueAtTime(0.2, ctx.currentTime + 0.01);
        o.start();
        setTimeout(()=>{
          g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.2);
          o.stop(ctx.currentTime + 0.22);
        }, 160);
      } catch(e) {
        log('Error al reproducir sonido: ' + e.message);
      }
    }
    
    function showAlert(html, ms=3500) {
      const el = document.getElementById('alert');
      el.innerHTML = html; el.style.display = 'block';
      document.body.classList.add('flash'); beep();
      setTimeout(()=>{ el.style.display='none'; document.body.classList.remove('flash'); }, ms);
    }

    console.log('Configuración Pusher:', {AUTH_URL, PUSHER_KEY, PUSHER_CLUSTER});
    
    try {
      Pusher.logToConsole = true;
      const pusher = new Pusher(PUSHER_KEY, {
        cluster: PUSHER_CLUSTER,
        channelAuthorization: {
          endpoint: AUTH_URL,
          transport: 'ajax',
        },
      });
      
      const channelName = 'private-game-' + GAME_ID;
      const channel = pusher.subscribe(channelName);
      
      log(`Conectando a canal: ${channelName}`);

      channel.bind('pusher:subscription_succeeded', () => {
        log('✅ SUSCRITO correctamente a ' + channelName);
      });
      
      channel.bind('pusher:subscription_error', (error) => {
        log('❌ ERROR de suscripción: ' + JSON.stringify(error));
      });

      channel.bind('game:number_drawn', ({n}) => { 
        log(`📡 Evento Pusher: número ${n} sorteado`);
      });
      
      channel.bind('game:game_reset', () => { 
        log('📡 Evento Pusher: juego reiniciado');
        unmarkAll(); 
        statusEl.textContent='Reiniciado';
        stopAutoPlay();
      });

      channel.bind('game:bingo_claimed', (p) => {
        log('📡 Reclamo de BINGO recibido: ' + JSON.stringify(p));
        const msg = p.valid
          ? `¡BINGO VALIDADO! Jugador: ${p.playerId} · Cartón: ${p.cardId} (${p.matches}/${p.required})`
          : `Reclamo de Bingo: ${p.playerId} · Cartón: ${p.cardId} (${p.matches ?? '?'} / ${p.required ?? '?'})`;
        showAlert(msg);
        if (p.valid) {
          stopAutoPlay();
          statusEl.textContent = 'Detenido: hubo Bingo';
        }
      });

      channel.bind('game:bingo_accepted', (p) => {
        log('📡 BINGO ACEPTADO: ' + JSON.stringify(p));
        const modClean = String(p.modalityName || p.modality || 'Bingo').replace(/^la\s+/i, '').trim();
        const modLabel = /^bingo/i.test(modClean) || /^pleno/i.test(modClean)
            ? `Ganadores de la modalidad ${modClean}`
            : `Ganadores de la ${modClean}`;
        const pName = p.player || p.userName || p.playerName || ('Jugador #' + (p.playerId || p.userId || ''));
        const rawList = Array.isArray(p.officialWinners) && p.officialWinners.length > 0
            ? p.officialWinners
            : (Array.isArray(p.winners) && p.winners.length > 0 ? p.winners : null);
        let winnersText = pName;
        if (rawList && rawList.length > 0) {
            const mId = parseInt(p.modalityId, 10);
            const matching = rawList.filter(w => {
                const wmId = parseInt(w.modalityId || w.modality_id, 10);
                if (mId > 0 && wmId > 0) return mId === wmId;
                const wmName = String(w.modalityName || w.modality || '').replace(/^la\s+/i, '').trim().toLowerCase();
                return wmName && wmName === modClean.toLowerCase();
            }).map(w => w.userName || w.player || '').filter(Boolean);
            if (matching.length > 0) {
                winnersText = Array.from(new Set(matching)).join(' + ');
            }
        }
        if (typeof window.showNotification === 'function') {
            window.showNotification({
                id: 'sing_' + (p.singId || p.cardId || Date.now()),
                type: 'sing',
                modalityId: p.modalityId,
                modality: modClean,
                player: pName,
                cartonId: p.cardId || p.cartonId,
                title: '🎉 ¡BINGO CANTADO!',
                message: `${modLabel}: ${winnersText}`,
                officialWinners: rawList || undefined
            });
        } else {
            showAlert(`${modLabel}: ${winnersText}`, 5000);
        }
        stopAutoPlay();
        statusEl.textContent = 'Detenido: hubo Bingo';
      });
      
    } catch (error) {
      log('❌ Error al inicializar Pusher: ' + error.message);
    }

    (async ()=>{
      try {
        log('🔄 Sincronizando estado inicial...');
        const r = await fetch(API_STATE); 
        const snap = await r.json();
        
        if (snap.drawn && snap.drawn.length > 0) {
          log(`Marcando ${snap.drawn.length} números ya sorteados`);
          snap.drawn.forEach(n => mark(n, false));
        }
        
        statusEl.textContent = snap.stopped ? 'Detenido: hubo Bingo' : 'Listo';
        log('✅ Sincronización completada');
        
      } catch (error) {
        log('❌ Error en sincronización inicial: ' + error.message);
      }
    })();
  </script>
</body>
</html>
