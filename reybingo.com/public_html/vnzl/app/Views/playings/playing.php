<link rel="stylesheet" href="<?= site_url('assets/plyr/plyr.css'); ?>?<?= md5(date("Hms")); ?>">
<div class="container-section">
    <div class="top-section <?php if ($game['type'] == 3 || $game['type'] == 4): ?>live<?php endif; ?>">
        <a class="btn btn-small btn-home" href="<?= site_url('play'); ?>"><i class="fa-duotone fa-solid fa-house"></i></a>

        <button type="button" class="btn btn-small btn-wallet" onclick="paymentsGet();">
            <i class="fa-duotone fa-solid fa-wallet"></i>
        </button>

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
        
        <button class="btn btn-small btn-binary hidden" onclick="RemoveCheck();">
            <?php if ($user['autodial'] == 1): ?>
                <i class="fa-duotone fa-solid fa-binary-circle-check"></i>
            <?php else : ?>
                <i class="fa-duotone fa-solid fa-binary-slash"></i>
            <?php endif; ?>
        </button>

        <button class="btn btn-small btn-sliders" onclick="ViewSliders();"><i class="fa-duotone fa-solid fa-sliders-simple"></i></button>

        <?php if ($game['type'] == 3): ?>
            <div class="ratio ratio-16x9 video-responsive"> 
                <iframe src="<?= $game['url']; ?>" title="YouTube video player" frameborder="0" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen>
              </iframe>
            </div>
        <?php endif; ?>

        <?php if ($game['type'] == 4): ?>
            <video class="w-100" poster="<?= !empty($game['cover']) ? site_url('uploads/covers/' . $game['cover']) : site_url('uploads/covers/image.jpg'); ?>" id="plyr-video-player" playsinline="" controls=""><source src="<?= !empty($game['video']) ? site_url('uploads/videos/' . $game['video']) : site_url('uploads/videos/image.jpg'); ?>" type="video/mp4"></video>
        <?php endif; ?>

        <h6 class="total-balls m-0"><small><?= translate('total balls'); ?></small> <br /><span id="balls-counter"><?= $totalNumbersGenerated ?> - <?= 75 - $totalNumbersGenerated ?></span></h6>

        <h6 class="total-accumulated m-0"><small><?= translate('accumulated'); ?></small> <br /><span id="accumulated-counter" data-counter="0.00"><?= systemGet('currency'); ?> 0.00</span></h6>

        <?php if ($game['type'] != 3 && $game['type'] != 4): ?>
            <?php $class = $lastNumber ? $getClass($lastNumber) : 'STOP'; ?> <?php $letter = $lastNumber ? $getClass($lastNumber) : ''; ?>
            <div class="bingo-ball <?= $class ?> size-100" id="last-number"><small style="position: absolute; top: -13px; font-size: 1.2rem; z-index: 1;"><?= $letter ?></small><span><?= $lastNumber ? $lastNumber : 'STOP'; ?></span></div>
        <?php endif; ?>
        
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
            <button class="btn btn-small btn-board size-40" onclick="boardGet();"><i class="fa-duotone fa-solid fa-chess-board"></i></button>
        </div>

        <?php if ($game['type'] != 3 && $game['type'] != 4): ?>
            <h6 class="text-white text-center mb-0"><?= $game['description']; ?></h6>
            <h6 class="text-white text-center next-game mb-1 text-uppercase" style="font-size: 0.8rem;"></h6><span class="cursor"></span>
        <?php endif; ?>
    </div>
    <div class="center-section">
        <div class="cartons-section">
            <div class="content-cartons">
                <?php if (isset($cartons) && count($cartons) > 0): ?>
                    <?php foreach ($cartons as $cartonData): ?>
                        <?php
                            $singMatches = [];
                            foreach ($singsUser as $sing) {
                                if ($sing['carton'] == $cartonData['cartonId']) {
                                    $singMatches[] = array_map('intval', explode(',', $sing['numbers']));
                                }
                            }

                            $singNumbers = [];
                            foreach ($singMatches as $match) {
                                $singNumbers = array_merge($singNumbers, $match);
                            }
                            $singNumbers = array_unique($singNumbers);
                        ?>
                        <div class="bingo-border-carton">
                            <h6 class="ms-2 mb-1 text-center text-muted" style="font-size: 0.8rem;">SERIAL: C<?= $cartonData['serial']; ?></h6>
                            <div class="bingo-carton" id="carton-<?= $cartonData['cartonId']; ?>">
                                <div class="bingo-carton-header B"><span>B</span></div>
                                <div class="bingo-carton-header I"><span>I</span></div>
                                <div class="bingo-carton-header N"><span>N</span></div>
                                <div class="bingo-carton-header G"><span>G</span></div>
                                <div class="bingo-carton-header O"><span>O</span></div>

                                <?php foreach ($cartonData['numbers'] as $index => $number): ?>
                                    <?php
                                        $classes = [];
                                        if ($number['status'] == 1) {
                                            $classes[] = 'marked';
                                        }

                                        if (in_array((int)$number['number'], $singNumbers)) {
                                            $classes[] = 'carton-sing';
                                        }
                                    ?>
                                    <?php if ($index === 12): ?>
                                        <div class="bingo-carton-number modality data-position-13" data-position="<?= $number['position']; ?>">⭐️</div>
                                    <?php else: ?>
                                        <div class="bingo-carton-number number-<?= $number['number']; ?> <?= implode(' ', $classes); ?>"
                                            data-position="<?= $number['position']; ?>"
                                            id="number-<?= $number['number']; ?>"
                                            onclick="dialNumber(<?= $number['number']; ?>);">
                                            <?= $number['number']; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p><?= translate('there are no cards available for this game'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<button class="btn btn-small btn-chat" id="toggle-messages-btn"><i class="fa-duotone fa-solid fa-comments-question"></i></button>

<div class="bottom-section">
    <button class="btn btn-small btn-bingooo" onclick="singBingo();">BINGO!</button>
</div>

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
            <button type="button" class="message-btn" onclick="sendMessage('<?= translate('bingo!'); ?> 🥳', 21)"><?= translate('bingo!'); ?> 🥳</button>
            <button type="button" class="message-btn" onclick="sendMessage('<?= translate('ha ha ha'); ?> 🤣', 22)"><?= translate('ha ha ha'); ?> 🤣</button>
            <button type="button" class="message-btn" onclick="sendMessage('<?= translate('lets have fun!'); ?> 😉', 23)"><?= translate('lets have fun!'); ?> 😉</button>
            <button type="button" class="message-btn" onclick="sendMessage('<?= translate('good luck!'); ?> 🤑', 24)"><?= translate('good luck!'); ?> 🤑</button>
            <button type="button" class="message-btn" onclick="sendMessage('<?= translate('im missing a number'); ?> 🤩', 25)"><?= translate('im missing a number'); ?> 🤩</button>
        </div>

        <div class="input-group">
            <input type="text" class="form-control form-control-chat message-input" name="message-send-new" id="message-send-new" placeholder="<?= translate('write a message'); ?>..." autofocus autocomplete="off" maxlength="50">
            <button type="button" id="message-button" class="btn btn-small btn-primary btn-send" onclick="sendMessageText()"><i class="fa-duotone fa-solid fa-paper-plane-top"></i></button>
        </div>
    </div>
</div>

<div class="modal fade" id="modalModalities" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered max-w-30 max-w-90-xs">
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

<div class="modal fade" id="modalBoard" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered max-w-45 max-w-50-xs mx-auto">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title ps-2"><i class="fa-duotone fa-solid fa-table-cells"></i> <?= translate('board'); ?></h6>
                <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body pt-0 text-center">
                <div class="board-number">
                    <div class="column">
                        <div class="bingo-ball B size-30"><span>B</span></div>
                        <?php foreach (range(1, 15) as $number): ?>
                            <?php $class = in_array($number, $selectedNumbers) ? $getClass($number) : ''; ?>
                            <div class="bingo-ball <?= $class ?> size-30" id="board-number-<?= $number ?>">
                                <span><?= $number ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="column">
                        <div class="bingo-ball I size-30"><span>I</span></div>
                        <?php foreach (range(16, 30) as $number): ?>
                            <?php $class = in_array($number, $selectedNumbers) ? $getClass($number) : ''; ?>
                            <div class="bingo-ball <?= $class ?> size-30" id="board-number-<?= $number ?>">
                                <span><?= $number ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="column">
                        <div class="bingo-ball N size-30"><span>N</span></div>
                        <?php foreach (range(31, 45) as $number): ?>
                            <?php $class = in_array($number, $selectedNumbers) ? $getClass($number) : ''; ?>
                            <div class="bingo-ball <?= $class ?> size-30" id="board-number-<?= $number ?>">
                                <span><?= $number ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="column">
                        <div class="bingo-ball G size-30"><span>G</span></div>
                        <?php foreach (range(46, 60) as $number): ?>
                            <?php $class = in_array($number, $selectedNumbers) ? $getClass($number) : ''; ?>
                            <div class="bingo-ball <?= $class ?> size-30" id="board-number-<?= $number ?>">
                                <span><?= $number ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="column">
                        <div class="bingo-ball O size-30"><span>O</span></div>
                        <?php foreach (range(61, 75) as $number): ?>
                            <?php $class = in_array($number, $selectedNumbers) ? $getClass($number) : ''; ?>
                            <div class="bingo-ball <?= $class ?> size-30" id="board-number-<?= $number ?>">
                                <span><?= $number ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
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
                <h6 class="modal-title ps-2"><?= translate('Warning!'); ?></h6>
                <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body pt-0 text-center">
                <?= translate('if you exit the game you could lose your game data. We recommend you stay in the game.'); ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary d-block w-50 btn-bingo mt-3 pe-2" id="cancelExit"><?= translate('cancel'); ?></button>
                <a href="javascript:void(0);" class="btn btn-primary d-block w-50 btn-bingo mt-3" id="confirmExit">
                    <?= translate('accept'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script src="<?= site_url('assets/plyr/plyr.js'); ?>?<?= md5(date("Hms")); ?>"></script>

<script type="text/javascript">
    window.singBall = "<?= systemGet('singBall'); ?>";
    window.timeBallGet = singBall.split('-')[0];
    //window.timeBallLast = singBall.split('-')[1];
    window.timeBallLast = 2500;
    window.totalNumbersGenerated = "<?= $totalNumbersGenerated; ?>";
    window.fiveNumbers = <?= $lastNumbersJson ?? '[]' ?>;
    window.winners = <?= json_encode($winners) ?>;
    window.gameDate = '<?= $game["date"] ?> <?= $game["time"] ?>';

    document.addEventListener('DOMContentLoaded', function () {
        let exitUrl = null;
        let allowUnload = false;
        let reloadAttempted = false;

        // Detectar actividad del usuario
        let lastActivity = Date.now();
        document.addEventListener('mousemove', () => { lastActivity = Date.now(); });
        document.addEventListener('keydown', () => { lastActivity = Date.now(); });
        document.addEventListener('click', () => { lastActivity = Date.now(); });
        
        // Botones de salida - Mostrar modal al hacer clic
        $('.btn-home, .btn-exit, .btn-back').on('click', function(e) {
            e.preventDefault();
            exitUrl = $(this).attr('href') || $(this).data('href');
            $('#modalExit').modal('show');
        });

        // Confirmar salida desde el modal
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

        // Interceptar botón atrás del navegador
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

    <?php if ($game['type'] == 4): ?>
        !function() {
            new Plyr("#plyr-video-player");
            document.getElementsByClassName("plyr")[0].style.borderRadius = "0px 0px 10px 10px";
            document.getElementsByClassName("plyr__poster")[0].style.display = "none";
            let e = document.getElementsByTagName("html")[0],
            t = document.querySelector(".stick-top");
            window.addEventListener("scroll", function() {
                e.classList.contains("layout-navbar-fixed") ? t.classList.add("course-content-fixed") : t.classList.remove("course-content-fixed")
            })
        } ();
    <?php endif; ?>
</script>

<!-- Añadir al final del archivo, antes de los scripts existentes -->
<script src="https://js.pusher.com/8.2/pusher.min.js"></script>
<script>
    // Configuración de Pusher
    const GAME_ID = '<?= $game["id"] ?>';
    const AUTH_URL = '<?= site_url("pusher/auth") ?>';
    const PUSHER_KEY = '<?= env("PUSHER_KEY") ?>';
    const PUSHER_CLUSTER = '<?= env("PUSHER_CLUSTER") ?>';
    const USER_ID = '<?= session()->get('id') ?>';
</script>
<script src="<?= site_url('assets/js/pusher-client.js'); ?>?<?= md5(date("Hms")); ?>"></script>

<script src="<?= site_url('assets/js/playing.js'); ?>?<?= md5(date("Hms")); ?>"></script>