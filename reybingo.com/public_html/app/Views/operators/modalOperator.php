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
                        <input type="text" class="form-control form-control-lg form-bingo" name="firstname" id="operator-firstname" value="<?= $isUpdate ? esc($operatorData['firstname']) : '' ?>" autocomplete="off" autofocus<?= $isUpdate ? ' readonly' : ''; ?>>
                        <small id="firstname-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="operator-lastname" class="form-label"><?= translate('last name'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="lastname" id="operator-lastname" value="<?= $isUpdate ? esc($operatorData['lastname']) : '' ?>" autocomplete="off"<?= $isUpdate ? ' readonly' : ''; ?>>
                        <small id="lastname-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="operator-document" class="form-label"><?= translate('document'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="document" id="operator-document" value="<?= $isUpdate ? esc($operatorData['document'] ?? '') : '' ?>" autocomplete="off"<?= $isUpdate ? ' readonly' : ''; ?>>
                        <small id="document-error" class="text-danger d-none"></small>
                        <?php if ($isUpdate) : ?>
                            <small class="text-muted">Nombre, correo y cédula no se pueden modificar.</small>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="operator-email" class="form-label"><?= translate('email'); ?></label>
                        <input type="email" class="form-control form-control-lg form-bingo" name="email" id="operator-email" value="<?= $isUpdate ? esc($operatorData['email']) : '' ?>" autocomplete="off"<?= $isUpdate ? ' readonly' : ''; ?>>
                        <small id="email-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-12 mb-2">
                        <label for="operator-business-name" class="form-label">Nombre Comercial / Marca del Operador</label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="business_name" id="operator-business-name" value="<?= $isUpdate ? esc($operatorData['business_name'] ?? '') : '' ?>" autocomplete="off" placeholder="Nombre comercial o marca">
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="operator-phone" class="form-label"><?= translate('phone'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="phone" id="operator-phone" value="<?= $isUpdate ? esc($operatorData['phone'] ?? '') : '' ?>" autocomplete="off" placeholder="<?= translate('phone'); ?>">
                        <small id="phone-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="operator-address" class="form-label"><?= translate('address'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="address_line" id="operator-address" value="<?= $isUpdate ? esc($operatorData['address_line'] ?? '') : '' ?>" autocomplete="off" placeholder="<?= translate('address'); ?>">
                        <small id="address_line-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="operator-city" class="form-label"><?= translate('city'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="city" id="operator-city" placeholder="<?= translate('city'); ?>" value="<?= $isUpdate ? esc($operatorData['city'] ?? '') : ''; ?>" autocomplete="off">
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="operator-state" class="form-label"><?= translate('state'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="state" id="operator-state" placeholder="<?= translate('state'); ?>" value="<?= $isUpdate ? esc($operatorData['state'] ?? '') : ''; ?>" autocomplete="off">
                    </div>

                    <div class="col-md-12 mb-2">
                        <label for="operator-last-mac" class="form-label"><i class="fa-solid fa-network-wired me-1"></i> Dirección MAC de Dispositivo</label>
                        <input type="text" class="form-control form-control-lg form-bingo font-monospace text-uppercase" name="last_mac" id="operator-last-mac" placeholder="00:1A:2B:3C:4D:5E" value="<?= $isUpdate ? esc($operatorData['last_mac'] ?? '') : ''; ?>" autocomplete="off">
                        <small class="text-muted">Asigna o actualiza la dirección MAC autorizada para el operador.</small>
                    </div>

                    <!-- Datos Bancarios Operador -->
                    <div class="col-md-12 mb-2 mt-2">
                        <h6 class="text-white border-bottom pb-1"><i class="fa-duotone fa-solid fa-building-columns me-1"></i> <?= translate('banking information'); ?></h6>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="operator-bank" class="form-label"><?= translate('bank'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="bank" id="operator-bank" placeholder="Nombre del Banco" value="<?= $isUpdate ? esc($operatorData['bank'] ?? '') : ''; ?>" autocomplete="off">
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="operator-account" class="form-label"><?= translate('account'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="account" id="operator-account" placeholder="<?= translate('enter account'); ?>" value="<?= $isUpdate ? esc($operatorData['account'] ?? '') : ''; ?>" autocomplete="off">
                    </div>

                    <div class="col-md-12 mb-2">
                        <label for="operator-account_type" class="form-label"><?= translate('account type'); ?></label>
                        <?php $opAccType = bingo_normalize_account_type($isUpdate ? ($operatorData['account_type'] ?? '') : ''); ?>
                        <select class="form-control form-control-lg form-bingo" name="account_type" id="operator-account_type">
                            <option value=""><?= translate('select a'); ?> <?= strtolower(translate('account type')); ?></option>
                            <option value="savings" <?= $opAccType === 'savings' ? 'selected' : ''; ?>><?= translate('savings account'); ?></option>
                            <option value="checking" <?= $opAccType === 'checking' ? 'selected' : ''; ?>><?= translate('checking account'); ?></option>
                        </select>
                    </div>

                    <div class="col-md-12 mb-2">
                        <label for="operator-password" class="form-label">
                            <?= translate('password'); ?>
                            <?= $isUpdate ? '<small class="text-muted">(' . translate('leave empty to keep current') . ')</small>' : '<span class="text-danger">*</span>' ?>
                        </label>
                        <input type="password" class="form-control form-control-lg form-bingo" name="password" id="operator-password" autocomplete="new-password">
                        <small id="password-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-4 d-flex flex-column justify-content-end mb-2">
                        <label for="operator_commission_rate" class="form-label mb-1"><?= translate('operator ggr affiliate rate'); ?> %</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control form-control-lg form-bingo" name="operator_commission_rate" id="operator_commission_rate"
                            value="<?= ($isUpdate && isset($operatorData['operator_commission_rate']) && $operatorData['operator_commission_rate'] !== null && $operatorData['operator_commission_rate'] !== '')
                                ? (float) $operatorData['operator_commission_rate'] * 100 : '' ?>"
                            placeholder="<?= translate('use global rate'); ?>">
                        <small id="operator_commission_rate-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-4 d-flex flex-column justify-content-end mb-2">
                        <label for="operator_recharge_rate" class="form-label mb-1"><?= translate('operator recharge rate'); ?> %</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control form-control-lg form-bingo" name="operator_recharge_rate" id="operator_recharge_rate"
                            value="<?= ($isUpdate && isset($operatorData['store_commission_rate']) && $operatorData['store_commission_rate'] !== null && $operatorData['store_commission_rate'] !== '')
                                ? (float) $operatorData['store_commission_rate'] * 100 : '' ?>"
                            placeholder="<?= translate('use global rate'); ?>">
                        <small id="operator_recharge_rate-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-4 d-flex flex-column justify-content-end mb-2">
                        <label for="operator_withdraw_rate" class="form-label mb-1"><?= translate('operator withdraw rate'); ?> %</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control form-control-lg form-bingo" name="operator_withdraw_rate" id="operator_withdraw_rate"
                            value="<?= ($isUpdate && isset($operatorData['store_prize_commission_rate']) && $operatorData['store_prize_commission_rate'] !== null && $operatorData['store_prize_commission_rate'] !== '')
                                ? (float) $operatorData['store_prize_commission_rate'] * 100 : '' ?>"
                            placeholder="<?= translate('use global rate'); ?>">
                        <small id="operator_withdraw_rate-error" class="text-danger d-none"></small>
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
