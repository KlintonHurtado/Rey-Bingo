
<a class="btn btn-small btn-profile" href="<?= site_url('profile'); ?>"><img src="<?= $imagePath ?>" alt="img"></a>

<button type="button" class="btn btn-small btn-wallet btn-wallet-profile" onclick="paymentsGet();">
    <i class="fa-duotone fa-solid fa-wallet"></i>
</button>

<span class="code-bgc fs-3 hidden" id="copyCode"><?= $user["code"] ?></span>

<button type="button" class="btn btn-small btn-gamepad btn-gamepad-profile" onclick="gamesGet();">
    <i class="fa-duotone fa-solid fa-gamepad"></i>
</button>

<button type="button" class="btn btn-small btn-qrcode" onclick="referralsGet();">
    <i class="fa-duotone fa-solid fa-qrcode"></i>
</button>

<button class="btn btn-small btn-volume hidden" onclick="RemoveVolume();">
    <?php if ($user['sounds'] == 1): ?>
        <i class="fa-duotone fa-solid fa-volume"></i>
    <?php else : ?>
        <i class="fa-duotone fa-solid fa-volume-slash"></i>
    <?php endif; ?>
</button>

<a class="btn btn-small btn-lock hidden" href="<?= site_url('password'); ?>"><i class="fa-duotone fa-solid fa-lock"></i></a>

<button class="btn btn-small btn-sliders" onclick="ViewSliders();"><i class="fa-duotone fa-solid fa-sliders-simple"></i></button>

<a class="btn btn-small btn-logout" href="<?= site_url('logout'); ?>"><i class="fa-duotone fa-solid fa-arrow-right-from-arc"></i></a>

