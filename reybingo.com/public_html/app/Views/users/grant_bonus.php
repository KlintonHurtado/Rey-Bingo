<?php
$currency = esc(systemGet('currency'));
$selectedUserId = (int) ($selectedUserId ?? 0);
$selectedBonus = $selectedUser
    ? number_format((float) ($selectedUser['wallet_bonus'] ?? 0), 2)
    : null;
?>

<div class="modal-dialog modal-dialog-centered max-w-40">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2">
                <i class="fa-duotone fa-solid fa-gift"></i>
                <?= translate('grant bonus balance'); ?>
            </h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal" onclick="hideGrantBonusModal();">
                <i class="fa-duotone fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body pt-0">
            <p class="text-muted small text-center mb-3">
                <?= translate('grant bonus balance help'); ?>
            </p>

            <form id="grant-bonus-form" novalidate>
                <?= csrf_field() ?>

                <div class="mb-2">
                    <label for="grant-bonus-user" class="form-label"><?= translate('player'); ?></label>
                    <select class="form-control form-control-lg form-bingo" name="user_id" id="grant-bonus-user" required>
                        <option value=""><?= translate('select player'); ?></option>
                        <?php foreach ($players as $player) : ?>
                            <?php
                            $label = trim(($player['firstname'] ?? '') . ' ' . ($player['lastname'] ?? ''));
                            if ($label === '') {
                                $label = (string) ($player['username'] ?? '');
                            }
                            $bonusNow = number_format((float) ($player['wallet_bonus'] ?? 0), 2);
                            ?>
                            <option
                                value="<?= (int) $player['id']; ?>"
                                data-bonus="<?= esc($bonusNow); ?>"
                                <?= $selectedUserId === (int) $player['id'] ? 'selected' : ''; ?>
                            >
                                <?= esc(($player['code'] ?? '') . ' - ' . $label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small id="grant-bonus-user-error" class="text-danger d-none"></small>
                    <small class="text-muted d-block mt-1" id="grant-bonus-current-wrap">
                        <?= translate('current bonus balance'); ?>:
                        <strong id="grant-bonus-current"><?= $selectedBonus !== null ? ($currency . ' ' . $selectedBonus) : '—'; ?></strong>
                    </small>
                </div>

                <div class="mb-2">
                    <label for="grant-bonus-amount" class="form-label"><?= translate('amount'); ?></label>
                    <div class="input-group">
                        <span class="input-group-text"><?= $currency ?></span>
                        <input
                            type="number"
                            class="form-control form-control-lg form-bingo"
                            name="amount"
                            id="grant-bonus-amount"
                            min="0.01"
                            step="0.01"
                            required
                            placeholder="0.00"
                        >
                    </div>
                    <small id="grant-bonus-amount-error" class="text-danger d-none"></small>
                </div>

                <div class="mb-3">
                    <label for="grant-bonus-note" class="form-label"><?= translate('note'); ?> <span class="text-muted">(<?= translate('optional'); ?>)</span></label>
                    <input
                        type="text"
                        class="form-control form-control-lg form-bingo"
                        name="note"
                        id="grant-bonus-note"
                        maxlength="200"
                        placeholder="<?= translate('bonus grant note placeholder'); ?>"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-bingo w-100" id="grant-bonus-submit">
                    <i class="fa-duotone fa-solid fa-gift"></i> <?= translate('grant bonus'); ?>
                </button>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
(function () {
    window.hideGrantBonusModal = function () {
        var el = document.getElementById('modalGrantBonus');
        if (!el) {
            return;
        }
        if (typeof hideBsModal === 'function') {
            hideBsModal(el);
        } else if (typeof bootstrap !== 'undefined') {
            var instance = bootstrap.Modal.getInstance(el);
            if (instance) {
                instance.hide();
            } else {
                el.classList.remove('show');
                el.style.display = 'none';
                el.setAttribute('aria-hidden', 'true');
                document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
                    backdrop.remove();
                });
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            }
        }
    };

    function updateCurrentBonus() {
        var $opt = $('#grant-bonus-user option:selected');
        var bonus = $opt.data('bonus');
        if ($opt.val() && bonus !== undefined) {
            $('#grant-bonus-current').text('<?= $currency ?> ' + bonus);
        } else {
            $('#grant-bonus-current').text('—');
        }
    }

    $('#grant-bonus-user').off('change.grantBonus').on('change.grantBonus', updateCurrentBonus);
    updateCurrentBonus();

    $('#grant-bonus-form').off('submit.grantBonus').on('submit.grantBonus', function (e) {
        e.preventDefault();
        e.stopPropagation();

        $('#grant-bonus-user-error, #grant-bonus-amount-error').addClass('d-none').text('');

        var userId = $('#grant-bonus-user').val();
        var amount = parseFloat($('#grant-bonus-amount').val());
        var ok = true;

        if (!userId) {
            $('#grant-bonus-user-error').removeClass('d-none').text('<?= translate('select player'); ?>');
            ok = false;
        }
        if (!amount || amount <= 0 || isNaN(amount)) {
            $('#grant-bonus-amount-error').removeClass('d-none').text('<?= translate('invalid bonus amount'); ?>');
            ok = false;
        }
        if (!ok) {
            return;
        }

        var $btn = $('#grant-bonus-submit').prop('disabled', true);

        $.ajax({
            url: '<?= site_url('users/grantPlayerBonus'); ?>',
            method: 'POST',
            dataType: 'json',
            data: {
                user_id: userId,
                amount: amount,
                note: $('#grant-bonus-note').val() || '',
                <?= csrf_token(); ?>: '<?= csrf_hash(); ?>'
            },
            success: function (response) {
                Toastify({
                    text: response.message || response.error || (response.success ? 'OK' : 'Error'),
                    duration: 3500,
                    gravity: 'top',
                    position: 'right',
                    style: { background: response.success ? '#198754' : '#dc3545' },
                    stopOnFocus: true
                }).showToast();

                if (response.success) {
                    $('#grant-bonus-amount').val('');
                    $('#grant-bonus-note').val('');
                    if (response.wallet_bonus !== undefined && response.wallet_bonus !== null) {
                        var formatted = Number(response.wallet_bonus).toFixed(2);
                        $('#grant-bonus-user option:selected').attr('data-bonus', formatted);
                        $('#grant-bonus-current').text('<?= $currency ?> ' + formatted);
                    }

                    hideGrantBonusModal();

                    if (typeof loadPayments === 'function') {
                        loadPayments();
                    } else if (typeof applyFilters === 'function') {
                        applyFilters();
                    }
                    if (typeof viewUser === 'function' && userId) {
                        setTimeout(function () {
                            viewUser(userId);
                        }, 250);
                    }
                }
            },
            error: function () {
                Toastify({
                    text: '<?= translate('invalid bonus amount'); ?>',
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    style: { background: '#dc3545' },
                    stopOnFocus: true
                }).showToast();
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });
})();
</script>
