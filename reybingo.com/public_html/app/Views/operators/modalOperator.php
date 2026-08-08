<div class="modal-dialog modal-dialog-centered max-w-40">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2" id="operator-modal-title">
                <i class="fa-duotone fa-solid fa-user-tie"></i>
                <?= $isUpdate ? translate('edit operator') : translate('add operator'); ?>
            </h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body pt-0">
            <?php echo form_open(site_url('users/operatorSubmit'), ['id' => 'operator-form']); ?>
                <?= csrf_field() ?>
                <input type="hidden" id="operator-id" name="operator-id" value="<?= $isUpdate ? (int) $operatorData['id'] : '' ?>">
                <input type="hidden" id="operator-action" name="operator-action" value="<?= $isUpdate ? 'update' : 'add' ?>">

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label for="operator-firstname" class="form-label"><?= translate('first name'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="firstname" id="operator-firstname" value="<?= $isUpdate ? esc($operatorData['firstname']) : '' ?>" autocomplete="off" autofocus>
                        <small id="firstname-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="operator-lastname" class="form-label"><?= translate('last name'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="lastname" id="operator-lastname" value="<?= $isUpdate ? esc($operatorData['lastname']) : '' ?>" autocomplete="off">
                        <small id="lastname-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-12 mb-2">
                        <label for="operator-email" class="form-label"><?= translate('email'); ?></label>
                        <input type="email" class="form-control form-control-lg form-bingo" name="email" id="operator-email" value="<?= $isUpdate ? esc($operatorData['email']) : '' ?>" autocomplete="off">
                        <small id="email-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-12 mb-2">
                        <label for="operator-password" class="form-label">
                            <?= translate('password'); ?>
                            <?= $isUpdate ? '<small class="text-muted">(' . translate('leave empty to keep current') . ')</small>' : '<span class="text-danger">*</span>' ?>
                        </label>
                        <input type="password" class="form-control form-control-lg form-bingo" name="password" id="operator-password" autocomplete="new-password">
                        <small id="password-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label for="operator_commission_rate" class="form-label"><?= translate('operator ggr affiliate rate'); ?> %</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control form-control-lg form-bingo" name="operator_commission_rate" id="operator_commission_rate"
                            value="<?= ($isUpdate && isset($operatorData['operator_commission_rate']) && $operatorData['operator_commission_rate'] !== null && $operatorData['operator_commission_rate'] !== '')
                                ? (float) $operatorData['operator_commission_rate'] * 100 : '' ?>"
                            placeholder="<?= translate('use global rate'); ?>">
                        <small id="operator_commission_rate-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label for="operator_recharge_rate" class="form-label"><?= translate('operator recharge rate'); ?> %</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control form-control-lg form-bingo" name="operator_recharge_rate" id="operator_recharge_rate"
                            value="<?= ($isUpdate && isset($operatorData['store_commission_rate']) && $operatorData['store_commission_rate'] !== null && $operatorData['store_commission_rate'] !== '')
                                ? (float) $operatorData['store_commission_rate'] * 100 : '' ?>"
                            placeholder="<?= translate('use global rate'); ?>">
                        <small id="operator_recharge_rate-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label for="operator_withdraw_rate" class="form-label"><?= translate('operator withdraw rate'); ?> %</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control form-control-lg form-bingo" name="operator_withdraw_rate" id="operator_withdraw_rate"
                            value="<?= ($isUpdate && isset($operatorData['store_prize_commission_rate']) && $operatorData['store_prize_commission_rate'] !== null && $operatorData['store_prize_commission_rate'] !== '')
                                ? (float) $operatorData['store_prize_commission_rate'] * 100 : '' ?>"
                            placeholder="<?= translate('use global rate'); ?>">
                        <small id="operator_withdraw_rate-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-12 mb-2">
                        <small class="text-muted d-block"><?= translate('operator commission rate global help short'); ?></small>
                    </div>

                    <?php if ($isUpdate) : ?>
                        <div class="col-md-12 mb-2">
                            <small class="text-muted"><?= translate('username'); ?>: <strong><?= esc($operatorData['username']) ?></strong></small>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-12 mb-2">
                        <label class="form-label"><?= translate('assign points of sale'); ?></label>
                        <div class="operator-stores-checklist">
                            <?php if (! empty($stores)) : ?>
                                <?php foreach ($stores as $store) : ?>
                                    <?php
                                    $storeId = (int) $store['id'];
                                    $checked = in_array($storeId, $assignedStoreIds ?? [], true);
                                    $assignedToOther = ! $checked
                                        && ! empty($store['operator_id'])
                                        && (int) $store['operator_id'] !== (int) ($operatorData['id'] ?? 0);
                                    ?>
                                    <label class="operator-store-option <?= $assignedToOther ? 'is-disabled' : '' ?>">
                                        <input type="checkbox" name="store_ids[]" value="<?= $storeId; ?>"
                                            <?= $checked ? 'checked' : ''; ?>
                                            <?= $assignedToOther ? 'disabled' : ''; ?>>
                                        <span>
                                            <strong><?= esc($store['business_name'] ?? '-'); ?></strong>
                                            <small class="text-muted d-block"><?= esc($store['code'] ?? ''); ?></small>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p class="text-muted small mb-0"><?= translate('no stores found'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-12 d-flex justify-content-center mt-3">
                        <button type="submit" class="btn btn-primary btn-bingo w-50" id="operator-submit-button">
                            <?= $isUpdate ? translate('update') : translate('add'); ?>
                        </button>
                    </div>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $('#operator-form').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#operator-submit-button');
        $btn.prop('disabled', true);
        $('.text-danger').addClass('d-none').text('');
        $('.form-control').removeClass('is-invalid');

        $.ajax({
            url: '<?= site_url('users/operatorSubmit') ?>',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#modalOperator').modal('hide');
                    if (typeof operatorRefreshList === 'function') {
                        operatorRefreshList();
                    }
                    Toastify({
                        text: response.message,
                        duration: 3000,
                        gravity: 'top',
                        position: 'right',
                        style: { background: '#198754' },
                        stopOnFocus: true
                    }).showToast();
                } else if (response.errors) {
                    $.each(response.errors, function(field, message) {
                        $('#' + field + '-error').text(message).removeClass('d-none');
                        $('#operator-' + field + ', #' + field).addClass('is-invalid');
                    });
                } else if (response.message) {
                    Toastify({
                        text: response.message,
                        duration: 3000,
                        gravity: 'top',
                        position: 'right',
                        style: { background: '#dc3545' },
                        stopOnFocus: true
                    }).showToast();
                }
            },
            error: function() {
                Toastify({
                    text: '<?= esc(translate('there was an error in the request to the server.'), 'js'); ?>',
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    style: { background: '#dc3545' },
                    stopOnFocus: true
                }).showToast();
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
</script>