<style>
    .next-game {
        background: rgba(9, 8, 39, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.28);
        border-radius: 12px;
        padding: 6px 10px;
        text-shadow: 0 2px 6px rgba(0, 0, 0, 0.35);
        box-shadow: 0 5px 14px rgba(0, 0, 0, 0.25);
    }
    .next-game:empty {
        display: none;
    }
    .favorite-game-btn {
        border: 0;
        background: rgba(255, 255, 255, 0.24);
        color: #fff;
        border-radius: 999px;
        width: 32px;
        height: 32px;
        font-size: 0.95rem;
    }
    .favorite-game-btn.is-favorite {
        background: rgba(255, 193, 7, 0.95);
        color: #522f00;
    }

    .player-play-view {
        padding-top: 5.5rem;
    }

    .player-wallet-card {
        margin-top: 0.5rem !important;
    }

    @media (min-width: 769px) {
        .player-play-view {
            padding-top: 2rem;
        }
    }

    .player-play-view .play-section--multi {
        height: auto;
        min-height: 0;
        max-height: none;
        width: 100%;
        overflow-x: auto !important;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
        scroll-snap-type: x mandatory;
        scrollbar-width: none;
        align-items: flex-start;
        justify-content: flex-start;
        padding-top: 0.25rem !important;
        padding-bottom: 5rem !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .player-play-view .play-section--multi::-webkit-scrollbar {
        display: none;
    }

    .player-play-view .play-cards--multi {
        display: flex !important;
        flex-direction: row;
        flex-wrap: nowrap;
        align-items: stretch;
        gap: 0;
        width: max-content;
        min-width: 100%;
        max-width: none !important;
        margin: 0;
        padding: 0 0 8px;
        grid-template-columns: unset !important;
    }

    .player-play-view .play-room-slide {
        flex: 0 0 var(--play-room-slide-step, 100%);
        width: var(--play-room-slide-step, 100%);
        min-width: var(--play-room-slide-step, 100%);
        max-width: var(--play-room-slide-step, 100%);
        display: flex;
        justify-content: center;
        align-items: stretch;
        scroll-snap-align: start;
        scroll-snap-stop: always;
        box-sizing: border-box;
    }

    .player-play-view .play-cards--multi .card {
        flex: 0 0 auto;
        width: var(--play-room-slide-width, min(300px, 74vw)) !important;
        min-width: var(--play-room-slide-width, min(300px, 74vw));
        max-width: var(--play-room-slide-width, min(300px, 74vw)) !important;
        margin: 0 !important;
    }

    .player-play-view .play-cards--multi .card .card-img-top {
        max-height: 120px;
        width: auto;
        margin: 0 auto;
        object-fit: contain;
    }

    .player-play-view .play-cards--multi .card .card-title,
    .player-play-view .play-cards--multi .card .card-body p {
        font-size: 0.9rem;
        margin: 2px 0;
    }

    .player-play-view .play-cards--multi .card .list-group-flush li {
        padding: 2px 6px !important;
        font-size: 0.8rem !important;
    }

    .player-play-view .play-cards--multi .card .card-body {
        padding: 6px 8px !important;
    }

    .player-play-view .play-cards--multi .card .btn {
        padding: 6px 10px;
        font-size: 0.82rem;
        margin-bottom: 4px !important;
    }

    .player-play-view .play-cards--multi .card-hour {
        font-size: 0.75rem;
        padding: 4px 8px;
        top: 8px;
        left: 8px;
    }

    .player-play-view .play-cards--multi .card-price {
        font-size: 0.75rem;
        padding: 5px 32px;
        top: 28px;
        right: -44px;
    }

    .player-play-view .play-section--single {
        max-height: calc(100dvh - 230px);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 !important;
    }

    .player-play-view .play-cards--single {
        grid-template-columns: repeat(1, 1fr) !important;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100%;
    }

    .player-play-view .play-cards--single .card {
        width: 60vw !important;
        max-width: 300px !important;
        margin: 0 auto;
    }

    .player-play-view .play-cards--single .card .card-img-top {
        max-height: 110px;
        width: auto;
        margin: 0 auto;
        object-fit: contain;
    }

    .player-play-view .play-cards--single .card .card-title {
        font-size: 0.9rem;
        margin: 2px 0;
    }

    .player-play-view .play-cards--single .card .list-group-flush li {
        padding: 2px 4px !important;
        font-size: 0.8rem !important;
    }

    .player-play-view .play-cards--single .card .card-body {
        padding: 6px 8px !important;
    }

    .player-play-view .play-cards--single .card .btn {
        padding: 6px 10px;
        font-size: 0.82rem;
        margin-bottom: 4px !important;
    }

    .play-rooms-carousel {
        position: relative;
        width: 100%;
        overflow: hidden;
    }

    .play-rooms-scroll-hint {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        z-index: 6;
        width: 48px;
        height: 48px;
        border: none;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.96);
        color: #6236ff;
        box-shadow: 0 3px 16px rgba(0, 0, 0, 0.3);
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        padding: 0;
        font-size: 1.25rem;
        line-height: 1;
    }

    .play-rooms-scroll-hint.is-visible {
        display: flex;
    }

    .play-rooms-scroll-hint--next {
        right: 6px;
        animation: playRoomsHintNext 1.2s ease-in-out infinite;
    }

    .play-rooms-scroll-hint--prev {
        left: 6px;
        animation: playRoomsHintPrev 1.2s ease-in-out infinite;
    }

    .play-rooms-carousel.has-scroll-right::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 52px;
        height: 100%;
        pointer-events: none;
        background: linear-gradient(to left, rgba(98, 54, 255, 0.35), transparent);
        z-index: 4;
    }

    @keyframes playRoomsHintNext {
        0%, 100% { transform: translateY(-50%) translateX(0); }
        50% { transform: translateY(-50%) translateX(5px); }
    }

    @keyframes playRoomsHintPrev {
        0%, 100% { transform: translateY(-50%) translateX(0); }
        50% { transform: translateY(-50%) translateX(-5px); }
    }
</style>

