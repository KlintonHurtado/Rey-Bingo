<style>
    #last-number-center {
        position: absolute;
        z-index: 9999;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(1);
        transform-origin: center center;
        display: none;
        justify-content: center;
        align-items: center;
        text-align: center;
        opacity: 1;
        font-size: 5rem;
    }
 
    #block-number {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.1);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1050;
        overflow: hidden;
        transition: all 1s ease-in-out;
    }

    .animate-to-last {
        transition: all 1s ease-in-out;
    }

    .to-last-position {
        position: absolute;
        transform: none !important;
        top: auto !important;
        left: auto !important;
    }
    
    .last-drawn {
        transform: scale(1.2);
        animation: highlight 2s ease-in-out;
    }
    
    @keyframes highlight {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); background: #ffc107; }
        100% { transform: scale(0.95); }
    }
    
    .log {
        font-family: ui-monospace, Menlo, Consolas, monospace;
        font-size: 12px;
        white-space: pre-wrap;
        margin: 12px auto;
        max-height: 200px;
        overflow-y: auto;
        background: #000000;
        padding: 8px;
        border-radius: 4px;
        color: #fff;
        width: 90%;
        max-width: 800px;
    }
    
    .interval-control {
        display: flex;
        align-items: center;
        margin: 0 10px;
    }
    
    .interval-control input {
        width: 60px;
        padding: 4px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    
    .status-display {
        background: rgba(0,0,0,0.2);
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        margin-bottom: 10px;
        text-align: center;
        font-weight: bold;
    }
    
    .alert-banner {
        position: fixed; 
        right: 16px; 
        top: 16px;
        background: #ffdd57; 
        color: #222; 
        padding: 10px 14px;
        border: 1px solid #e6c54f; 
        border-radius: 8px;
        box-shadow: 0 4px 14px rgba(0,0,0,.15);
        font-weight: 600; 
        z-index: 9999; 
        display: none;
    }
    
    .flash { 
        animation: flash-bg 0.8s ease-in-out 3; 
    }
    
    @keyframes flash-bg {
        0% { background-color: #fff; }
        50% { background-color: #ffe9a8; }
        100% { background-color: #fff; }
    }
</style>
<div class="container-section">
    <div class="top-section">
        <a class="btn btn-small btn-home" href="<?= site_url('play'); ?>"><i class="fa-duotone fa-solid fa-house"></i></a>

        <button type="button" class="btn btn-small btn-wallet" onclick="paymentsGet();">
            <i class="fa-duotone fa-solid fa-wallet"></i>
        </button>

        <h6 class="total-balls m-0"><small><?= translate('total balls'); ?></small> <br /><span id="balls-counter"><?= $totalNumbersGenerated ?> - <?= 75 - $totalNumbersGenerated ?></span></h6>

        <div class="header-switch">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="activateAlgorithm" <?php if (systemGet('activateAlgorithm') == 1): ?>checked<?php endif; ?>>
            </div>
        </div>
        
        <div class="time-game">
            <h6 class="init-count m-0">
                <small><i class="fa-duotone fa-solid fa-timer"></i> <?= translate('TIME'); ?></small><br />
                <span class="time-text">00:00:00</span>
            </h6>
        </div>

        <button class="btn btn-small btn-volume hidden" onclick="RemoveVolume();">
            <?php if ($user['sounds'] == 1): ?>
                <i class="fa-duotone fa-solid fa-volume"></i>
            <?php else : ?>
                <i class="fa-duotone fa-solid fa-volume-slash"></i>
            <?php endif; ?>
        </button>

        <button class="btn btn-small btn-microphone hidden" onclick="RemoveMicrophone();">
            <?php if ($user['narration'] == 1): ?>
                <i class="fa-duotone fa-solid fa-microphone"></i>
            <?php else : ?>
                <i class="fa-duotone fa-solid fa-microphone-slash"></i>
            <?php endif; ?>
        </button>

        <button class="btn btn-small btn-sliders" onclick="ViewSliders();"><i class="fa-duotone fa-solid fa-sliders-simple"></i></button>

        <h6 class="total-accumulated m-0"><?php
            $hudPrize = bingo_live_prize_hud($game, bingo_count_game_cartons((int) ($game['id'] ?? 0)));
        ?><small id="accumulated-label"><?= esc($hudPrize['label']); ?></small> <br /><span id="accumulated-counter" data-counter="<?= esc($hudPrize['amount']); ?>"><?= systemGet('currency'); ?> <?= esc($hudPrize['amount']); ?></span></h6>
        
        <?php $class = $lastNumber ? $getClass($lastNumber) : 'STOP'; ?>
        <?php $letter = $lastNumber ? $getClass($lastNumber) : ''; ?>
        <div class="bingo-ball <?= $class ?> size-100" id="last-number">
            <small style="position: absolute; top: -13px; font-size: 1.2rem; z-index: 1;"><?= $letter ?></small>
            <span id="current-number"><?= $lastNumber ? $lastNumber : 'STOP'; ?></span></div>
        <div class="last-numbers">
            <button class="btn btn-small btn-cells size-40" onclick="modalitiesGet();"><i class="fa-duotone fa-solid fa-table-cells"></i></button>
            <span id="last-five-numbers">
                <?php foreach ($fourNumbers as $number): ?>
                    <?php $class = in_array($number, $fourNumbers) ? $getClass($number) : ''; ?>
                    <div class="bingo-ball <?= $class ?> size-40">
                        <span><?= $number ?></span>
                    </div>
                <?php endforeach; ?>
            </span>
            <button class="btn btn-small btn-award size-40" onclick="playersGet();"><i class="fa-duotone fa-solid fa-users"></i><count class="count_notifications"></count></button>
        </div>
        <h6 class="text-white text-center mb-0"><?= $game['description']; ?></h6>
        <h6 class="text-white text-center next-game mb-1 text-uppercase" style="font-size: 0.7rem;"></h6>
    </div>
    <div class="center-section">
        <div class="interval-control">
            <small><?= translate('Interval'); ?> (s)</small>
            <input type="number" id="intervalInput" value="3" min="1" max="10" class="form-control form-control-sm">
        </div>
        <button id="btnReset" type="button">🔄 Reiniciar</button>
        <h6 class="m-0"><small><?= translate('Status'); ?></small> <br /><span id="status-text">Listo</span></h6>
        <div class="board-section">
            <div class="content-board">
                <div class="board-number">
                    <div id="block-number"><div class="bingo-ball-200 STOP size-200" id="last-number-center"><span><?= $lastNumber ? $lastNumber : 'STOP'; ?></span></div></div>
                    <div class="column">
                        <div class="bingo-ball B size-50"><span>B</span></div>
                        <?php foreach (range(1, 15) as $number): ?>
                            <?php
                                $isSelected = in_array($number, $selectedNumbers);
                                $class = $isSelected ? $getClass($number) : '';
                                $addOnClick = !$isSelected;
                            ?>
                            <div class="bingo-ball size-50 <?= $class ?>" <?= $addOnClick ? 'onclick="generateNumber(' . $number . ');"' : '' ?> id="number-<?= $number ?>">
                                <span><?= $number ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="column">
                        <div class="bingo-ball I size-50"><span>I</span></div>
                        <?php foreach (range(16, 30) as $number): ?>
                            <?php
                                $isSelected = in_array($number, $selectedNumbers);
                                $class = $isSelected ? $getClass($number) : '';
                                $addOnClick = !$isSelected;
                            ?>
                            <div class="bingo-ball size-50 <?= $class ?>" <?= $addOnClick ? 'onclick="generateNumber(' . $number . ');"' : '' ?> id="number-<?= $number ?>">
                                <span><?= $number ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="column">
                        <div class="bingo-ball N size-50"><span>N</span></div>
                        <?php foreach (range(31, 45) as $number): ?>
                            <?php
                                $isSelected = in_array($number, $selectedNumbers);
                                $class = $isSelected ? $getClass($number) : '';
                                $addOnClick = !$isSelected;
                            ?>
                            <div class="bingo-ball size-50 <?= $class ?>" <?= $addOnClick ? 'onclick="generateNumber(' . $number . ');"' : '' ?> id="number-<?= $number ?>">
                                <span><?= $number ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="column">
                        <div class="bingo-ball G size-50"><span>G</span></div>
                        <?php foreach (range(46, 60) as $number): ?>
                            <?php
                                $isSelected = in_array($number, $selectedNumbers);
                                $class = $isSelected ? $getClass($number) : '';
                                $addOnClick = !$isSelected;
                            ?>
                            <div class="bingo-ball size-50 <?= $class ?>" <?= $addOnClick ? 'onclick="generateNumber(' . $number . ');"' : '' ?> id="number-<?= $number ?>">
                                <span><?= $number ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="column">
                        <div class="bingo-ball O size-50"><span>O</span></div>
                        <?php foreach (range(61, 75) as $number): ?>
                            <?php
                                $isSelected = in_array($number, $selectedNumbers);
                                $class = $isSelected ? $getClass($number) : '';
                                $addOnClick = !$isSelected;
                            ?>
                            <div class="bingo-ball size-50 <?= $class ?>" <?= $addOnClick ? 'onclick="generateNumber(' . $number . ');"' : '' ?> id="number-<?= $number ?>">
                                <span><?= $number ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="alert" class="alert-banner"></div>

<button class="btn btn-small btn-chat" id="toggle-messages-btn"><i class="fa-duotone fa-solid fa-comments-question"></i></button>

<div class="bottom-section">
    <?php if ($status != 'stop') : ?>
    <div class="controls-board" id="controls">
        <button id="start-button" class="btn btn-small btn-primary size-50"><i class="fa-duotone fa-solid fa-play"></i></button>
        <button id="play-button" class="btn btn-small btn-primary size-50" style="display: none;"><i class="fa-duotone fa-solid fa-play"></i></button>
        <button id="stop-button" class="btn btn-small btn-primary size-50" style="display: none;"><i class="fa-duotone fa-solid fa-pause"></i></button>
        <button id="next-number-button" class="btn btn-small btn-primary size-50" style="display: none;"><i class="fa-duotone fa-solid fa-forward-step"></i></button>
    </div>
    <?php endif; ?>
</div>

<div class="log hidden" id="log"></div>

<div class="message-display-container" id="message-display-container">
    <div class="message-display" id="message-display"></div>
    <div class="emoji-message-panel">
        <div class="emoji-slider">
            <button type="button" class="emoji-btn" onclick="sendEmoji('😊', 1)">😊</button>
            <button type="button" class="emoji-btn" onclick="sendEmoji('😢', 2)">😢</button>
            <button type="button" class="emoji-btn" onclick="sendEmoji('🤯', 3)">🤯</button>
            <button type="button" class="emoji-btn" onclick="sendEmoji('😂', 4)">😂</button>
            <button type="button" class="emoji-btn" onclick="sendEmoji('😍', 5)">😍</button>
            <button type="button" class="emoji-btn" onclick="sendEmoji('🥺', 6)">🥺</button>
            <button type="button" class="emoji-btn" onclick="sendEmoji('🤔', 7)">🤔</button>
            <button type="button" class="emoji-btn" onclick="sendEmoji('🙄', 8)">🙄</button>
            <button type="button" class="emoji-btn" onclick="sendEmoji('🧐', 9)">🧐</button>
            <button type="button" class="emoji-btn" onclick="sendEmoji('😘', 10)">😘</button>
            <button type="button" class="emoji-btn" onclick="sendEmoji('😜', 11)">😜</button>
            <button type="button" class="emoji-btn" onclick="sendEmoji('😅', 12)">😅</button>
            <button type="button" class="emoji-btn" onclick="sendEmoji('😨', 13)">😨</button>
            <button type="button" class="emoji-btn" onclick="sendEmoji('😎', 14)">😎</button>
            <button type="button" class="emoji-btn" onclick="sendEmoji('🤪', 15)">🤪</button>
            <button type="button" class="emoji-btn" onclick="sendEmoji('😲', 16)">😲</button>
            <button type="button" class="emoji-btn" onclick="sendEmoji('😒', 17)">😒</button>
            <button type="button" class="emoji-btn" onclick="sendEmoji('😛', 18)">😛</button>
            <button type="button" class="emoji-btn" onclick="sendEmoji('😓', 19)">😓</button>
        </div>

        <div class="message-bubble-slider">
            <button type="button" class="message-btn" onclick="sendMessage('<?= translate('hello!'); ?> 🥰', 20)"><?= translate('hello!'); ?> 🥰</button>
            <button type="button" class="message-btn" onclick="sendMessage('<?= translate('ha ha ha'); ?> 🤣', 22)"><?= translate('ha ha ha'); ?> 🤣</button>
            <button type="button" class="message-btn" onclick="sendMessage('<?= translate('lets have fun!'); ?> 😉', 23)"><?= translate('lets have fun!'); ?> 😉</button>
            <button type="button" class="message-btn" onclick="sendMessage('<?= translate('good luck!'); ?> 🤑', 24)"><?= translate('good luck!'); ?> 🤑</button>
        </div>

        <div class="input-group">
            <input type="text" class="form-control form-control-chat message-input" name="message-send-new" id="message-send-new" placeholder="<?= translate('write a message'); ?>..." autofocus autocomplete="off" maxlength="50">
            <button type="button" id="message-button" class="btn btn-small btn-primary btn-send" onclick="sendMessageText()"><i class="fa-duotone fa-solid fa-paper-plane-top"></i></button>
        </div>
    </div>
</div>

<div class="modal fade" id="modalModalities" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered max-w-30">
        <div class="modal-content">
            <div class="modal-header text-center">
                <h6 class="modal-title ps-2"><i class="fa-duotone fa-solid fa-chess-board"></i> <?= translate('modalities'); ?></h6>
                <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body pt-0 text-center">
                <?php if (!empty($modalities)): ?>
                    <?php
                        $count = count($modalities);
                        $columns = $count >= 3 ? 3 : $count;
                        $gridStyle = "grid-template-columns: repeat($columns, 1fr); width: 110px;";
                    ?>
                    <div class="container-cartons-modalities" style="<?= $gridStyle; ?>">
                        <?php foreach ($modalities as $modality): ?>
                            <?php
                                $isSing = in_array($modality['id'], $singsModalities);
                                $positions = explode(',', $modality['positions']);
                            ?>
                            <div class="border-carton">
                                <small style="font-size: .7rem"><?= translate($modality['name']); ?></small>
                                <div class="carton <?= $isSing ? 'cartn-sing' : '' ?>" id="modality-<?= $modality['id']; ?>">
                                    <div class="card-letter B"><span>B</span></div>
                                    <div class="card-letter I"><span>I</span></div>
                                    <div class="card-letter N"><span>N</span></div>
                                    <div class="card-letter G"><span>G</span></div>
                                    <div class="card-letter O"><span>O</span></div>
                                    <?php for ($i = 1; $i <= 25; $i++): ?>
                                        <?php
                                            $isMarked = in_array($i, $positions);
                                            $showStar = $isSing && $isMarked || $i == 13;
                                        ?>
                                        <?php if ($i == 13): ?>
                                            <div class="card-number" data-position="13" >⭐️</div>
                                        <?php else: ?>
                                            <div class="card-number <?= $isMarked ? 'modality-sing' : '' ?> <?= $isSing && $isMarked ? 'sing' : '' ?>" data-position="<?= $i; ?>"><?= $showStar ? '⭐️' : '' ?></div>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <?php if ($game['award'] == 2) : ?>
                                    <small style="font-size: .8rem" id="modality-amount-<?= $modality['id']; ?>"><?= systemGet('currency'); ?> <?= number_format($modality['amount'], 2) ?></small>
                                <?php else: ?>
                                    <small style="font-size: .8rem" id="modality-amount-<?= $modality['id']; ?>"></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p><?= translate('there are no modalities available'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div id="countdown-container" style="position: fixed; display: none;">
    <div class="countdown-container"> 
        <div id="countdown">10</div>
        <div id="text-countdown"></div>
    </div>
</div>

<div id="game-finalized" style="position: fixed; display: none;">
    <div class="game-finalized"> 
        <div id="finalized"></div>
    </div>
</div>

<div class="modal fade" id="modalExit" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title ps-2">
          <i class="fa-duotone fa-solid fa-triangle-exclamation"></i> <?= translate('Warning!'); ?>
        </h6>
        <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal">
          <i class="fa-duotone fa-solid fa-xmark"></i>
        </button>
      </div>
      <div class="modal-body pt-0 text-center">
        <?= translate('if you exit the game you could lose your game data. We recommend you stay in the game.'); ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary d-block w-50 btn-bingo mt-3 pe-2" id="cancelExit">
          <?= translate('cancel'); ?>
        </button>
        <a href="javascript:void(0);" class="btn btn-primary d-block w-50 btn-bingo mt-3" id="confirmExit">
          <?= translate('accept'); ?>
        </a>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
    window.singBall = "<?= systemGet('singBall'); ?>";
    window.timeBallGet = singBall.split('-')[0];
    window.timeBallLast = singBall.split('-')[1];
    window.totalNumbersGenerated = "<?= $totalNumbersGenerated; ?>";
    window.fiveNumbers = <?= $lastNumbersJson ?? '[]' ?>;
    window.winners = <?= json_encode($winners) ?>;
    window.gameDate = '<?= $game["date"] ?> <?= $game["time"] ?>';

    document.getElementById('activateAlgorithm').addEventListener('change', function() {
        let value = this.checked ? 1 : 0;

        fetch('<?= site_url('home/activateAlgorithm') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest', 
                'X-CSRF-TOKEN': '<?= csrf_hash() ?>'
            },
            body: JSON.stringify({ activateAlgorithm: value })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                console.log('Estado actualizado:', value);
            }
        })
        .catch(err => console.error('Error:', err));
    });

    document.addEventListener('DOMContentLoaded', function () {
        let exitUrl = null;
        let allowUnload = false;
        let reloadAttempted = false;

        // Detectar clic en el botón de recarga del navegador
        // Esto es una técnica que intenta detectar cuando el usuario hace clic en el botón de recarga
        let lastActivity = Date.now();
        document.addEventListener('mousemove', () => { lastActivity = Date.now(); });
        document.addEventListener('keydown', () => { lastActivity = Date.now(); });
        document.addEventListener('click', () => { lastActivity = Date.now(); });
        
        // Interceptar el evento beforeunload
        window.addEventListener('beforeunload', function(e) {
            // Si el usuario ya confirmó la salida, permitir
            if (allowUnload) return;
            
            // Si detectamos que probablemente sea un clic en recargar
            // (sin actividad reciente del usuario y sin navegación explícita)
            if (Date.now() - lastActivity < 100 && !exitUrl) {
                reloadAttempted = true;
                
                // Mostrar el modal en lugar del diálogo nativo
                setTimeout(() => {
                    $('#modalExit').modal('show');
                }, 10);
                
                // Prevenir la recarga
                e.preventDefault();
                e.returnValue = '';
                return '';
            }
            
            // Para otros casos (cerrar pestaña, etc.)
            e.preventDefault();
            e.returnValue = '';
            return '';
        });

        // Botones de salida
        $('.btn-home, .btn-exit, .btn-back').on('click', function(e) {
            e.preventDefault();
            exitUrl = $(this).attr('href') || $(this).data('href');
            $('#modalExit').modal('show');
        });

        // Confirmar salida
        $('#confirmExit').on('click', function(e) {
            e.preventDefault();
            $('#modalExit').modal('hide');
            
            // Permitir la salida/recarga
            allowUnload = true;
            
            if (reloadAttempted) {
                // Si fue un intento de recarga, recargar la página
                reloadAttempted = false;
                setTimeout(() => {
                    location.reload();
                }, 100);
            } else if (exitUrl) {
                // Si hay una URL específica, navegar a ella
                window.location.href = exitUrl;
            } else {
                // Si fue el botón atrás, permitir la navegación atrás
                history.back();
            }
        });

        // Cancelar salida
        $('#cancelExit').on('click', function() {
            $('#modalExit').modal('hide');
            exitUrl = null;
            reloadAttempted = false;
            allowUnload = false;
        });

        // Interceptar botón atrás
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.pushState(null, null, location.href);
            $('#modalExit').modal('show');
        };
        
        // Interceptar F5/Ctrl+R
        window.addEventListener('keydown', function(e) {
            const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
            const refreshCombo = 
                (e.key === 'F5') || 
                (e.ctrlKey && e.key.toLowerCase() === 'r') ||
                (isMac && e.metaKey && e.key.toLowerCase() === 'r');
                
            if (refreshCombo && !allowUnload) {
                e.preventDefault();
                reloadAttempted = true;
                $('#modalExit').modal('show');
            }
        });
    });
</script>

<script>
  const GAME_ID   = <?= json_encode(session()->get('game_id')) ?>;
  const API_DRAW  = <?= json_encode(site_url("api/games/" . session()->get('game_id') . "/draw")) ?>;
  const API_RESET = <?= json_encode(site_url("api/games/" . session()->get('game_id') . "/reset")) ?>;
  const API_STATE = <?= json_encode(site_url("api/games/" . session()->get('game_id') . "/state")) ?>;
  const API_AUTO_DRAW = <?= json_encode(site_url("api/games/" . session()->get('game_id') . "/auto-draw")) ?>;
  const AUTH_URL  = <?= json_encode(site_url('pusher/auth')) ?>;
  const PUSHER_KEY = <?= json_encode(env('PUSHER_KEY')) ?>;
  const PUSHER_CLUSTER = <?= json_encode(env('PUSHER_CLUSTER')) ?>;
</script>

<script src="https://js.pusher.com/8.2/pusher.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const statusEl = document.getElementById('status-text');
        const logEl = document.getElementById('log');
        const btnPlay = document.getElementById('start-button');
        const btnStop = document.getElementById('stop-button');
        const btnReset = document.getElementById('btnReset');
        const intervalInput = document.getElementById('intervalInput');
        const currentNumber = document.getElementById('current-number');
        const lastNumberCenter = document.getElementById('last-number-center');
        const blockNumber = document.getElementById('block-number');
        const lastFiveNumbers = document.getElementById('last-five-numbers');
        const ballsCounter = document.getElementById('balls-counter');
        
        let autoDrawInterval = null;
        let availableNumbers = [];
        let lastDrawnNumber = null;
        let drawnNumbers = [];
        let timeCounter = 0;
        let timeInterval = null;
        
        // Initialize available numbers
        for (let i = 1; i <= 75; i++) {
            availableNumbers.push(i);
        }
        
        function updateBallsCounter() {
            const drawn = drawnNumbers.length;
            const remaining = 75 - drawn;
            ballsCounter.textContent = `${drawn} - ${remaining}`;
        }
        
        function updateLastFiveNumbers() {
            lastFiveNumbers.innerHTML = '';
            
            // Get the last 4 numbers (excluding the current one)
            const lastFour = drawnNumbers.slice(-5, -1);
            
            // Create elements for each number
            lastFour.forEach(n => {
                const letterClass = getLetterClass(n);
                const div = document.createElement('div');
                div.className = `bingo-ball ${letterClass} size-40`;
                div.innerHTML = `<span>${n}</span>`;
                lastFiveNumbers.appendChild(div);
            });
        }
        
        function getLetterClass(n) {
            if (n >= 1 && n <= 15) return 'B';
            if (n >= 16 && n <= 30) return 'I';
            if (n >= 31 && n <= 45) return 'N';
            if (n >= 46 && n <= 60) return 'G';
            if (n >= 61 && n <= 75) return 'O';
            return '';
        }
        
        function log(x) { 
            console.log(x); 
            try { 
                const timestamp = new Date().toLocaleTimeString();
                const message = typeof x === 'string' ? x : JSON.stringify(x);
                logEl.textContent += `[${timestamp}] ${message}\n`;
                logEl.scrollTop = logEl.scrollHeight;
            } catch(e) {
                console.error("Logging error:", e);
            } 
        }
        
        function mark(n, isNew = false) { 
            const element = document.getElementById(`number-${n}`);
            const letterClass = getLetterClass(n);
            if (element) {
                element.classList.add(letterClass);
                
                if (isNew) {
                    // Update the last drawn number display
                    document.getElementById('last-number').className = `bingo-ball ${letterClass} size-100`;
                    document.getElementById('last-number').querySelector('small').textContent = letterClass;
                    currentNumber.textContent = n;
                    
                    // Show the center animation
                    lastNumberCenter.innerHTML = `<small style="position: absolute; top: -1px; font-size: 2.5rem; z-index: 1;">${letterClass}</small><span>${n}</span>`;
                    lastNumberCenter.className = `bingo-ball-200 ${letterClass} size-200`;
                    blockNumber.style.display = 'flex';
                    lastNumberCenter.style.display = 'flex';

                    setTimeout(() => {
                        blockNumber.style.display = 'none';
                        lastNumberCenter.style.display = 'none';
                    }, 2000);
                    
                    // Remove previous highlight
                    if (lastDrawnNumber) {
                        const prevElement = document.getElementById(`number-${lastDrawnNumber}`);
                        if (prevElement) {
                            prevElement.classList.remove('marked', 'last-drawn');
                        }
                    }
                    
                    // Add highlight to current number
                    element.classList.add('marked', 'last-drawn');
                    lastDrawnNumber = n;
                    
                    // Add to drawn numbers array
                    if (!drawnNumbers.includes(n)) {
                        drawnNumbers.push(n);
                        updateBallsCounter();
                        updateLastFiveNumbers();
                    }
                }
                
                // Remove from available numbers
                const index = availableNumbers.indexOf(n);
                if (index > -1) {
                    availableNumbers.splice(index, 1);
                }
                
                log(`Número ${n} marcado`);
            }
        }
        
        function unmarkAll() { 
            for (let i = 1; i <= 75; i++) {
                const element = document.getElementById(`number-${i}`);
                if (element) {
                    element.classList.remove('marked', 'last-drawn');
                }
            }
            
            // Reset variables
            availableNumbers = [];
            for (let i = 1; i <= 75; i++) availableNumbers.push(i);
            lastDrawnNumber = null;
            drawnNumbers = [];
            
            // Reset displays
            currentNumber.textContent = 'STOP';
            document.getElementById('last-number').className = 'bingo-ball STOP size-100';
            document.getElementById('last-number').querySelector('small').textContent = '';
            lastFiveNumbers.innerHTML = '';
            updateBallsCounter();
            
            log('Tablero reiniciado');
        }
        
        async function post(url, data) {
            try {
                const res = await fetch(url, {
                    method: 'POST', 
                    headers: {'Content-Type': 'application/json'}, 
                    body: JSON.stringify(data || {})
                });
                const json = await res.json();
                log({POST: url, status: res.status, response: json});
                return json;
            } catch (error) {
                log({POST_ERROR: url, error: error.message});
                throw error;
            }
        }
        
        async function drawRandomNumber() {
            if (availableNumbers.length === 0) {
                stopAutoPlay();
                statusEl.textContent = 'Completado';
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
                    statusEl.textContent = '¡BINGO!';
                    log('Juego detenido por BINGO');
                }
            } catch (error) {
                log(`Error al sortear número ${n}: ${error.message}`);
                const element = document.getElementById(`number-${n}`);
                if (element) {
                    element.classList.remove('marked', 'last-drawn');
                }
                availableNumbers.push(n);
            }
        }
        
        function startAutoPlay() {
            const interval = parseInt(intervalInput.value) * 1000;
            autoDrawInterval = setInterval(drawRandomNumber, interval);
            btnPlay.style.display = 'none';
            btnStop.style.display = 'inline-block';
            intervalInput.disabled = true;
            statusEl.textContent = 'Sorteando...';
            
            // Start time counter
            if (!timeInterval) {
                timeInterval = setInterval(updateTimer, 1000);
            }
            
            log(`Sorteo automático iniciado (intervalo: ${intervalInput.value}s)`);
        }
        
        function stopAutoPlay() {
            if (autoDrawInterval) {
                clearInterval(autoDrawInterval);
                autoDrawInterval = null;
            }
            btnPlay.style.display = 'inline-block';
            btnStop.style.display = 'none';
            intervalInput.disabled = false;
            
            if (statusEl.textContent === 'Sorteando...') {
                statusEl.textContent = 'Pausado';
            }
            
            log('Sorteo automático detenido');
        }
        
        function updateTimer() {
            timeCounter++;
            const hours = Math.floor(timeCounter / 3600).toString().padStart(2, '0');
            const minutes = Math.floor((timeCounter % 3600) / 60).toString().padStart(2, '0');
            const seconds = (timeCounter % 60).toString().padStart(2, '0');
            document.querySelector('.time-text').textContent = `${hours}:${minutes}:${seconds}`;
        }
        
        // Add event listeners to all number elements
        for (let i = 1; i <= 75; i++) {
            const element = document.getElementById(`number-${i}`);
            if (element) {
                element.addEventListener('click', async () => {
                    if (element.classList.contains('marked')) {
                        log(`Número ${i} ya está marcado`);
                        return;
                    }
                    
                    if (autoDrawInterval) {
                        stopAutoPlay();
                    }
                    
                    log(`Sorteando número manual: ${i}`);
                    
                    try {
                        mark(i, true);
                        const snap = await post(API_DRAW, { n: i });
                        
                        if (snap?.stopped) {
                            statusEl.textContent = '¡BINGO!';
                            log('Juego detenido por BINGO');
                        }
                    } catch (error) {
                        log(`Error al sortear número ${i}: ${error.message}`);
                        element.classList.remove('marked', 'last-drawn');
                        availableNumbers.push(i);
                    }
                });
            }
        }
        
        // Button event listeners
        btnPlay.addEventListener('click', startAutoPlay);
        btnStop.addEventListener('click', stopAutoPlay);
        btnReset.addEventListener('click', async () => {
            stopAutoPlay();
            log('Reiniciando juego...');
            
            try {
                await post(API_RESET, {});
                unmarkAll(); 
                statusEl.textContent = 'Reiniciado';
                
                // Reset timer
                if (timeInterval) {
                    clearInterval(timeInterval);
                    timeInterval = null;
                }
                timeCounter = 0;
                document.querySelector('.time-text').textContent = '00:00:00';
                
            } catch (error) {
                log(`Error al reiniciar: ${error.message}`);
            }
        });
        
        function beep() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const o = ctx.createOscillator(); 
                const g = ctx.createGain();
                o.type = 'square'; 
                o.frequency.value = 880;
                o.connect(g); 
                g.connect(ctx.destination);
                g.gain.setValueAtTime(0.001, ctx.currentTime);
                g.gain.exponentialRampToValueAtTime(0.2, ctx.currentTime + 0.01);
                o.start();
                setTimeout(() => {
                    g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.2);
                    o.stop(ctx.currentTime + 0.22);
                }, 160);
            } catch(e) {
                log('Error al reproducir sonido: ' + e.message);
            }
        }
        
        function showAlert(html, ms = 3500) {
            const el = document.getElementById('alert');
            el.innerHTML = html; 
            el.style.display = 'block';
            document.body.classList.add('flash'); 
            beep();
            setTimeout(() => { 
                el.style.display = 'none'; 
                document.body.classList.remove('flash'); 
            }, ms);
        }
        
        // Initialize Pusher
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
                statusEl.textContent = 'Reiniciado';
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
                    statusEl.textContent = '¡BINGO!';
                }
            });
            
            channel.bind('game:bingo_accepted', (p) => {
                log('📡 BINGO ACEPTADO: ' + JSON.stringify(p));
                showAlert(`¡BINGO! Ganador: ${p.playerId} · Cartón: ${p.cardId}`, 5000);
                stopAutoPlay();
                statusEl.textContent = '¡BINGO!';
            });
            
        } catch (error) {
            log('❌ Error al inicializar Pusher: ' + error.message);
        }
        
        // Initial state synchronization
        (async () => {
            try {
                log('🔄 Sincronizando estado inicial...');
                const r = await fetch(API_STATE); 
                const snap = await r.json();
                
                if (snap.drawn && snap.drawn.length > 0) {
                    log(`Marcando ${snap.drawn.length} números ya sorteados`);
                    drawnNumbers = [...snap.drawn];
                    snap.drawn.forEach(n => mark(n, false));
                    
                    // Update the last drawn number
                    if (snap.drawn.length > 0) {
                        const lastNumber = snap.drawn[snap.drawn.length - 1];
                        mark(lastNumber, true);
                    }
                    
                    updateBallsCounter();
                    updateLastFiveNumbers();
                }
                
                statusEl.textContent = snap.stopped ? '¡BINGO!' : 'Listo';
                log('✅ Sincronización completada');
                
            } catch (error) {
                log('❌ Error en sincronización inicial: ' + error.message);
            }
        })();
    });
</script>