<div class="modal-dialog modal-dialog-centered max-w-40">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2" id="store-modal-title">
                <i class="fa-duotone fa-solid fa-store"></i>
                <?= $isUpdate ? translate('edit store') : translate('add store'); ?>
            </h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body pt-0">
            <?php echo form_open(site_url('users/storeSubmit'), ['id' => 'store-form']); ?>
                <?= csrf_field() ?>
                <input type="hidden" id="store-id" name="store-id" value="<?= $isUpdate ? (int) $storeData['id'] : '' ?>">
                <input type="hidden" id="store-action" name="store-action" value="<?= $isUpdate ? 'update' : 'add' ?>">

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label for="firstname" class="form-label"><?= translate('first name'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="firstname" id="firstname" value="<?= $isUpdate ? esc($storeData['firstname']) : '' ?>" autocomplete="off" autofocus>
                        <small id="firstname-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="lastname" class="form-label"><?= translate('last name'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="lastname" id="lastname" value="<?= $isUpdate ? esc($storeData['lastname']) : '' ?>" autocomplete="off">
                        <small id="lastname-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-12 mb-2">
                        <label for="business_name" class="form-label"><?= translate('business name'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="business_name" id="business_name" value="<?= $isUpdate ? esc($storeData['business_name'] ?? '') : '' ?>" autocomplete="off">
                        <small id="business_name-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="document" class="form-label"><?= translate('document'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="document" id="document" value="<?= $isUpdate ? esc($storeData['document'] ?? '') : '' ?>" autocomplete="off">
                        <small id="document-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="email" class="form-label"><?= translate('email'); ?></label>
                        <input type="email" class="form-control form-control-lg form-bingo" name="email" id="email" value="<?= $isUpdate ? esc($storeData['email']) : '' ?>" autocomplete="off">
                        <small id="email-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="phone" class="form-label"><?= translate('phone'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="phone" id="phone" value="<?= $isUpdate ? esc($storeData['phone'] ?? '') : '' ?>" autocomplete="off">
                        <small id="phone-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-12 mb-2">
                        <label for="address_line" class="form-label"><?= translate('address'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="address_line" id="address_line" value="<?= $isUpdate ? esc($storeData['address_line'] ?? '') : '' ?>" autocomplete="off">
                        <small id="address_line-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="city" class="form-label"><?= translate('city'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="city" id="city" placeholder="<?= translate('city'); ?>" value="<?= $isUpdate ? esc($storeData['city'] ?? '') : ''; ?>" autocomplete="off">
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="state" class="form-label"><?= translate('state'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="state" id="state" placeholder="<?= translate('state'); ?>" value="<?= $isUpdate ? esc($storeData['state'] ?? '') : ''; ?>" autocomplete="off">
                    </div>

                    <div class="col-md-12 mb-2">
                        <label for="last_mac" class="form-label"><i class="fa-solid fa-network-wired me-1"></i> Dirección MAC de Terminal / Dispositivo</label>
                        <input type="text" class="form-control form-control-lg form-bingo font-monospace text-uppercase" name="last_mac" id="last_mac" placeholder="00:1A:2B:3C:4D:5E" value="<?= $isUpdate ? esc($storeData['last_mac'] ?? '') : ''; ?>" autocomplete="off">
                        <small class="text-muted">Asigna la MAC de la máquina o terminal autorizada para este Punto de Venta.</small>
                    </div>

                    <!-- Datos Bancarios PV -->
                    <div class="col-md-12 mb-2 mt-2">
                        <h6 class="text-white border-bottom pb-1"><i class="fa-duotone fa-solid fa-building-columns me-1"></i> <?= translate('banking information'); ?></h6>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="bank" class="form-label"><?= translate('bank'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="bank" id="bank" placeholder="Nombre del Banco" value="<?= $isUpdate ? esc($storeData['bank'] ?? '') : ''; ?>" autocomplete="off">
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="account" class="form-label"><?= translate('account'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="account" id="account" placeholder="<?= translate('enter account'); ?>" value="<?= $isUpdate ? esc($storeData['account'] ?? '') : ''; ?>" autocomplete="off">
                    </div>

                    <div class="col-md-12 mb-2">
                        <label for="account_type" class="form-label"><?= translate('account type'); ?></label>
                        <?php $storeAccType = bingo_normalize_account_type($isUpdate ? ($storeData['account_type'] ?? '') : ''); ?>
                        <select class="form-control form-control-lg form-bingo" name="account_type" id="account_type">
                            <option value=""><?= translate('select a'); ?> <?= strtolower(translate('account type')); ?></option>
                            <option value="savings" <?= $storeAccType === 'savings' ? 'selected' : ''; ?>><?= translate('savings account'); ?></option>
                            <option value="checking" <?= $storeAccType === 'checking' ? 'selected' : ''; ?>><?= translate('checking account'); ?></option>
                        </select>
                    </div>

                    <div class="col-md-12 mb-2">
                        <label for="operator_id" class="form-label"><?= translate('operator'); ?></label>
                        <select class="form-control form-control-lg form-bingo" name="operator_id" id="operator_id">
                            <option value=""><?= translate('no operator assigned'); ?></option>
                            <?php foreach ($operators ?? [] as $operator) : ?>
                                <option value="<?= (int) $operator['id']; ?>"
                                    <?= ($isUpdate && (int) ($storeData['operator_id'] ?? 0) === (int) $operator['id']) ? 'selected' : ''; ?>>
                                    <?= esc(trim(($operator['firstname'] ?? '') . ' ' . ($operator['lastname'] ?? ''))); ?>
                                    (<?= esc($operator['code'] ?? ''); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-12 mb-2">
                        <label for="store_commission_rate" class="form-label"><?= translate('store recharge commission rate'); ?> %</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control form-control-lg form-bingo" name="store_commission_rate" id="store_commission_rate"
                            value="<?= ($isUpdate && isset($storeData['store_commission_rate']) && $storeData['store_commission_rate'] !== null && $storeData['store_commission_rate'] !== '')
                                ? (float) $storeData['store_commission_rate'] * 100
                                : '' ?>"
                            placeholder="<?= translate('use global rate'); ?>">
                        <small id="store_commission_rate-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="ggr_commission_rate" class="form-label"><?= translate('ggr commission rate'); ?> %</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control form-control-lg form-bingo" name="ggr_commission_rate" id="ggr_commission_rate"
                            value="<?= ($isUpdate && isset($storeData['ggr_commission_rate']) && $storeData['ggr_commission_rate'] !== null && $storeData['ggr_commission_rate'] !== '')
                                ? (float) $storeData['ggr_commission_rate'] * 100
                                : '' ?>"
                            placeholder="<?= translate('use global rate'); ?>">
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="store_prize_commission_rate" class="form-label"><?= translate('store prize commission rate'); ?> %</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control form-control-lg form-bingo" name="store_prize_commission_rate" id="store_prize_commission_rate"
                            value="<?= ($isUpdate && isset($storeData['store_prize_commission_rate']) && $storeData['store_prize_commission_rate'] !== null && $storeData['store_prize_commission_rate'] !== '')
                                ? (float) $storeData['store_prize_commission_rate'] * 100
                                : '' ?>"
                            placeholder="<?= translate('use global rate'); ?>">
                        <small id="store_prize_commission_rate-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-12 mb-2">
                        <small class="text-muted d-block"><?= translate('ggr commission rate store help'); ?></small>
                    </div>

                    <div class="col-md-12 mb-2">
                        <label for="password" class="form-label">
                            <?= translate('password'); ?>
                            <?= $isUpdate ? '<small class="text-muted">(' . translate('leave empty to keep current') . ')</small>' : '<span class="text-danger">*</span>' ?>
                        </label>
                        <input type="password" class="form-control form-control-lg form-bingo" name="password" id="password" autocomplete="new-password">
                        <small id="password-error" class="text-danger d-none"></small>
                    </div>

                    <?php if ($isUpdate) : ?>
                        <div class="col-md-12 mb-2">
                            <small class="text-muted"><?= translate('username'); ?>: <strong><?= esc($storeData['username']) ?></strong></small>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-12 d-flex justify-content-center mt-3">
                        <button type="submit" class="btn btn-primary btn-bingo w-50" id="store-submit-button">
                            <?= $isUpdate ? translate('update') : translate('add'); ?>
                        </button>
                    </div>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $('#store-form').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#store-submit-button');
        $btn.prop('disabled', true);
        $('.text-danger').addClass('d-none').text('');
        $('.form-control').removeClass('is-invalid');

        $.ajax({
            url: '<?= site_url('users/storeSubmit') ?>',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#modalStore').modal('hide');
                    if (typeof storeRefreshList === 'function') {
                        storeRefreshList();
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
                        $('#' + field).addClass('is-invalid');
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
