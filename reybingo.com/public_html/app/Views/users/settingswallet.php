<div class="modal-dialog modal-dialog-centered max-w-40">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2"><i class="fa-duotone fa-solid fa-wallet"></i> <?= translate('bank account'); ?></h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body pt-0">
            <?php echo form_open(site_url() . 'payments/settingswalletSubmit', array('enctype' => 'multipart/form-data', 'id' => 'settingswallet-form'));?>
                
                <?= csrf_field() ?>
                
                <div class="row">
                    <div class="col-md-12 mb-1">
                        <label for="setting-bank" class="form-label"><?= translate('bank'); ?></label>
                        <select class='form-control form-control-lg form-bingo' name="setting-bank" id="setting-bank">
                            <option value=""><?= translate('select a'); ?> <?= strtolower(translate('bank')); ?></option>
                            <!-- BANCOS -->
                            <option <?= $user['bank'] == 'BANCO PICHINCHA' ? 'selected' : '' ?> value="BANCO PICHINCHA">BANCO PICHINCHA</option>
                            <option <?= $user['bank'] == 'BANCO GUAYAQUIL' ? 'selected' : '' ?> value="BANCO GUAYAQUIL">BANCO GUAYAQUIL</option>
                            <option <?= $user['bank'] == 'BANCO DEL PACIFICO' ? 'selected' : '' ?> value="BANCO DEL PACIFICO">BANCO DEL PACÍFICO</option>
                            <option <?= $user['bank'] == 'BANCO DEL AUSTRO' ? 'selected' : '' ?> value="BANCO DEL AUSTRO">BANCO DEL AUSTRO</option>

                            <!-- COOPERATIVAS -->
                            <option <?= $user['bank'] == 'COOP. JEP' ? 'selected' : '' ?> value="COOP. JEP">COOPERATIVA JEP</option>
                            <option <?= $user['bank'] == 'COOP. JARDIN AZUAYO' ? 'selected' : '' ?> value="COOP. JARDIN AZUAYO">COOPERATIVA JARDÍN AZUAYO</option>
                            <option <?= $user['bank'] == 'COOP. POLICIA NACIONAL' ? 'selected' : '' ?> value="COOP. POLICIA NACIONAL">COOPERATIVA DE LA POLICÍA NACIONAL</option>
                            <option <?= $user['bank'] == 'COOP. ALIANZA DEL VALLE' ? 'selected' : '' ?> value="COOP. ALIANZA DEL VALLE">COOPERATIVA ALIANZA DEL VALLE</option>
                            <option <?= $user['bank'] == 'COOP. COOPERCO' ? 'selected' : '' ?> value="COOP. COOPERCO">COOPERATIVA COOPERCO</option>
                            <option <?= $user['bank'] == 'COOP. MUSHUC RUNA' ? 'selected' : '' ?> value="COOP. MUSHUC RUNA">COOPERATIVA MUSHUC RUNA</option>
                        </select>
                        <small id="setting-bank-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-12 mb-1">
                        <label for="setting-account" class="form-label"><?= translate('naccount'); ?></label>
                        <input type="number" class="form-control form-control-lg form-bingo" name="setting-account" id="setting-account" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('naccount')); ?>" autocomplete="off" value="<?= $user['account']; ?>">
                        <small id="setting-account-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-12 mb-1">
                        <label for="setting-account-type" class="form-label"><?= translate('account type'); ?></label>
                        <?php $accountType = bingo_normalize_account_type($user['account_type'] ?? ''); ?>
                        <select class="form-control form-control-lg form-bingo" name="setting-account-type" id="setting-account-type">
                            <option value=""><?= translate('select a'); ?> <?= strtolower(translate('account type')); ?></option>
                            <option value="savings" <?= $accountType === 'savings' ? 'selected' : ''; ?>><?= translate('savings account'); ?></option>
                            <option value="checking" <?= $accountType === 'checking' ? 'selected' : ''; ?>><?= translate('checking account'); ?></option>
                        </select>
                        <small id="setting-account-type-error" class="text-danger d-none"></small>
                    </div>
                    
                    <div class="col-md-6 mb-1">
                        <label for="setting-document" class="form-label"><?= translate('document'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="setting-document" id="setting-document" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('document')); ?>" autocomplete="off" value="<?= $user['document']; ?>">
                        <small id="setting-document-error" class="text-danger d-none"></small>
                    </div>
                    
                    <div class="col-md-6 mb-1">
                        <label for="setting-phone" class="form-label"><?= translate('phone'); ?></label>
                        <input type="number" class="form-control form-control-lg form-bingo" name="setting-phone" id="setting-phone" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('phone')); ?>" autocomplete="off" value="<?= $user['phone']; ?>">
                        <small id="setting-phone-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary d-block w-50 btn-bingo mt-3" id="settingswallet-button"><?= translate('update'); ?></button>
                    </div>
                </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $('#settingswallet-form').on('submit', function (e) {
            e.preventDefault(); 

            var button = $('#settingswallet-button');
            button.prop("disabled", true);

            $('.text-danger').addClass('d-none').text('');
            $('.form-control').removeClass('is-invalid');

            $.ajax({
                url: '<?= site_url('payments/settingswalletSubmit') ?>',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        
                        $('#modalSettings').modal('hide');

                        Toastify({
                            text: "<?= translate('account updated successfully'); ?>",
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            style: { background: "#198754" },
                            stopOnFocus: true
                        }).showToast();
                    } else {
                        if (response.errors) {
                            $.each(response.errors, function(field, message) {
                                $('#' + field + '-error').text(message).removeClass('d-none');
                                $('#' + field).addClass('is-invalid');
                            });
                        }
                    }
                },
                error: function() {
                    Toastify({
                        text: "<?= translate('there was an error in the request to the server'); ?>",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: { background: "#dc3545" },
                        stopOnFocus: true
                    }).showToast();
                },
                complete: function() {
                    button.prop("disabled", false);
                }
            });
        });
    });
</script>