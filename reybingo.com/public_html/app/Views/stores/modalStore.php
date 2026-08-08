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

                    <div class="col-md-12 mb-2">
                        <label for="email" class="form-label"><?= translate('email'); ?></label>
                        <input type="email" class="form-control form-control-lg form-bingo" name="email" id="email" value="<?= $isUpdate ? esc($storeData['email']) : '' ?>" autocomplete="off">
                        <small id="email-error" class="text-danger d-none"></small>
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
