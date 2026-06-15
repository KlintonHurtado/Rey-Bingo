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

                    <div class="col-md-6 mb-2">
                        <label for="wallet" class="form-label"><?= translate('wallet'); ?></label>
                        <input type="number" step="0.01" class="form-control form-control-lg form-bingo" name="wallet" id="wallet" placeholder="0.00" value="<?= $isUpdate ? number_format($userData['wallet'], 2, '.', '') : '0.00'; ?>">
                        <small id="wallet-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="group" class="form-label"><?= translate('group'); ?></label>
                        <select class="form-control form-control-lg form-bingo" name="group" id="group">
                            <option value="0" <?= $isUpdate && $userData['group'] == 0 ? 'selected' : ''; ?>><?= translate('player'); ?></option>
                            <option value="1" <?= $isUpdate && $userData['group'] == 1 ? 'selected' : ''; ?>><?= translate('admin'); ?></option>
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
                        <label for="is_reseller" class="form-label">Punto de venta / afiliado</label>
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
                            <option <?= $isUpdate && $userData['bank'] == '0001 - BANCO CENTRAL DE VENEZUELA' ? 'selected' : '' ?> value="0001 - BANCO CENTRAL DE VENEZUELA">0001 - BANCO CENTRAL DE VENEZUELA</option>
                            <option <?= $isUpdate && $userData['bank'] == '0102 - BANCO DE VENEZUELA' ? 'selected' : '' ?> value="0102 - BANCO DE VENEZUELA">0102 - BANCO DE VENEZUELA</option>
                            <option <?= $isUpdate && $userData['bank'] == '0104 - VENEZOLANO DE CRÉDITO' ? 'selected' : '' ?> value="0104 - VENEZOLANO DE CRÉDITO">0104 - VENEZOLANO DE CRÉDITO</option>
                            <option <?= $isUpdate && $userData['bank'] == '0105 - BANCO MERCANTIL' ? 'selected' : '' ?> value="0105 - BANCO MERCANTIL">0105 - BANCO MERCANTIL</option>
                            <option <?= $isUpdate && $userData['bank'] == '0108 - BANCO PROVINCIAL' ? 'selected' : '' ?> value="0108 - BANCO PROVINCIAL">0108 - BANCO PROVINCIAL</option>
                            <option <?= $isUpdate && $userData['bank'] == '0114 - BANCO DEL CARIBE' ? 'selected' : '' ?> value="0114 - BANCO DEL CARIBE">0114 - BANCO DEL CARIBE</option>
                            <option <?= $isUpdate && $userData['bank'] == '0115 - BANCO EXTERIOR' ? 'selected' : '' ?> value="0115 - BANCO EXTERIOR">0115 - BANCO EXTERIOR</option>
                            <option <?= $isUpdate && $userData['bank'] == '0116 - BANCO OCCIDENTAL DE DESCUENTO' ? 'selected' : '' ?> value="0116 - BANCO OCCIDENTAL DE DESCUENTO">0116 - BANCO OCCIDENTAL DE DESCUENTO</option>
                            <option <?= $isUpdate && $userData['bank'] == '0128 - BANCO CARONI' ? 'selected' : '' ?> value="0128 - BANCO CARONI">0128 - BANCO CARONI</option>
                            <option <?= $isUpdate && $userData['bank'] == '0134 - BANESCO' ? 'selected' : '' ?> value="0134 - BANESCO">0134 - BANESCO</option>
                            <option <?= $isUpdate && $userData['bank'] == '0137 - BANCO SOFITASA' ? 'selected' : '' ?> value="0137 - BANCO SOFITASA">0137 - BANCO SOFITASA</option>
                            <option <?= $isUpdate && $userData['bank'] == '0138 - BANCO PLAZA' ? 'selected' : '' ?> value="0138 - BANCO PLAZA">0138 - BANCO PLAZA</option>
                            <option <?= $isUpdate && $userData['bank'] == '0146 - BANCO DE LA GENTE EMPRENDEDORA BANGENTE' ? 'selected' : '' ?> value="0146 - BANCO DE LA GENTE EMPRENDEDORA BANGENTE">0146 - BANCO DE LA GENTE EMPRENDEDORA BANGENTE</option>
                            <option <?= $isUpdate && $userData['bank'] == '0149 - BANCO DEL PUEBLO SOBERANO' ? 'selected' : '' ?> value="0149 - BANCO DEL PUEBLO SOBERANO">0149 - BANCO DEL PUEBLO SOBERANO</option>
                            <option <?= $isUpdate && $userData['bank'] == '0151 - BFC BANCO FONDO COMUN C.A.' ? 'selected' : '' ?> value="0151 - BFC BANCO FONDO COMUN C.A.">0151 - BFC BANCO FONDO COMUN C.A.</option>
                            <option <?= $isUpdate && $userData['bank'] == '0156 - 100%BANCO' ? 'selected' : '' ?> value="0156 - 100%BANCO">0156 - 100%BANCO</option>
                            <option <?= $isUpdate && $userData['bank'] == '0157 - DELSUR' ? 'selected' : '' ?> value="0157 - DELSUR">0157 - DELSUR</option>
                            <option <?= $isUpdate && $userData['bank'] == '0163 - BANCO DEL TESORO' ? 'selected' : '' ?> value="0163 - BANCO DEL TESORO">0163 - BANCO DEL TESORO</option>
                            <option <?= $isUpdate && $userData['bank'] == '0164 - BANCO DE DESARROLLO DEL MICROEMPRESARIO' ? 'selected' : '' ?> value="0164 - BANCO DE DESARROLLO DEL MICROEMPRESARIO">0164 - BANCO DE DESARROLLO DEL MICROEMPRESARIO</option>
                            <option <?= $isUpdate && $userData['bank'] == '0166 - BANCO AGRICOLA DE VENEZUELA' ? 'selected' : '' ?> value="0166 - BANCO AGRICOLA DE VENEZUELA">0166 - BANCO AGRICOLA DE VENEZUELA</option>
                            <option <?= $isUpdate && $userData['bank'] == '0168 - BANCRECER' ? 'selected' : '' ?> value="0168 - BANCRECER">0168 - BANCRECER</option>
                            <option <?= $isUpdate && $userData['bank'] == '0169 - MI BANCO' ? 'selected' : '' ?> value="0169 - MI BANCO">0169 - MI BANCO,</option>
                            <option <?= $isUpdate && $userData['bank'] == '0171 - BANCO ACTIVO' ? 'selected' : '' ?> value="0171 - BANCO ACTIVO">0171 - BANCO ACTIVO</option>
                            <option <?= $isUpdate && $userData['bank'] == '0172 - BANCAMIGA' ? 'selected' : '' ?> value="0172 - BANCAMIGA">0172 - BANCAMIGA</option>
                            <option <?= $isUpdate && $userData['bank'] == '0173 - BANCO INTERNACIONAL DE DESARROLLO' ? 'selected' : '' ?> value="0173 - BANCO INTERNACIONAL DE DESARROLLO">0173 - BANCO INTERNACIONAL DE DESARROLLO</option>
                            <option <?= $isUpdate && $userData['bank'] == '0174 - BANPLUS' ? 'selected' : '' ?> value="0174 - BANPLUS">0174 - BANPLUS</option>
                            <option <?= $isUpdate && $userData['bank'] == '0175 - BANCO DIGITAL DE LOS TRABAJADORES' ? 'selected' : '' ?> value="0175 - BANCO DIGITAL DE LOS TRABAJADORES">0175 - BANCO DIGITAL DE LOS TRABAJADORES</option>
                            <option <?= $isUpdate && $userData['bank'] == '0176 - NOVO BANCO' ? 'selected' : '' ?> value="0176 - NOVO BANCO">0176 - NOVO BANCO</option>
                            <option <?= $isUpdate && $userData['bank'] == '0177 - BANCO DE LA FUERZA ARMADA NACIONAL BOLIVARIANA' ? 'selected' : '' ?> value="0177 - BANCO DE LA FUERZA ARMADA NACIONAL BOLIVARIANA">0177 - BANCO DE LA FUERZA ARMADA NACIONAL BOLIVARIANA</option>
                            <option <?= $isUpdate && $userData['bank'] == '0190 - CITIBANK N.A.' ? 'selected' : '' ?> value="0190 - CITIBANK N.A.">0190 - CITIBANK N.A.</option>
                            <option <?= $isUpdate && $userData['bank'] == '0191 - BANCO NACIONAL CRÉDITO' ? 'selected' : '' ?> value="0191 - BANCO NACIONAL CRÉDITO">0191 - BANCO NACIONAL CRÉDITO</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label for="account" class="form-label"><?= translate('account'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="account" id="account" placeholder="<?= translate('enter account'); ?>" value="<?= $isUpdate ? esc($userData['account']) : ''; ?>" autocomplete="off">
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
