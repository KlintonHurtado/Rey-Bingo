<?php
$currency = esc(systemGet('currency'));
$configuredThreshold = systemGet('lowBalanceThreshold');
$thresholdValue = ($configuredThreshold !== null && $configuredThreshold !== '')
    ? number_format((float) $configuredThreshold, 2, '.', '')
    : '';
$autoRouletteEnabled = (int) systemGet('lowBalanceAutoRoulette') === 1;
$canManageRoulette = $canManageRoulette ?? (function_exists('bingo_can') ? bingo_can_any(['settings.manage', 'games.manage']) : false);
?>

<div class="admin-stores-scroll">
<?= view('games/partials/admin_nav_cluster', [
    'activeNav' => 'low_balance',
    'showHome' => true,
    'showStatistics' => false,
    'showUsers' => false,
]) ?>

<a class="btn btn-small btn-logout" href="<?= site_url('logout'); ?>"><i class="fa-duotone fa-solid fa-arrow-right-from-arc"></i></a>

<div class="container admin-stores-page">
    <div class="row d-flex justify-content-center">
        <div class="col-md-12">
            <div class="card mb-3 admin-stores-card">
                <div class="card-body p-3">
                    <h5 class="mb-3"><i class="fa-duotone fa-solid fa-sliders"></i> <?= translate('low balance configuration'); ?></h5>

                    <?php if (! $canManageRoulette) : ?>
                        <div class="alert alert-info d-flex align-items-center gap-2 mb-3 py-2" role="alert">
                            <i class="fa fa-eye"></i>
                            <span class="small">Estás viendo la información de saldo bajo y ruleta en modo <strong>solo lectura</strong>.</span>
                        </div>
                    <?php endif; ?>

                    <form id="low-balance-settings-form" class="row g-3 align-items-end">
                        <?= csrf_field() ?>
                        <div class="col-md-4">
                            <label for="lowBalanceThreshold" class="form-label"><?= translate('balance threshold'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><?= $currency ?></span>
                                <input
                                    type="number"
                                    class="form-control form-bingo"
                                    name="lowBalanceThreshold"
                                    id="lowBalanceThreshold"
                                    min="0"
                                    step="0.01"
                                    value="<?= esc($thresholdValue) ?>"
                                    placeholder="<?= number_format((float) ($threshold ?? 0), 2, '.', '') ?>"
                                    <?= $canManageRoulette ? 'required' : 'disabled readonly' ?>
                                >
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="lowBalanceAutoRoulette" class="form-label"><?= translate('low balance auto roulette short'); ?></label>
                            <select class="form-control form-bingo" name="lowBalanceAutoRoulette" id="lowBalanceAutoRoulette" <?= $canManageRoulette ? '' : 'disabled' ?>>
                                <option value="1" <?= $autoRouletteEnabled ? 'selected' : '' ?>><?= translate('active'); ?></option>
                                <option value="0" <?= ! $autoRouletteEnabled ? 'selected' : '' ?>><?= translate('inactive'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label d-none d-md-block">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary btn-bingo flex-grow-1" onclick="lowBalanceHistoryOpen();">
                                    <i class="fa-duotone fa-solid fa-clock-rotate-left"></i> <?= translate('history'); ?>
                                </button>
                                <?php if ($canManageRoulette) : ?>
                                <button type="submit" class="btn btn-primary btn-bingo flex-grow-1">
                                    <i class="fa-duotone fa-solid fa-floppy-disk"></i> <?= translate('save changes'); ?>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal fade" id="modalLowBalanceHistory" tabindex="-1" role="dialog" aria-hidden="true"></div>

            <div class="card mb-3 admin-stores-card">
                <div class="card-body p-3">
                    <div class="admin-stores-header d-flex justify-content-between align-items-center gap-3 mb-3">
                        <h5 class="mb-0">
                            <i class="fa-duotone fa-solid fa-coins"></i> <?= translate('low balance players'); ?>
                            <small class="text-muted ms-2" id="low-balance-threshold-label">
                                <?= $currency ?> <?= number_format((float) ($threshold ?? 0), 2) ?>
                                <?php if (! empty($players)) : ?>
                                    · <?= count($players) ?>
                                <?php endif; ?>
                            </small>
                        </h5>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <a
                                href="<?= site_url('users/exportLowBalancePlayers'); ?>"
                                class="btn btn-success btn-modal-add text-white stores-add-btn"
                                title="<?= translate('export'); ?>"
                            >
                                <i class="fa-duotone fa-solid fa-file-arrow-down"></i>
                            </a>
                            <button type="button" class="btn btn-primary btn-modal-add text-white stores-add-btn" onclick="lowBalancePlayersRefresh();" title="<?= translate('refresh'); ?>">
                                <i class="fa-duotone fa-solid fa-arrows-rotate"></i>
                            </button>
                        </div>
                    </div>

                    <?php if ((int) systemGet('activateRoulette') !== 1) : ?>
                        <p class="text-warning small mb-3">
                            <i class="fa-duotone fa-solid fa-triangle-exclamation"></i>
                            <?= translate('roulette disabled short'); ?>
                        </p>
                    <?php endif; ?>

                    <div class="table-responsive" id="low-balance-players-list">
                        <?= view('users/low_balance_players/list', [
                            'players' => $players ?? [],
                            'threshold' => $threshold ?? 0,
                            'canManageRoulette' => $canManageRoulette,
                        ]); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script type="text/javascript">
    function lowBalanceHistoryOpen() {
        $('#modalLowBalanceHistory').load('<?= site_url('users/lowBalanceHistoryListGet') ?>', function() {
            if (typeof showBsModal === 'function') {
                showBsModal('#modalLowBalanceHistory');
            } else {
                new bootstrap.Modal(document.getElementById('modalLowBalanceHistory')).show();
            }
        });
    }

    function lowBalanceHistoryRefresh() {
        if (! $('#modalLowBalanceHistory').hasClass('show')) {
            return;
        }

        $('#modalLowBalanceHistory').load('<?= site_url('users/lowBalanceHistoryListGet') ?>', function() {
            if (typeof showBsModal === 'function') {
                showBsModal('#modalLowBalanceHistory');
            }
        });
    }

    function lowBalancePlayersRefresh() {
        $.get('<?= site_url('users/lowBalancePlayersListGet') ?>', function(html) {
            $('#low-balance-players-list').html(html);
        });

        lowBalanceHistoryRefresh();

        if (typeof refreshLowBalancePendingBadge === 'function') {
            refreshLowBalancePendingBadge();
        }
    }

    var canManageRoulette = <?= $canManageRoulette ? 'true' : 'false' ?>;

    $('#low-balance-settings-form').on('submit', function(event) {
        event.preventDefault();
        if (! canManageRoulette) { return; }

        $.post('<?= site_url('users/lowBalanceSettingsSubmit') ?>', $(this).serialize(), function(response) {
            if (response.success) {
                const threshold = parseFloat($('#lowBalanceThreshold').val() || 0).toFixed(2);
                const count = $('#low-balance-players-list tbody tr').length;
                const emptyRow = $('#low-balance-players-list tbody tr td[colspan]').length > 0;
                const suffix = emptyRow ? '' : ' · ' + count;
                $('#low-balance-threshold-label').text('<?= $currency ?> ' + threshold + suffix);

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message,
                        timer: 3000,
                        showConfirmButton: false
                    });
                } else {
                    alert(response.message);
                }

                lowBalancePlayersRefresh();
            } else {
                Toastify({
                    text: response.error || response.message,
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    style: { background: '#dc3545' },
                    stopOnFocus: true
                }).showToast();
            }
        }, 'json');
    });

    function grantPlayerRoulette(userId, playerName) {
        if (! canManageRoulette) { return; }
        Swal.fire({
            title: '<?= esc(translate('grant roulette'), 'js'); ?>',
            text: '<?= esc(translate('are you sure you want to grant roulette to this player?'), 'js'); ?>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<?= esc(translate('yes'), 'js'); ?>',
            cancelButtonText: '<?= esc(translate('cancel'), 'js'); ?>'
        }).then(function(result) {
            if (!result.isConfirmed) {
                return;
            }

            $.post('<?= site_url('users/grantPlayerRoulette') ?>', {
                <?= csrf_token() ?>: '<?= csrf_hash() ?>',
                user_id: userId
            }, function(response) {
                if (response.success) {
                    lowBalancePlayersRefresh();
                    if (typeof updateLowBalancePendingBadge === 'function' && response.pending_count !== undefined) {
                        updateLowBalancePendingBadge(response.pending_count);
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: response.message,
                            timer: 3000,
                            showConfirmButton: false
                        });
                    } else {
                        alert(response.message);
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.error || 'Ocurrió un error inesperado',
                            timer: 3000,
                            showConfirmButton: false
                        });
                    } else {
                        alert(response.error || 'Ocurrió un error inesperado');
                    }
                }
            }, 'json');
        });
    }
</script>
