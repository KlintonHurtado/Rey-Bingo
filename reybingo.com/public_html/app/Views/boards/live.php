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
        font-size: 7rem;
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

    .border-carton.modality-won {
        border-color: #9e9e9e !important;
        box-shadow: 0 0 0 2px rgba(158, 158, 158, 0.35) !important;
        opacity: 0.6 !important;
        filter: grayscale(100%) !important;
    }

    .container-transmission {
        display: flex;
        flex-direction: column;
        height: 100vh;
    }

    .board-transmission {
        flex: 1;
        height: calc(100vh - 150px);
        display: flex;
        justify-content: center;
        align-items: center;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 10px;
    }

    .transmission {
        box-sizing: border-box; /* importante para que padding/bordes no rompan el cálculo */
        display: flex;
        flex-direction: column;
        height: 150px;        /* altura total del bloque */
        max-height: 150px;
        background: linear-gradient(0deg, rgba(98,54,255,1) 0%, rgba(135,103,250,1) 100%);
        border-radius: 0 0 50px 50px;
        box-shadow: 0 0 10px rgba(0,0,0,0.2), 0 0 20px rgba(0,0,0,0.3);
        overflow: hidden;     /* evita que el contenido sobresalga */
    }

    /* Zona superior: ocupa el resto (100% - 70px) */
    .top-content {
        display: flex;
        height: calc(100% - 70px); /* resta el footer fijo */
    }

    /* Columnas izquierda / derecha */
    .top-content > .left {
        flex: 1;                    /* 50% cada una */
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 8px;
        padding-top: 65px;
        box-sizing: border-box;
    }

    .top-content > .right {
        flex: 1;                    /* 50% cada una */
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 8px;
        padding-top: 75px;
        box-sizing: border-box;
    }

    .top-content .last-numbers {
        display: flex;
        justify-content: center;
        gap: 5px;
        background-color: rgba(255, 255, 255, 0.5);
        padding: 5px;
        border-radius: 50px;
        margin-top: 0px;
        margin-bottom: 0px;
    }

    .transmission .STOP {
        background: radial-gradient(circle at 30% 30%, #e5e5e5 10%, #b4b0b0 40%, #0c0c0c 100%);
        font-size: 1.5rem;
    }

    /* Chat admin LIVE */
    .board-admin-chat.message-display-container {
        display: none !important;
        flex-direction: column !important;
        position: fixed !important;
        bottom: 80px !important;
        left: 12px !important;
        width: 330px !important;
        max-width: calc(100vw - 24px) !important;
        height: 450px !important;
        max-height: calc(100vh - 100px) !important;
        z-index: 1100 !important;
        background: linear-gradient(180deg, rgba(24, 10, 84, 0.35) 0%, rgba(33, 16, 95, 0.82) 35%, rgba(55, 29, 146, 0.96) 100%) !important;
        border: none !important;
        border-radius: 16px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.35) !important;
        justify-content: space-between !important;
        overflow: hidden !important;
        pointer-events: auto !important;
    }
    .board-admin-chat.message-display-container.is-open {
        display: flex !important;
    }
    .board-admin-chat .message-display {
        flex-grow: 1 !important;
        width: 100% !important;
        height: calc(100% - 150px) !important;
        margin: 0 !important;
        padding: 0 10px !important;
        background: transparent !important;
    }
    .board-admin-chat .message-bubble {
        margin-bottom: 6px !important;
        background: #ffffff !important;
        opacity: 0.85 !important;
    }
    .board-admin-chat .emoji-slider,
    .board-admin-chat .message-bubble-slider {
        display: flex !important;
        flex-wrap: nowrap !important;
        overflow-x: auto !important;
        white-space: nowrap !important;
        width: 100% !important;
        gap: 4px !important;
    }
    .board-admin-chat .emoji-btn {
        width: 30px !important;
        height: 30px !important;
        margin: 3px 0 !important;
        border-radius: 50% !important;
        font-size: 18px !important;
        flex: 0 0 auto !important;
    }
    .board-admin-chat .message-btn {
        width: auto !important;
        padding: 6px 12px !important;
        margin: 3px 0 !important;
        border-radius: 50px !important;
        font-size: 13px !important;
        white-space: nowrap !important;
        flex: 0 0 auto !important;
    }
    body .btn-chat {
        position: fixed !important;
        left: 15px !important;
        bottom: 15px !important;
        z-index: 1101 !important;
        pointer-events: auto !important;
        width: 50px !important;
        height: 50px !important;
    }
    @media (max-width: 700px) {
        .board-admin-chat.message-display-container {
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            bottom: calc(64px + env(safe-area-inset-bottom, 0px)) !important;
            height: min(50vh, 420px) !important;
            border-radius: 16px 16px 0 0 !important;
        }
        body .btn-chat {
            bottom: calc(8px + env(safe-area-inset-bottom, 0px)) !important;
        }
    }
