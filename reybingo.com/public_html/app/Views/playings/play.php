
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
</style>

<div class="container">
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
                        <div class="card m-2 p-2 text-center" style="background: rgba(255,255,255,0.92); border-radius: 12px;">
                            <small class="text-muted d-block">Saldo total</small>
                            <strong><?= systemGet('currency'); ?> <?= number_format($walletTotal, 2); ?></strong>
                            <div class="d-flex justify-content-center gap-2 mt-1 flex-wrap" style="font-size: 0.75rem;">
                                <span>Recarga: <?= number_format($user['wallet_recharge'], 2); ?></span>
                                <span>Retiro: <?= number_format($user['wallet_withdraw'], 2); ?></span>
                                <span>Bono: <?= number_format($user['wallet_bonus'], 2); ?></span>
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
                                                <option value="<?= $game['id'] ?>"><?= $game['room'] ?> · <?= $game['description'] ?> · <?= systemGet('currency'); ?> <?= $game['price'] ?> · <?= translate_day($game['date'] . ' ' . $game['time']) ?></option>
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
                                                    <option value="<?= $game['id'] ?>"><?= $game['room'] ?> · <?= $game['description'] ?> · <?= systemGet('currency'); ?> <?= $game['price'] ?> · <?= translate_day($game['date'] . ' ' . $game['time']) ?></option>
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
                        <div class="play-section p-2">
                            <?php if (!empty($games)) : ?>
                                <div class="mb-2 px-1">
                                    <input type="search" id="play-games-search" class="form-control form-control-lg form-bingo" placeholder="Buscar sala o partida..." autocomplete="off">
                                </div>
                                <div class="mb-2 px-1">
                                    <input type="number" id="play-min-start-filter" class="form-control form-control-lg form-bingo" min="0" step="0.01" placeholder="Mínimo de inicio (precio cartón)">
                                </div>
                            <?php endif; ?>
                            <?php if (count($games) <= 1): ?>
                                <style>
                                    .play-cards {
                                        grid-template-columns: repeat(1, 1fr) !important;
                                    }

                                    .play-cards .card {
                                        width: 70%;
                                        margin: auto;
                                    }
                                </style>
                            <?php endif; ?>
                            <div class="play-cards">
                                <?php if (!empty($games)) : ?>
                                    <?php
                                        function getCardColor($index) {
                                            $colors = ['bingo-bg-primary', 'bingo-bg-success', 'bingo-bg-info', 'bingo-bg-warning', 'bingo-bg-danger', 'bingo-bg-secondary', 'bingo-bg-white', 'bingo-bg-dark', 'bingo-bg-orange', 'bingo-bg-purple'];
                                            return $colors[$index % count($colors)];
                                        }
                                    ?>
                                    <?php foreach ($games as $index => $game): ?>
                                        <div class="card <?= getCardColor($index) ?> text-center card-game-<?= $game['id'] ?>" data-game-id="<?= $game['id'] ?>" data-search-text="<?= esc(strtolower($game['room'] . ' ' . $game['description'])); ?>" data-price="<?= esc((string) $game['price']); ?>">
                                            <span class="card-hour"><?= translate_time($game['time']) ?></span>
                                            <div class="text-end p-1 pb-0">
                                                <button type="button" class="favorite-game-btn" data-favorite-game="<?= $game['id']; ?>" aria-label="Favorito">☆</button>
                                            </div>
                                            <span class="card-price text-center"><?= translate('carton'); ?>: <?= systemGet('currency'); ?> <?= $game['price'] ?></span>
                                            <img src="<?= site_url('assets/img/logo.png'); ?>" class="card-img-top p-1" alt="img">
                                            <div class="card-body p-1">
                                                <h5 class="card-title text-center mb-0">
                                                    <?= $game['room'] ?>
                                                    <div class="scrolling-text">
                                                        <span><?= $game['description'] ?></span>
                                                        <span><?= $game['description'] ?></span>
                                                    </div>
                                                </h5>
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
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <h3 class="no_active_rooms"><?= translate('there are no active rooms'); ?></h3>
                                <?php endif; ?>
                            </div>
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
            text: 'Código de afiliación: ' + text,
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
                        text = `EL PRÓXIMO JUEGO INICIA EN: <br /> ${days} DÍA${days > 1 ? 'S' : ''} ${hours} HORA${hours > 1 ? 'S' : ''} - ${minutes}:${seconds < 10 ? '0' : ''}${seconds} MIN`;
                    } else if (hours > 0) {
                        text = `EL PRÓXIMO JUEGO INICIA EN: <br /> ${hours} HORA${hours > 1 ? 'S' : ''} - ${minutes}:${seconds < 10 ? '0' : ''}${seconds} MIN`;
                    } else {
                        if (minutes === 0) {
                            const sec = Math.max(0, seconds);
                            text = `EL PRÓXIMO JUEGO INICIA EN: <br /> ${sec} SEGUNDO${sec === 1 ? '' : 'S'}`;
                        } else {
                            text = `EL PRÓXIMO JUEGO INICIA EN: <br /> ${minutes}:${seconds < 10 ? '0' : ''}${seconds} MINUTO${minutes === 1 ? '' : 'S'}`;
                        }
                    }

                    nextGameSpan.innerHTML = text;
                }

                updateCountdown();
                interval = setInterval(updateCountdown, 1000);
            });
        <?php endif; ?>
    <?php endif; ?>

    function applyGameFiltersAndFavorites() {
        const q = (document.getElementById('play-games-search')?.value || '').trim().toLowerCase();
        const minStart = parseFloat(document.getElementById('play-min-start-filter')?.value || '0') || 0;
        const favorites = JSON.parse(localStorage.getItem('favoriteGames') || '[]');

        const cards = Array.from(document.querySelectorAll('.play-cards .card[data-search-text]'));
        cards.forEach(function(card) {
            const text = (card.getAttribute('data-search-text') || '').toLowerCase();
            const price = parseFloat(card.getAttribute('data-price') || '0') || 0;
            const visible = (!q || text.includes(q)) && (price >= minStart);
            card.style.display = visible ? '' : 'none';
        });

        cards.sort(function(a, b) {
            const aFav = favorites.includes(parseInt(a.getAttribute('data-game-id') || '0', 10));
            const bFav = favorites.includes(parseInt(b.getAttribute('data-game-id') || '0', 10));
            if (aFav === bFav) return 0;
            return aFav ? -1 : 1;
        });

        const container = document.querySelector('.play-cards');
        if (container) {
            cards.forEach(function(card) { container.appendChild(card); });
        }

        document.querySelectorAll('[data-favorite-game]').forEach(function(btn) {
            const gameId = parseInt(btn.getAttribute('data-favorite-game') || '0', 10);
            const isFavorite = favorites.includes(gameId);
            btn.classList.toggle('is-favorite', isFavorite);
            btn.textContent = isFavorite ? '★' : '☆';
        });
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
</script>

<script src="<?= site_url('assets/js/play.js'); ?>?<?= md5(date("Hms")); ?>"></script>