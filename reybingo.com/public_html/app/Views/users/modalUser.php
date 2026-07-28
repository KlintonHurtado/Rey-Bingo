<div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2" id="user-modal-title">
                <i class="fa-duotone fa-solid fa-user"></i> 
                <?= $isUpdate ? translate('update user') : translate('add user'); ?>
            </h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body pt-0">
            <?php echo form_open(site_url() . 'users/userSubmit', array('enctype' => 'multipart/form-data', 'id' => 'user-form'));?>
                <?= csrf_field() ?>
                <input type="hidden" id="user-id" name="user-id" value="<?= $isUpdate ? $userData['id'] : ''; ?>">
                <input type="hidden" id="user-action" name="user-action" value="<?= $isUpdate ? 'update' : 'add'; ?>">
                
                <div class="row">
                    <!-- Información Personal -->
                    <div class="col-md-12 mb-3">
                        <h6 class="text-white"><?= translate('personal information'); ?></h6>
                        <hr class="mt-1 mb-0">
                    </div>
                    
                    <div class="col-md-6 mb-2">
                        <label for="firstname" class="form-label"><?= translate('first name'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="firstname" id="firstname" placeholder="<?= translate('enter first name'); ?>" value="<?= $isUpdate ? esc($userData['firstname']) : ''; ?>" autocomplete="off" autofocus>
                        <small id="firstname-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="lastname" class="form-label"><?= translate('last name'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="lastname" id="lastname" placeholder="<?= translate('enter last name'); ?>" value="<?= $isUpdate ? esc($userData['lastname']) : ''; ?>" autocomplete="off">
                        <small id="lastname-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="username" class="form-label"><?= translate('username'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="username" id="username" placeholder="<?= translate('enter username'); ?>" value="<?= $isUpdate ? esc($userData['username']) : ''; ?>" autocomplete="off">
                        <small id="username-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="email" class="form-label"><?= translate('email'); ?></label>
                        <input type="email" class="form-control form-control-lg form-bingo" name="email" id="email" placeholder="<?= translate('enter email'); ?>" value="<?= $isUpdate ? esc($userData['email']) : ''; ?>" autocomplete="off">
                        <small id="email-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="phone" class="form-label"><?= translate('phone'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="phone" id="phone" placeholder="<?= translate('enter phone'); ?>" value="<?= $isUpdate ? esc($userData['phone']) : ''; ?>" autocomplete="off">
                        <small id="phone-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="document" class="form-label"><?= translate('document'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="document" id="document" placeholder="<?= translate('enter document'); ?>" value="<?= $isUpdate ? esc($userData['document']) : ''; ?>" autocomplete="off">
                        <small id="document-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="document_expires_at" class="form-label"><?= translate('document expiry'); ?></label>
                        <input type="date" class="form-control form-control-lg form-bingo" name="document_expires_at" id="document_expires_at" value="<?= $isUpdate ? esc($userData['document_expires_at'] ?? '') : ''; ?>">
                    </div>

                    <div class="col-md-12 mb-2">
                        <label for="address_line" class="form-label"><?= translate('address'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="address_line" id="address_line" placeholder="<?= translate('address'); ?>" value="<?= $isUpdate ? esc($userData['address_line'] ?? '') : ''; ?>" autocomplete="off">
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="city" class="form-label"><?= translate('city'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="city" id="city" placeholder="<?= translate('city'); ?>" value="<?= $isUpdate ? esc($userData['city'] ?? '') : ''; ?>" autocomplete="off">
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="state" class="form-label"><?= translate('state'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="state" id="state" placeholder="<?= translate('state'); ?>" value="<?= $isUpdate ? esc($userData['state'] ?? '') : ''; ?>" autocomplete="off">
                    </div>

                    <!-- Información de Cuenta -->
                    <div class="col-md-12 mb-3 mt-3">
                        <h6 class="text-white"><?= translate('account information'); ?></h6>
                        <hr class="mt-1 mb-0">
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="password" class="form-label"><?= translate('password'); ?> <?= !$isUpdate ? '<span class="text-danger">*</span>' : '<small class="text-muted">(' . translate('leave empty to keep current') . ')</small>'; ?>
                        </label>
                        <input type="password" class="form-control form-control-lg form-bingo" name="password" id="password" placeholder="<?= translate('enter password'); ?>" autocomplete="new-password">
                        <small id="password-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label for="wallet_bonus" class="form-label"><?= translate('bonus balance'); ?></label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-lg form-bingo" name="wallet_bonus" id="wallet_bonus" placeholder="0.00" value="<?= $isUpdate ? number_format((float) ($userData['wallet_bonus'] ?? 0), 2, '.', '') : '0.00'; ?>">
                        <small id="wallet_bonus-error" class="text-danger d-none"></small>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label for="wallet_recharge" class="form-label"><?= translate('recharge balance'); ?></label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-lg form-bingo" name="wallet_recharge" id="wallet_recharge" placeholder="0.00" value="<?= $isUpdate ? number_format((float) ($userData['wallet_recharge'] ?? 0), 2, '.', '') : '0.00'; ?>">
                        <small id="wallet_recharge-error" class="text-danger d-none"></small>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label for="wallet_withdraw" class="form-label"><?= translate('withdraw balance'); ?></label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-lg form-bingo" name="wallet_withdraw" id="wallet_withdraw" placeholder="0.00" value="<?= $isUpdate ? number_format((float) ($userData['wallet_withdraw'] ?? 0), 2, '.', '') : '0.00'; ?>">
                        <small id="wallet_withdraw-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="group" class="form-label"><?= translate('group'); ?></label>
                        <select class="form-control form-control-lg form-bingo" name="group" id="group">
                            <option value="0" <?= $isUpdate && $userData['group'] == 0 ? 'selected' : ''; ?>><?= translate('player'); ?></option>
                            <option value="1" <?= $isUpdate && $userData['group'] == 1 ? 'selected' : ''; ?>><?= translate('admin'); ?></option>
                            <option value="2" <?= $isUpdate && $userData['group'] == 2 ? 'selected' : ''; ?>><?= translate('point of sale'); ?></option>
                            <option value="3" <?= $isUpdate && $userData['group'] == 3 ? 'selected' : ''; ?>><?= translate('operator'); ?></option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="status" class="form-label"><?= translate('status'); ?></label>
                        <select class="form-control form-control-lg form-bingo" name="status" id="status">
                            <option value="1" <?= $isUpdate && $userData['status'] == 1 ? 'selected' : ''; ?>><?= translate('active'); ?></option>
                            <option value="0" <?= $isUpdate && $userData['status'] == 0 ? 'selected' : ''; ?>><?= translate('banned'); ?></option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="is_reseller" class="form-label"><?= translate('point of sale'); ?></label>
                        <select class="form-control form-control-lg form-bingo" name="is_reseller" id="is_reseller">
                            <option value="0" <?= $isUpdate && (int) ($userData['is_reseller'] ?? 0) === 0 ? 'selected' : ''; ?>><?= translate('no'); ?></option>
                            <option value="1" <?= $isUpdate && (int) ($userData['is_reseller'] ?? 0) === 1 ? 'selected' : ''; ?>><?= translate('yes'); ?></option>
                        </select>
                    </div>

                    <!-- Información Bancaria -->
                    <div class="col-md-12 mb-3 mt-3">
                        <h6 class="text-white"><?= translate('banking information'); ?></h6>
                        <hr class="mt-1 mb-0">
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="bank" class="form-label"><?= translate('bank'); ?></label>
                        <select class='form-control form-control-lg form-bingo' name="bank" id="bank">
                            <option value=""><?= translate('select a'); ?> <?= strtolower(translate('bank')); ?></option>
                            <!-- BANCOS -->
                            <option <?= $isUpdate && $userData['bank'] == 'BANCO PICHINCHA' ? 'selected' : '' ?> value="BANCO PICHINCHA">BANCO PICHINCHA</option>
                            <option <?= $isUpdate && $userData['bank'] == 'BANCO GUAYAQUIL' ? 'selected' : '' ?> value="BANCO GUAYAQUIL">BANCO GUAYAQUIL</option>
                            <option <?= $isUpdate && $userData['bank'] == 'BANCO DEL PACIFICO' ? 'selected' : '' ?> value="BANCO DEL PACIFICO">BANCO DEL PACÍFICO</option>
                            <option <?= $isUpdate && $userData['bank'] == 'BANCO DEL AUSTRO' ? 'selected' : '' ?> value="BANCO DEL AUSTRO">BANCO DEL AUSTRO</option>

                            <!-- COOPERATIVAS -->
                            <option <?= $isUpdate && $userData['bank'] == 'COOP. JEP' ? 'selected' : '' ?> value="COOP. JEP">COOPERATIVA JEP</option>
                            <option <?= $isUpdate && $userData['bank'] == 'COOP. JARDIN AZUAYO' ? 'selected' : '' ?> value="COOP. JARDIN AZUAYO">COOPERATIVA JARDÍN AZUAYO</option>
                            <option <?= $isUpdate && $userData['bank'] == 'COOP. POLICIA NACIONAL' ? 'selected' : '' ?> value="COOP. POLICIA NACIONAL">COOPERATIVA DE LA POLICÍA NACIONAL</option>
                            <option <?= $isUpdate && $userData['bank'] == 'COOP. ALIANZA DEL VALLE' ? 'selected' : '' ?> value="COOP. ALIANZA DEL VALLE">COOPERATIVA ALIANZA DEL VALLE</option>
                            <option <?= $isUpdate && $userData['bank'] == 'COOP. COOPERCO' ? 'selected' : '' ?> value="COOP. COOPERCO">COOPERATIVA COOPERCO</option>
                            <option <?= $isUpdate && $userData['bank'] == 'COOP. MUSHUC RUNA' ? 'selected' : '' ?> value="COOP. MUSHUC RUNA">COOPERATIVA MUSHUC RUNA</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="account" class="form-label"><?= translate('account'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="account" id="account" placeholder="<?= translate('enter account'); ?>" value="<?= $isUpdate ? esc($userData['account']) : ''; ?>" autocomplete="off">
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="account_type" class="form-label"><?= translate('account type'); ?></label>
                        <?php $accountType = bingo_normalize_account_type($isUpdate ? ($userData['account_type'] ?? '') : ''); ?>
                        <select class="form-control form-control-lg form-bingo" name="account_type" id="account_type">
                            <option value=""><?= translate('select a'); ?> <?= strtolower(translate('account type')); ?></option>
                            <option value="savings" <?= $accountType === 'savings' ? 'selected' : ''; ?>><?= translate('savings account'); ?></option>
                            <option value="checking" <?= $accountType === 'checking' ? 'selected' : ''; ?>><?= translate('checking account'); ?></option>
                        </select>
                    </div>

                    <!-- Configuraciones -->
                    <div class="col-md-12 mb-3 mt-3">
                        <h6 class="text-white"><?= translate('settings'); ?></h6>
                        <hr class="mt-1 mb-0">
                    </div>

                    <div class="col-md-3 mb-2">
                        <label for="sounds" class="form-label"><?= translate('sounds'); ?></label>
                        <select class="form-control form-control-lg form-bingo" name="sounds" id="sounds">
                            <option value="1" <?= $isUpdate && $userData['sounds'] == 1 ? 'selected' : ''; ?>><?= translate('enabled'); ?></option>
                            <option value="0" <?= $isUpdate && $userData['sounds'] == 0 ? 'selected' : ''; ?>><?= translate('disabled'); ?></option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-2">
                        <label for="narration" class="form-label"><?= translate('narration'); ?></label>
                        <select class="form-control form-control-lg form-bingo" name="narration" id="narration">
                            <option value="1" <?= $isUpdate && $userData['narration'] == 1 ? 'selected' : ''; ?>><?= translate('enabled'); ?></option>
                            <option value="0" <?= $isUpdate && $userData['narration'] == 0 ? 'selected' : ''; ?>><?= translate('disabled'); ?></option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-2">
                        <label for="autodial" class="form-label"><?= translate('autodial'); ?></label>
                        <select class="form-control form-control-lg form-bingo" name="autodial" id="autodial">
                            <option value="1" <?= $isUpdate && $userData['autodial'] == 1 ? 'selected' : ''; ?>><?= translate('enabled'); ?></option>
                            <option value="0" <?= $isUpdate && $userData['autodial'] == 0 ? 'selected' : ''; ?>><?= translate('disabled'); ?></option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-2">
                        <label for="roulette" class="form-label"><?= translate('roulette'); ?></label>
                        <select class="form-control form-control-lg form-bingo" name="roulette" id="roulette">
                            <option value="1" <?= $isUpdate && $userData['roulette'] == 1 ? 'selected' : ''; ?>><?= translate('rotated'); ?></option>
                            <option value="0" <?= $isUpdate && $userData['roulette'] == 0 ? 'selected' : ''; ?>><?= translate('not rotated'); ?></option>
                        </select>
                    </div>

                    <!-- Botón de envío -->
                    <div class="col-md-12 d-flex justify-content-center mt-4">
                        <button type="submit" class="btn btn-primary btn-bingo w-50" id="submit-button">
                            <?= $isUpdate ? translate('update') : translate('add'); ?>
                        </button>
                    </div>
                </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#user-form').on('submit', function(e) {
            e.preventDefault();
            
            var button = $('#submit-button');
            button.prop("disabled", true);
            
            // Limpiar errores previos
            $('.text-danger').addClass('d-none').text('');
            $('.form-control').removeClass('is-invalid');
            
            $.ajax({
                url: '<?= site_url('users/userSubmit') ?>',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#modalUser').modal('hide');
                        
                        // Recargar la página para mostrar los cambios
                        statisticsGet('players');
                        
                        Toastify({
                            text: response.message,
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
                        
                        if (response.message) {
                            Toastify({
                                text: response.message,
                                duration: 3000,
                                gravity: "top",
                                position: "right",
                                style: { background: "#dc3545" },
                                stopOnFocus: true
                            }).showToast();
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