</style>
<div class="container-transmission">
    <div class="transmission">
        <a class="btn btn-small btn-home" href="<?= site_url('play'); ?>"><i class="fa-duotone fa-solid fa-house"></i></a>

        <button type="button" class="btn btn-small btn-wallet" onclick="paymentsGet();">
            <i class="fa-duotone fa-solid fa-wallet"></i>
        </button>

        <h6 class="total-balls m-0"><small><?= translate('total balls'); ?></small> <br /><span id="balls-counter"><?= $totalNumbersGenerated ?> - <?= 75 - $totalNumbersGenerated ?></span></h6>

        <div class="header-switch hidden">
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

        <h6 class="total-accumulated m-0"><small><?= translate('accumulated'); ?></small> <br /><span id="accumulated-counter" data-counter="0.00"><?= systemGet('currency'); ?> 0.00</span></h6>
        <div class="top-content">
            <div class="left">
                <?php $class = $lastNumber ? $getClass($lastNumber) : 'STOP'; ?> <?php $letter = $lastNumber ? $getClass($lastNumber) : ''; ?>
                <div class="bingo-ball <?= $class ?> size-130" id="last-number"><small style="position: absolute; top: -13px; font-size: 1.2rem; z-index: 1;"><?= $letter ?></small><span><?= $lastNumber ? $lastNumber : 'STOP'; ?></span></div>
            </div>
            <div class="right">
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
            </div>
        </div>
        <div class="footer">
            <h6 class="text-white text-center mb-0 fs-3"><?= $game['description']; ?></h6>
            <h6 class="text-white text-center next-game mb-1 text-uppercase fs-6"></h6>
        </div>
    </div>

    <div class="center-section">
        <div class="board-transmission">
            <div class="content-board">
                <div class="board-number">
                    <div id="block-number"><div class="bingo-ball-200 STOP size-200" id="last-number-center"><span><?= $lastNumber ? $lastNumber : 'STOP'; ?></span></div></div>
                    <div class="column">
                        <div class="bingo-ball B size-70"><span>B</span></div>
                        <?php foreach (range(1, 15) as $number): ?>
                            <?php
                                $isSelected = in_array($number, $selectedNumbers);
                                $class = $isSelected ? $getClass($number) : '';
                                $addOnClick = !$isSelected;
                            ?>
                            <div class="bingo-ball size-70 <?= $class ?>" <?= $addOnClick ? 'onclick="generateNumber(' . $number . ');"' : '' ?> id="number-<?= $number ?>">
                                <span><?= $number ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="column">
                        <div class="bingo-ball I size-70"><span>I</span></div>
                        <?php foreach (range(16, 30) as $number): ?>
                            <?php
                                $isSelected = in_array($number, $selectedNumbers);
                                $class = $isSelected ? $getClass($number) : '';
                                $addOnClick = !$isSelected;
                            ?>
                            <div class="bingo-ball size-70 <?= $class ?>" <?= $addOnClick ? 'onclick="generateNumber(' . $number . ');"' : '' ?> id="number-<?= $number ?>">
                                <span><?= $number ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="column">
                        <div class="bingo-ball N size-70"><span>N</span></div>
                        <?php foreach (range(31, 45) as $number): ?>
                            <?php
                                $isSelected = in_array($number, $selectedNumbers);
                                $class = $isSelected ? $getClass($number) : '';
                                $addOnClick = !$isSelected;
                            ?>
                            <div class="bingo-ball size-70 <?= $class ?>" <?= $addOnClick ? 'onclick="generateNumber(' . $number . ');"' : '' ?> id="number-<?= $number ?>">
                                <span><?= $number ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="column">
                        <div class="bingo-ball G size-70"><span>G</span></div>
                        <?php foreach (range(46, 60) as $number): ?>
                            <?php
                                $isSelected = in_array($number, $selectedNumbers);
                                $class = $isSelected ? $getClass($number) : '';
                                $addOnClick = !$isSelected;
                            ?>
                            <div class="bingo-ball size-70 <?= $class ?>" <?= $addOnClick ? 'onclick="generateNumber(' . $number . ');"' : '' ?> id="number-<?= $number ?>">
                                <span><?= $number ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="column">
                        <div class="bingo-ball O size-70"><span>O</span></div>
                        <?php foreach (range(61, 75) as $number): ?>
                            <?php
                                $isSelected = in_array($number, $selectedNumbers);
                                $class = $isSelected ? $getClass($number) : '';
                                $addOnClick = !$isSelected;
                            ?>
                            <div class="bingo-ball size-70 <?= $class ?>" <?= $addOnClick ? 'onclick="generateNumber(' . $number . ');"' : '' ?> id="number-<?= $number ?>">
                                <span><?= $number ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bottom-section">
    <?php if ($status != 'stop') : ?>
    <div class="controls-board text-center my-3" id="controls">
        <button id="start-button" class="btn btn-small btn-primary size-50"><i class="fa-duotone fa-solid fa-play"></i></button>
        <button id="play-button" class="btn btn-small btn-primary size-50" style="display: none;"><i class="fa-duotone fa-solid fa-play"></i></button>
        <button id="stop-button" class="btn btn-small btn-primary size-50" style="display: none;"><i class="fa-duotone fa-solid fa-pause"></i></button>
        <button id="next-number-button" class="btn btn-small btn-primary size-50" style="display: none;"><i class="fa-duotone fa-solid fa-forward-step"></i></button>
    </div>
    <?php endif; ?>
