<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable max-w-40">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2" id="operator-store-modal-title">
                <i class="fa-duotone fa-solid fa-store"></i>
                <?= translate('create point of sale'); ?>
            </h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body pt-0">
            <?php echo form_open(site_url('operator/storeSubmit'), ['id' => 'operator-store-form']); ?>
                <?= csrf_field() ?>

                <div class="row">
                    <div class="col-md-12 mb-2">
                        <label for="operator-store-business_name" class="form-label"><?= translate('business name'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="business_name" id="operator-store-business_name" autocomplete="off" autofocus>
                        <small id="business_name-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-12 mb-2">
                        <label for="operator-store-phone" class="form-label"><?= translate('phone'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="phone" id="operator-store-phone" placeholder="<?= translate('phone'); ?>" autocomplete="off">
                        <small id="phone-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-12 mb-2">
                        <label for="operator-store-address_line" class="form-label"><?= translate('address'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="address_line" id="operator-store-address_line" placeholder="<?= translate('address'); ?>" autocomplete="off">
                        <small id="address_line-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-12 mb-2">
                        <label for="operator-store-password" class="form-label">
                            <?= translate('password'); ?> <span class="text-danger">*</span>
                        </label>
                        <input type="password" class="form-control form-control-lg form-bingo" name="password" id="operator-store-password" autocomplete="new-password">
                        <small id="password-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-12 d-flex justify-content-center mt-3">
                        <button type="submit" class="btn btn-primary btn-bingo w-50" id="operator-store-submit-button">
                            <?= translate('create'); ?>
                        </button>
                    </div>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $('#operator-store-form').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#operator-store-submit-button');
        $btn.prop('disabled', true);
        $('#operator-store-form .text-danger').addClass('d-none').text('');
        $('#operator-store-form .form-control').removeClass('is-invalid');

        $.ajax({
            url: '<?= site_url('operator/storeSubmit') ?>',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#modalOperatorStore').modal('hide');
                    if (typeof Toastify !== 'undefined') {
                        Toastify({
                            text: response.message || '<?= esc(translate('store added successfully'), 'js'); ?>',
                            duration: 3000,
                            gravity: 'top',
                            position: 'right',
                            style: { background: '#198754' }
                        }).showToast();
                    }
                    setTimeout(function() {
                        window.location.reload();
                    }, 800);
                    return;
                }

                if (response.errors) {
                    $.each(response.errors, function(field, message) {
                        $('#' + field + '-error').text(message).removeClass('d-none');
                        $('#operator-store-form [name="' + field + '"]').addClass('is-invalid');
                    });
                } else if (response.message) {
                    alert(response.message);
                }
            },
            error: function() {
                alert('<?= esc(translate('there was an error in the request to the server.'), 'js'); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
</script>