<div class="container player-play-view">
    <div class="row d-flex justify-content-center">
        <div class="<?php if (systemGet('activateRoomCards') != 1) : ?>col-md-5 col-xl-5<?php endif; ?>">
            <div class="row">
                <div class="col">
                    <div class="text-center">
                        <?php if (systemGet('activateRoomCards') != 1) : ?>
                            <img src="<?= site_url('assets/img/logo.png'); ?>" class="img-fluid logo w-50" alt="img">
                        <?php endif; ?>
                        <h5 class="mb-0 p-2 hidden"><?= translate('hello'); ?>, <?= session()->get('firstname'); ?>!</h5>
                        <h6 class="text-white text-center next-game mt-2 text-uppercase"></h6>
                        <?php
                            $user = wallet_service()->normalizeUser($user);
                            $walletTotal = wallet_total($user);
                        ?>
                        <div class="card m-2 p-2 text-center player-wallet-card" style="background: rgba(255,255,255,0.92); border-radius: 12px;">
                            <small class="text-muted d-block">Saldo total</small>
                            <strong><?= systemGet('currency'); ?> <span class="available-wallet wallet-total-value"><?= number_format($walletTotal, 2); ?></span></strong>
                            <div class="d-flex justify-content-center gap-2 mt-1 flex-wrap" style="font-size: 0.75rem;">
                                <span>Recarga: <span class="wallet-recharge-value"><?= number_format($user['wallet_recharge'], 2); ?></span></span>
                                <span style="color: #DD3A35; font-weight: bold;">Retiro: <span class="wallet-withdraw-value"><?= number_format($user['wallet_withdraw'], 2); ?></span></span>
                                <span>Bono: <span class="wallet-bonus-value"><?= number_format($user['wallet_bonus'], 2); ?></span></span>
                            </div>
                            <?php if (systemGet('activateRoulette') == 1 && ($user['roulette'] ?? 1) == 0) : ?>
                                <button type="button" class="btn btn-primary btn-bingo btn-sm mt-2" onclick="var m=document.getElementById('modalactivateRoulette'); if(m){ new bootstrap.Modal(m).show(); }">Girar ruleta</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (systemGet('activateRoomCards') != 1) : ?>
                        <?php if (systemGet('generateCartons') >= 1) : ?>
                            <div class="row">
                                <div class="col-md-12 mb-1">
                                    <label for="game" class="form-label"><?= translate('game room'); ?></label>
                                    <select class='form-control form-control-lg form-bingo' name="game" id="game" onchange="totalCartonsGet();">
                                        <?php if (!empty($games)) : ?>
                                            <?php foreach ($games as $game): ?>
                                                <option value="<?= $game['id'] ?>"><?= $game['room'] ?> ┬╖ <?= $game['description'] ?> ┬╖ <?= systemGet('currency'); ?> <?= $game['price'] ?> ┬╖ <?= translate_day($game['date'] . ' ' . $game['time']) ?></option>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <option value=""><?= translate('there are no active games'); ?></option>
                                        <?php endif; ?>
                                    </select>
                                    <small id="game-error" class="text-danger d-none"></small>
                                </div>
                                
                                <div class="col-md-12 mb-1 hidden">
                                    <label for="cartons" class="form-label"><?= translate('no. of cartons'); ?></label>
                                    <div class="input-group w-90">
                                        <button type="button" id="decrease-button" class="btn btn-small btn-primary btn-minus"><i class="fa-duotone fa-solid fa-minus"></i></button>
                                            <input type="number" class="form-control form-control-lg form-bingo text-center format" name="cartons" id="cartons" value="1" min="1" max="<?= systemGet('maxCartons'); ?>" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('no. of cartons')); ?>" autofocus autocomplete="off">
                                        <button type="button" id="increase-button" class="btn btn-small btn-primary btn-plus"><i class="fa-duotone fa-solid fa-plus"></i></button>
                                    </div>
                                    <small id="cartons-error" class="text-danger d-none"></small>
                                </div>

                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-small btn-primary d-block w-50 btn-bingo mt-3" onclick="availableCartonsGet();"><?= translate('select cartons'); ?></button>
                                </div>
                            </div>
                        <?php else : ?>
                            <?php echo form_open(site_url() . 'playings/playSubmit', array('enctype' => 'multipart/form-data', 'id' => 'play-form'));?>
                            
                                <?= csrf_field() ?>

                                <div class="row">
                                    <div class="col-md-12 mb-1">
                                        <label for="game" class="form-label"><?= translate('game room'); ?></label>
                                        <select class='form-control form-control-lg form-bingo' name="game" id="game" onchange="totalCartonsGet();">
                                            <?php if (!empty($games)) : ?>
                                                <?php foreach ($games as $game): ?>
                                                    <option value="<?= $game['id'] ?>"><?= $game['room'] ?> ┬╖ <?= $game['description'] ?> ┬╖ <?= systemGet('currency'); ?> <?= $game['price'] ?> ┬╖ <?= translate_day($game['date'] . ' ' . $game['time']) ?></option>
                                                <?php endforeach; ?>
                                            <?php else : ?>
                                                <option value=""><?= translate('there are no active games'); ?></option>
                                            <?php endif; ?>
                                        </select>
                                        <small id="game-error" class="text-danger d-none"></small>
                                    </div>
                                    
                                    <div class="col-md-12 mb-1">
                                        <label for="cartons" class="form-label"><?= translate('no. of cartons'); ?></label>
                                        <div class="input-group w-90">
                                            <button type="button" id="decrease-button" class="btn btn-small btn-primary btn-minus"><i class="fa-duotone fa-solid fa-minus"></i></button>
                                                <input type="number" class="form-control form-control-lg form-bingo text-center format" name="cartons" id="cartons" value="1" min="1" max="<?= systemGet('maxCartons'); ?>" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('no. of cartons')); ?>" autofocus autocomplete="off">
                                            <button type="button" id="increase-button" class="btn btn-small btn-primary btn-plus"><i class="fa-duotone fa-solid fa-plus"></i></button>
                                        </div>
                                        <small id="cartons-error" class="text-danger d-none"></small>
                                    </div>

                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-small btn-primary d-block w-50 btn-bingo mt-3" id="play-button"><?= translate('play'); ?></button>
                                    </div>
                                </div>
                            <?= form_close(); ?>
                        <?php endif; ?>
                    <?php else : ?>
                        <?php
                            $activeGameCount = is_array($games) ? count($games) : 0;
                            $playCardsLayoutClass = $activeGameCount > 1 ? 'play-cards--multi' : 'play-cards--single';
                            $playSectionLayoutClass = $activeGameCount > 1 ? 'play-section--multi' : 'play-section--single';
                        ?>
                        <div class="play-rooms-carousel" id="play-rooms-carousel">
                            <div class="play-section play-section--rooms <?= esc($playSectionLayoutClass) ?> p-2">
                            <?php if (!empty($games)) : ?>
                                <!-- Buscadores removidos según solicitud -->
                            <?php endif; ?>
                            <div class="play-cards <?= esc($playCardsLayoutClass) ?>">
                                <?php if (!empty($games)) : ?>
                                    <?php
                                        function getCardColor($index) {
                                            $colors = ['bingo-bg-primary', 'bingo-bg-success', 'bingo-bg-info', 'bingo-bg-warning', 'bingo-bg-danger', 'bingo-bg-secondary', 'bingo-bg-white', 'bingo-bg-dark', 'bingo-bg-orange', 'bingo-bg-purple'];
                                            return $colors[$index % count($colors)];
                                        }
                                    ?>
                                    <?php foreach ($games as $index => $game): ?>
                                        <div class="play-room-slide">
                                        <div class="card <?= getCardColor($index) ?> text-center card-game-<?= $game['id'] ?>" data-game-id="<?= $game['id'] ?>" data-search-text="<?= esc(strtolower($game['room'] . ' ' . $game['description'])); ?>" data-price="<?= esc((string) $game['price']); ?>">
                                            <span class="card-hour"><?= translate_time($game['time']) ?></span>
                                            <div class="text-end p-1 pb-0">
                                                <button type="button" class="favorite-game-btn" data-favorite-game="<?= $game['id']; ?>" aria-label="Favorito">Γÿå</button>
                                            </div>
                                            <span class="card-price text-center"><?= translate('carton'); ?>: <?= systemGet('currency'); ?> <?= $game['price'] ?></span>
                                            <img src="<?= site_url('assets/img/logo.png'); ?>" class="card-img-top p-1" alt="img">
                                                <div class="card-body p-1">
                                                    <h5 class="card-title text-center mb-0 d-block">
                                                        <?= $game['room'] ?>
                                                    </h5>
                                                    <p class="text-center mb-0" style="font-size:0.85rem; opacity:0.9;">
                                                        <?= $game['description'] ?>
                                                    </p>
                                                </div>
                                            <ul class="list-group list-group-flush">
                                                <li class="p-0" style="font-size: 0.8rem;"><?= translate_date($game['date']) ?></li>
                                                <li class="p-0" id="card-accumulated-<?= $game['id'] ?>"></li>
                                                <li class="p-0" style="font-size: 0.7rem;" id="card-time-<?= $game['id'] ?>"></li>
                                            </ul>
                                            <div class="card-body p-1">
                                                <?php if ($game['cartons'] >= 1) : ?>
                                                    <button type="submit" class="btn btn-small btn-primary d-block w-100 btn-bingo mb-1" id="card-button-play-<?= $game['id'] ?>" onclick="gameGet(<?= $game['id'] ?>);"><?= translate('come in to play'); ?></button>
                                                    <button type="submit" class="btn btn-small btn-primary d-block w-100 btn-bingo bingo-bg-success card-button-buy" id="card-button-buy-<?= $game['id'] ?>" onclick="generateCartonsGet(<?= $game['id'] ?>);"><?= translate('buy cartons'); ?></button>
                                                <?php else : ?>
                                                    <button type="submit" class="btn btn-small btn-primary d-block w-100 btn-bingo mb-1" id="card-button-play-<?= $game['id'] ?>" disabled><?= translate('come in to play'); ?></button>
                                                    <button type="submit" class="btn btn-small btn-primary d-block w-100 btn-bingo bingo-bg-success card-button-buy" id="card-button-buy-<?= $game['id'] ?>" onclick="generateCartonsGet(<?= $game['id'] ?>);"><?= translate('buy cartons'); ?></button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <h3 class="no_active_rooms"><?= translate('there are no active rooms'); ?></h3>
                                <?php endif; ?>
                            </div>
                            </div>
                            <button type="button" class="play-rooms-scroll-hint play-rooms-scroll-hint--prev" id="play-rooms-scroll-prev" aria-label="Ver salas anteriores" aria-hidden="true">
                                <i class="fa-duotone fa-solid fa-chevron-left"></i>
                            </button>
                            <button type="button" class="play-rooms-scroll-hint play-rooms-scroll-hint--next" id="play-rooms-scroll-next" aria-label="Deslizar para ver más salas" aria-hidden="true">
                                <i class="fa-duotone fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="whatsapp-plugin"></div>