</div>

<button type="button" class="btn btn-small btn-chat" id="toggle-messages-btn" aria-label="Chat" aria-expanded="false"><i class="fa-duotone fa-solid fa-comments-question"></i></button>

<div class="message-display-container board-admin-chat" id="message-display-container" aria-hidden="true">
    <div class="message-display-container__toolbar">
        <button type="button" class="message-display-close" id="message-display-close" aria-label="Cerrar">
            <i class="fa-duotone fa-solid fa-xmark"></i>
        </button>
    </div>
    <div class="message-display" id="message-display" aria-live="polite"></div>
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
            <input type="text" class="form-control form-control-chat message-input" name="message-send-new" id="message-send-new" placeholder="<?= translate('write a message'); ?>..." autocomplete="off" maxlength="50">
            <button type="button" id="message-button" class="btn btn-small btn-primary btn-send"><i class="fa-duotone fa-solid fa-paper-plane-top"></i></button>
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
                            <div class="border-carton <?= $isSing ? 'modality-won' : '' ?>">
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

<div class="modal fade" id="modalPlayers" tabindex="-1" role="dialog"></div>

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
    window.site_url = "<?= site_url(); ?>/";
    var site_url = window.site_url;
    window.selectedNumbers = <?= json_encode($selectedNumbers ?? []); ?>;
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

<script src="<?= site_url('assets/js/live.js'); ?>?<?= md5(date("Hms")); ?>"></script>