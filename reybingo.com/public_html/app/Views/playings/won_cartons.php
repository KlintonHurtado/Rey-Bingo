<?php
$currency = esc(systemGet('currency'));
$pendingPrizes = $pendingPrizes ?? [];
$gameOptions = $gameOptions ?? [];
$pendingTotal = (int) ($pendingTotal ?? 0);
?>

<?= view('playings/partials/player_nav_cluster', [
    'mode' => 'home',
    'wonCartonsExtraClass' => 'btn-won-cartons-profile is-active',
]); ?>

<a class="btn btn-small btn-logout" href="<?= site_url('logout'); ?>"><i class="fa-duotone fa-solid fa-arrow-right-from-arc"></i></a>

<div class="container admin-stores-page mt-5 pt-4">
    <div class="row d-flex justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card mb-3 admin-stores-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h5 class="mb-1"><i class="fa-duotone fa-solid fa-ticket"></i> Mis cartones ganados</h5>
                            <p class="text-muted small mb-0">
                                Aquí aparecen los cartones que reclamaste en la ruleta. Elige la partida y modalidad donde quieres usarlos.
                            </p>
                        </div>
                        <?php if ($pendingTotal > 0) : ?>
                            <span class="badge bg-warning text-dark fs-6"><?= $pendingTotal ?> pendiente<?= $pendingTotal === 1 ? '' : 's'; ?></span>
                        <?php endif; ?>
                    </div>

                    <div id="won-cartons-list">
                        <?php if (! empty($pendingPrizes)) : ?>
                            <?php foreach ($pendingPrizes as $prize) : ?>
                                <div class="card mb-3 won-carton-prize-card" data-roulette-id="<?= (int) $prize['id']; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?= (int) $prize['cartons']; ?> cartón<?= (int) $prize['cartons'] === 1 ? '' : 'es'; ?> de ruleta
                                                </h6>
                                                <small class="text-muted">
                                                    Reclamado el <?= date('d/m/Y H:i', strtotime($prize['created_at'])); ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-primary p-2">Pendiente de asignar</span>
                                        </div>

                                        <label class="form-label small">Elige partida y modalidad</label>
                                        <select class="form-control form-bingo won-carton-game-select mb-2">
                                            <option value="">Selecciona una partida...</option>
                                            <?php foreach ($gameOptions as $game) : ?>
                                                <option
                                                    value="<?= (int) $game['id']; ?>"
                                                    data-detail="<?= esc($game['detail'] ?? '', 'attr'); ?>"
                                                >
                                                    <?= esc($game['label']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted d-block won-carton-game-info mb-3"></small>

                                        <button type="button" class="btn btn-primary btn-bingo assign-won-cartons-btn">
                                            Usar cartones en esta partida
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="text-center py-5">
                                <i class="fa-duotone fa-solid fa-ticket fa-3x mb-3 text-muted"></i>
                                <p class="mb-1">No tienes cartones ganados pendientes.</p>
                                <small class="text-muted">Cuando gires la ruleta y reclames un premio, aparecerán aquí.</small>
                            </div>
                        <?php endif; ?>

                        <?php if (! empty($pendingPrizes) && empty($gameOptions)) : ?>
                            <div class="alert alert-warning mt-3 mb-0">
                                Tienes cartones pendientes, pero ninguna partida activa permite usarlos por ahora.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function updateGameInfo(selectEl) {
        const card = selectEl.closest('.won-carton-prize-card');
        const info = card ? card.querySelector('.won-carton-game-info') : null;
        const option = selectEl.options[selectEl.selectedIndex];
        if (!info) {
            return;
        }
        info.textContent = option && option.dataset.detail ? option.dataset.detail : '';
    }

    document.querySelectorAll('.won-carton-game-select').forEach(function(selectEl) {
        selectEl.addEventListener('change', function() {
            updateGameInfo(selectEl);
        });
    });

    document.querySelectorAll('.assign-won-cartons-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const card = button.closest('.won-carton-prize-card');
            if (!card) {
                return;
            }

            const rouletteId = parseInt(card.dataset.rouletteId, 10);
            const selectEl = card.querySelector('.won-carton-game-select');
            const gameId = selectEl ? parseInt(selectEl.value, 10) : 0;

            if (!gameId) {
                Toastify({
                    text: 'Selecciona la partida donde quieres usar tus cartones.',
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    style: { background: '#dc3545' },
                    stopOnFocus: true
                }).showToast();
                return;
            }

            button.disabled = true;
            button.textContent = 'Asignando...';

            $.ajax({
                url: '<?= site_url('playings/assignWonCartons'); ?>',
                method: 'POST',
                data: {
                    roulette_id: rouletteId,
                    game_id: gameId,
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Toastify({
                            text: response.message,
                            duration: 3500,
                            gravity: 'top',
                            position: 'right',
                            style: { background: '#198754' },
                            stopOnFocus: true
                        }).showToast();

                        if (typeof updateWonCartonsPendingBadge === 'function') {
                            updateWonCartonsPendingBadge(response.pending_cartons || 0);
                        }

                        if (response.redirect_url) {
                            setTimeout(function() {
                                window.location.href = response.redirect_url;
                            }, 1200);
                        } else {
                            card.remove();
                        }
                    } else {
                        Toastify({
                            text: response.message || 'No se pudieron asignar los cartones.',
                            duration: 3500,
                            gravity: 'top',
                            position: 'right',
                            style: { background: '#dc3545' },
                            stopOnFocus: true
                        }).showToast();
                        button.disabled = false;
                        button.textContent = 'Usar cartones en esta partida';
                    }
                },
                error: function() {
                    Toastify({
                        text: '<?= translate('there was an error in the request to the server'); ?>',
                        duration: 3500,
                        gravity: 'top',
                        position: 'right',
                        style: { background: '#dc3545' },
                        stopOnFocus: true
                    }).showToast();
                    button.disabled = false;
                    button.textContent = 'Usar cartones en esta partida';
                }
            });
        });
    });
})();
</script>