<script type="text/javascript">
    <?php if (systemGet('generateCartons') == 0 && systemGet('activateRoomCards') == 0) : ?>
        var maxCartons = "<?= systemGet('maxCartons'); ?>";

        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('cartons');

            input.addEventListener('input', function () {
                let value = parseInt(this.value);

                if (value < 1) {
                    this.value = 1;
                } else if (value > maxCartons) {
                    this.value = maxCartons;
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const decreaseButton = document.getElementById('decrease-button');
            const increaseButton = document.getElementById('increase-button');
            const quantityInput = document.getElementById('cartons');

            decreaseButton.addEventListener('click', function() {
                let currentValue = parseInt(quantityInput.value, 10);
                if (currentValue > 1) {
                    quantityInput.value = currentValue - 1;
                }
            });

            increaseButton.addEventListener('click', function() {
                let currentValue = parseInt(quantityInput.value, 10);
                if (currentValue < maxCartons) {
                    quantityInput.value = currentValue + 1;
                }
            });
        });

        $(document).ready(function () {
            totalCartonsGet();
        });

        function totalCartonsGet() {
            const gameId = document.getElementById('game').value;

            fetch(`<?= site_url('playings/totalCartonsGet') ?>/${gameId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('<?= translate('error getting data'); ?>');
                    }
                    return response.json();
                })
                .then(data => {
                    const cartonsInput = document.getElementById('cartons');
                    if (data.totalCartons >= 1 && data.totalCartons <= maxCartons) {
                        cartonsInput.value = data.totalCartons;
                    } else {
                        cartonsInput.value = 1;
                    }
                })
                .catch(error => {
                    console.error("Error fetching total cartons:", error);
                });
        }
    <?php endif; ?>

    function gameGet(gameId) {
        fetch('<?= site_url('game') ?>/' + gameId, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                console.error('error when starting the game:', data.message || 'unknown error');
                Toastify({
                    text: "<?= translate('the game could not be started. try again'); ?>",
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    style: { background: "#dc3545" },
                    stopOnFocus: true
                }).showToast();
            }
        })
        .catch(error => {
            console.error('request error:', error);
            Toastify({
                text: "<?= translate('there was an error processing your request. Please try again'); ?>",
                duration: 3000,
                gravity: "top",
                position: "right",
                style: { background: "#dc3545" },
                stopOnFocus: true
            }).showToast();
        });
    }

    $('.format').change(function() {
        if (this.value) {
            this.value = parseFloat(this.value.replace(/,/g, ""));
        } else {
            $(this).val('1');
        }
    });

    document.getElementById('copyCode').addEventListener('click', function () {
        const text = this.textContent;
    
        const tempInput = document.createElement('input');
        tempInput.value = text;
        document.body.appendChild(tempInput);
    
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
    
        Toastify({
            text: 'C├│digo de afiliaci├│n: ' + text,
            duration: 3000,
            gravity: "top",
            position: "right",
            style: { background: "#198754" },
            stopOnFocus: true
        }).showToast();
    });

    <?php if (systemGet('activateRoomCards') == 0) : ?>
        <?php if (isset($lastGame) && count($lastGame) > 0): ?>
            document.addEventListener('DOMContentLoaded', () => {
                const gameDate = '<?= $lastGame["date"] ?> <?= $lastGame["time"] ?>';
                const nextGameSpan = document.querySelector('.next-game');
                const targetDate = new Date(gameDate);
                const now = new Date();

                let interval;

                function updateCountdown() {
                    const now = new Date();
                    const timeDiff = targetDate - now;

                    if (timeDiff <= 0) {
                        nextGameSpan.textContent = '';
                        clearInterval(interval);
                        return;
                    }

                    const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((timeDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((timeDiff % (1000 * 60)) / 1000);

                    let text = '';
                    if (days > 0) {
                        text = `EL PR├ôXIMO JUEGO INICIA EN: <br /> ${days} D├ìA${days > 1 ? 'S' : ''} ${hours} HORA${hours > 1 ? 'S' : ''} - ${minutes}:${seconds < 10 ? '0' : ''}${seconds} MIN`;
                    } else if (hours > 0) {
                        text = `EL PR├ôXIMO JUEGO INICIA EN: <br /> ${hours} HORA${hours > 1 ? 'S' : ''} - ${minutes}:${seconds < 10 ? '0' : ''}${seconds} MIN`;
                    } else {
                        if (minutes === 0) {
                            const sec = Math.max(0, seconds);
                            text = `EL PR├ôXIMO JUEGO INICIA EN: <br /> ${sec} SEGUNDO${sec === 1 ? '' : 'S'}`;
                        } else {
                            text = `EL PR├ôXIMO JUEGO INICIA EN: <br /> ${minutes}:${seconds < 10 ? '0' : ''}${seconds} MINUTO${minutes === 1 ? '' : 'S'}`;
                        }
                    }

                    nextGameSpan.innerHTML = text;
                }

                updateCountdown();
                interval = setInterval(updateCountdown, 1000);
            });
        <?php endif; ?>
    <?php endif; ?>

    window.setPlayRoomSlideWidths = function setPlayRoomSlideWidths() {
        const playSection = document.querySelector('.play-section--rooms.play-section--multi');
        const cardsContainer = playSection ? playSection.querySelector('.play-cards--multi') : null;
        if (!playSection || !cardsContainer) {
            return;
        }

        const viewport = playSection.clientWidth;
        const cardWidth = Math.min(300, Math.max(230, Math.round(viewport * 0.74)));

        playSection.style.setProperty('--play-room-slide-width', cardWidth + 'px');
        playSection.style.setProperty('--play-room-slide-step', viewport + 'px');
        cardsContainer.style.paddingLeft = '0';
        cardsContainer.style.paddingRight = '0';
    };

    window.updatePlayRoomsScrollHints = function updatePlayRoomsScrollHints() {
        const carousel = document.getElementById('play-rooms-carousel');
        const playSection = document.querySelector('.play-section--rooms');
        const prevBtn = document.getElementById('play-rooms-scroll-prev');
        const nextBtn = document.getElementById('play-rooms-scroll-next');

        if (!carousel || !playSection || !prevBtn || !nextBtn) {
            return;
        }

        if (typeof window.setPlayRoomSlideWidths === 'function') {
            window.setPlayRoomSlideWidths();
        }

        const canScroll = playSection.classList.contains('play-section--multi')
            && playSection.scrollWidth > playSection.clientWidth + 4;

        if (!canScroll) {
            prevBtn.classList.remove('is-visible');
            nextBtn.classList.remove('is-visible');
            carousel.classList.remove('has-scroll-right', 'has-scroll-left');
            prevBtn.setAttribute('aria-hidden', 'true');
            nextBtn.setAttribute('aria-hidden', 'true');
            return;
        }

        const maxScroll = playSection.scrollWidth - playSection.clientWidth;
        const atStart = playSection.scrollLeft <= 4;
        const atEnd = playSection.scrollLeft >= maxScroll - 4;

        prevBtn.classList.toggle('is-visible', !atStart);
        nextBtn.classList.toggle('is-visible', !atEnd);
        carousel.classList.toggle('has-scroll-right', !atEnd);
        carousel.classList.toggle('has-scroll-left', !atStart);
        prevBtn.setAttribute('aria-hidden', atStart ? 'true' : 'false');
        nextBtn.setAttribute('aria-hidden', atEnd ? 'true' : 'false');
    };

    window.initPlayRoomsScrollHints = function initPlayRoomsScrollHints() {
        const playSection = document.querySelector('.play-section--rooms');
        const prevBtn = document.getElementById('play-rooms-scroll-prev');
        const nextBtn = document.getElementById('play-rooms-scroll-next');

        if (!playSection || !prevBtn || !nextBtn) {
            return;
        }

        if (!window._playRoomsScrollHintsReady) {
            window._playRoomsScrollHintsReady = true;

            playSection.addEventListener('scroll', window.updatePlayRoomsScrollHints, { passive: true });
            window.addEventListener('resize', window.updatePlayRoomsScrollHints);

            prevBtn.addEventListener('click', function() {
                playSection.scrollBy({ left: -playSection.clientWidth, behavior: 'smooth' });
            });

            nextBtn.addEventListener('click', function() {
                playSection.scrollBy({ left: playSection.clientWidth, behavior: 'smooth' });
            });
        }

        window.updatePlayRoomsScrollHints();
    };

    window.syncPlayCardsLayout = function syncPlayCardsLayout() {
        const cardsContainer = document.querySelector('.play-cards');
        const playSection = document.querySelector('.play-section--rooms');
        if (!cardsContainer || !playSection) {
            return;
        }

        const visibleCards = Array.from(cardsContainer.querySelectorAll('[class*="card-game-"]'))
            .filter(function(card) {
                const slide = card.closest('.play-room-slide');
                if (slide) {
                    return slide.style.display !== 'none';
                }
                return card.style.display !== 'none';
            });
        const count = visibleCards.length;

        cardsContainer.classList.remove('play-cards--multi', 'play-cards--single');
        playSection.classList.remove('play-section--multi', 'play-section--single');

        if (count > 1) {
            cardsContainer.classList.add('play-cards--multi');
            playSection.classList.add('play-section--multi');
        } else if (count === 1) {
            cardsContainer.classList.add('play-cards--single');
            playSection.classList.add('play-section--single');
        }

        requestAnimationFrame(function() {
            if (typeof window.initPlayRoomsScrollHints === 'function') {
                window.initPlayRoomsScrollHints();
            }
        });
    };

    function applyGameFiltersAndFavorites() {
        const q = (document.getElementById('play-games-search')?.value || '').trim().toLowerCase();
        const minStart = parseFloat(document.getElementById('play-min-start-filter')?.value || '0') || 0;
        const favorites = JSON.parse(localStorage.getItem('favoriteGames') || '[]');

        const cards = Array.from(document.querySelectorAll('.play-cards .card[data-search-text]'));
        cards.forEach(function(card) {
            const text = (card.getAttribute('data-search-text') || '').toLowerCase();
            const price = parseFloat(card.getAttribute('data-price') || '0') || 0;
            const visible = (!q || text.includes(q)) && (price >= minStart);
            const slide = card.closest('.play-room-slide');
            if (slide) {
                slide.style.display = visible ? '' : 'none';
            } else {
                card.style.display = visible ? '' : 'none';
            }
        });

        cards.sort(function(a, b) {
            const aFav = favorites.includes(parseInt(a.getAttribute('data-game-id') || '0', 10));
            const bFav = favorites.includes(parseInt(b.getAttribute('data-game-id') || '0', 10));
            if (aFav === bFav) return 0;
            return aFav ? -1 : 1;
        });

        const container = document.querySelector('.play-cards');
        if (container) {
            const seenSlides = new Set();
            cards.forEach(function(card) {
                const slide = card.closest('.play-room-slide');
                const node = slide || card;
                if (seenSlides.has(node)) {
                    return;
                }
                seenSlides.add(node);
                container.appendChild(node);
            });
        }

        document.querySelectorAll('[data-favorite-game]').forEach(function(btn) {
            const gameId = parseInt(btn.getAttribute('data-favorite-game') || '0', 10);
            const isFavorite = favorites.includes(gameId);
            btn.classList.toggle('is-favorite', isFavorite);
            btn.textContent = isFavorite ? 'Γÿà' : 'Γÿå';
        });

        if (typeof window.syncPlayCardsLayout === 'function') {
            window.syncPlayCardsLayout();
        }
    }

    document.getElementById('play-games-search')?.addEventListener('input', applyGameFiltersAndFavorites);
    document.getElementById('play-min-start-filter')?.addEventListener('input', applyGameFiltersAndFavorites);
    document.querySelectorAll('[data-favorite-game]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const gameId = parseInt(this.getAttribute('data-favorite-game') || '0', 10);
            let favorites = JSON.parse(localStorage.getItem('favoriteGames') || '[]');
            if (favorites.includes(gameId)) {
                favorites = favorites.filter(function(id) { return id !== gameId; });
            } else {
                favorites.push(gameId);
            }
            localStorage.setItem('favoriteGames', JSON.stringify(favorites));
            applyGameFiltersAndFavorites();
        });
    });
    applyGameFiltersAndFavorites();

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.syncPlayCardsLayout === 'function') {
            window.syncPlayCardsLayout();
        } else if (typeof window.initPlayRoomsScrollHints === 'function') {
            window.initPlayRoomsScrollHints();
        }

        if (typeof window.refreshWalletFromServer === 'function') {
            window.refreshWalletFromServer();
        }
    });
</script>

<script src="<?= site_url('assets/js/play.js'); ?>?<?= md5(date("Hms")); ?>"></script>
