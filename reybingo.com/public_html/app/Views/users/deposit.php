<div class="modal-dialog modal-dialog-centered max-w-40">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2"><i class="fa-duotone fa-arrow-down-to-bracket"></i> <?= translate('deposit wallet'); ?></h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body pt-0">
            <?php echo form_open(site_url() . 'payments/depositSubmit', array('enctype' => 'multipart/form-data', 'id' => 'deposit-form'));?>
                
                <?= csrf_field() ?>

                <?php 
                    $paypalCredentials = paypalCredentials();
                    $stripeEnabled = (systemGet('activateStripe') == 1) || (systemGet('secretStripe') != '') || (env('stripe.secretKey') != '');
                ?>
                
                <div class="row" id="deposit-wallet">
                    
                    <h6 class="help-block text-center"><?= translate('send your payment information, amount, reference and phone number'); ?></h6>
                    <p class="text-muted small text-center mb-2">No necesitas verificación KYC para depositar. La verificación de identidad solo se solicita al retirar ganancias.</p>

                    <div class="row" id="step-deposit-1">
                        <?php if (session()->get('group') == 1) : ?>
                            <div class="col-md-12 mb-1">
                                <label for="deposit-user" class="form-label"><?= translate('user'); ?></label>
                                <select class='form-control form-control-lg form-bingo' name="deposit-user" id="deposit-user">
                                    <option value=""><?= translate('user'); ?></option>
                                    <?php foreach ($users as $userOption) : ?>
                                    <option value="<?= $userOption['id']; ?>" <?= ($filters['user_id'] ?? '') == $userOption['id'] ? 'selected' : ''; ?>>
                                        <?= esc($userOption['code'] . ' - ' . $userOption['firstname'] . ' ' . $userOption['lastname']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small id="deposit-user-error" class="text-danger d-none"></small>
                            </div>
                            <div class="col-md-12 mb-2 d-none" id="deposit-user-stats-wrap">
                                <p class="small text-muted mb-2 text-center">Historial del jugador seleccionado</p>
                                <?= view('users/partials/accreditation_user_stats', [
                                    'userStats' => ['manual_credits' => 0, 'user_spend' => 0, 'total_prizes' => 0],
                                    'wrapperClass' => 'user-accreditation-stats deposit-user-stats',
                                ]) ?>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-12 mb-1">
                            <label for="deposit-method" class="form-label"><?= translate('payment method'); ?></label>
                            <select class='form-control form-control-lg form-bingo' name="deposit-method" id="deposit-method" onchange="getButtons();">
                                <option value=""><?= translate('payment method'); ?></option>
                                <?php if (systemGet('activatePayPal') == 1) : ?>
                                <option value="paypal"><?= translate('paypal'); ?></option>
                                <?php endif; ?>
                                <?php if ($stripeEnabled) : ?>
                                <option value="stripe">Stripe</option>
                                <?php endif; ?>
                                <option <?= systemGet('method') == 'transfer' ? 'selected' : '' ?> value="transfer"><?= translate('transfer'); ?></option>
                                <option <?= systemGet('method') == 'mobile payment' ? 'selected' : '' ?> value="mobile payment"><?= translate('mobile payment'); ?></option>
                                <option <?= systemGet('method') == 'deposit' ? 'selected' : '' ?> value="deposit"><?= translate('deposit'); ?></option>
                            </select>
                            <small id="deposit-method-error" class="text-danger d-none"></small>
                        </div>

                        <div class="col-md-12 mb-1" id="deposit-method-bank">
                            <label for="deposit-account" class="form-label"><?= translate('bingo bank'); ?></label>
                            <select class='form-control form-control-lg form-bingo' name="deposit-account" id="deposit-account" onchange="infobankGet();">
                                <option value=""><?= translate('bingo bank'); ?></option>
                                <?php foreach ($banks as $bank): ?>
                                    <option <?= systemGet('bank') == $bank['id'] ? 'selected' : '' ?> value="<?= $bank['id'] ?>"><?= $bank['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small id="deposit-account-error" class="text-danger d-none"></small>
                        </div>

                        <div class="col-md-12 d-flex justify-content-center" id="deposit-info-bank"></div>

                        <div class="col-md-12" id="step-deposit-button">
                            <button type="button" class="btn btn-primary d-block w-50 btn-bingo mt-3" id="deposit-step-button"><?= translate('continue'); ?></button>
                        </div>
                    </div>

                    <div class="row" id="step-deposit-2" style="display: none;">
                        <div class="col-md-12 mb-1">
                            <label for="deposit-bank" class="form-label"><?= translate('home bank'); ?></label>
                            <select class='form-control form-control-lg form-bingo' name="deposit-bank" id="deposit-bank">
                                <option value=""><?= translate('select a'); ?> <?= strtolower(translate('bank')); ?></option>
                                <option <?= ($user['bank'] == '0102 - BANCO DE VENEZUELA' && session()->get('group') != 1) ? 'selected' : '' ?> value="0102 - BANCO DE VENEZUELA">0102 - BANCO DE VENEZUELA</option>
                                <option <?= ($user['bank'] == '0104 - VENEZOLANO DE CRÉDITO' && session()->get('group') != 1) ? 'selected' : '' ?> value="0104 - VENEZOLANO DE CRÉDITO">0104 - VENEZOLANO DE CRÉDITO</option>
                                <option <?= ($user['bank'] == '0105 - BANCO MERCANTIL' && session()->get('group') != 1) ? 'selected' : '' ?> value="0105 - BANCO MERCANTIL">0105 - BANCO MERCANTIL</option>
                                <option <?= ($user['bank'] == '0108 - BANCO PROVINCIAL' && session()->get('group') != 1) ? 'selected' : '' ?> value="0108 - BANCO PROVINCIAL">0108 - BANCO PROVINCIAL</option>
                                <option <?= ($user['bank'] == '0114 - BANCO DEL CARIBE' && session()->get('group') != 1) ? 'selected' : '' ?> value="0114 - BANCO DEL CARIBE">0114 - BANCO DEL CARIBE</option>
                                <option <?= ($user['bank'] == '0115 - BANCO EXTERIOR' && session()->get('group') != 1) ? 'selected' : '' ?> value="0115 - BANCO EXTERIOR">0115 - BANCO EXTERIOR</option>
                                <option <?= ($user['bank'] == '0116 - BANCO OCCIDENTAL DE DESCUENTO' && session()->get('group') != 1) ? 'selected' : '' ?> value="0116 - BANCO OCCIDENTAL DE DESCUENTO">0116 - BANCO OCCIDENTAL DE DESCUENTO</option>
                                <option <?= ($user['bank'] == '0128 - BANCO CARONI' && session()->get('group') != 1) ? 'selected' : '' ?> value="0128 - BANCO CARONI">0128 - BANCO CARONI</option>
                                <option <?= ($user['bank'] == '0134 - BANESCO' && session()->get('group') != 1) ? 'selected' : '' ?> value="0134 - BANESCO">0134 - BANESCO</option>
                                <option <?= ($user['bank'] == '0137 - BANCO SOFITASA' && session()->get('group') != 1) ? 'selected' : '' ?> value="0137 - BANCO SOFITASA">0137 - BANCO SOFITASA</option>
                                <option <?= ($user['bank'] == '0138 - BANCO PLAZA' && session()->get('group') != 1) ? 'selected' : '' ?> value="0138 - BANCO PLAZA">0138 - BANCO PLAZA</option>
                                <option <?= ($user['bank'] == '0146 - BANCO DE LA GENTE EMPRENDEDORA BANGENTE' && session()->get('group') != 1) ? 'selected' : '' ?> value="0146 - BANCO DE LA GENTE EMPRENDEDORA BANGENTE">0146 - BANCO DE LA GENTE EMPRENDEDORA BANGENTE</option>
                                <option <?= ($user['bank'] == '0149 - BANCO DEL PUEBLO SOBERANO' && session()->get('group') != 1) ? 'selected' : '' ?> value="0149 - BANCO DEL PUEBLO SOBERANO">0149 - BANCO DEL PUEBLO SOBERANO</option>
                                <option <?= ($user['bank'] == '0151 - BFC BANCO FONDO COMUN C.A.' && session()->get('group') != 1) ? 'selected' : '' ?> value="0151 - BFC BANCO FONDO COMUN C.A.">0151 - BFC BANCO FONDO COMUN C.A.</option>
                                <option <?= ($user['bank'] == '0156 - 100%BANCO' && session()->get('group') != 1) ? 'selected' : '' ?> value="0156 - 100%BANCO">0156 - 100%BANCO</option>
                                <option <?= ($user['bank'] == '0157 - DELSUR' && session()->get('group') != 1) ? 'selected' : '' ?> value="0157 - DELSUR">0157 - DELSUR</option>
                                <option <?= ($user['bank'] == '0163 - BANCO DEL TESORO' && session()->get('group') != 1) ? 'selected' : '' ?> value="0163 - BANCO DEL TESORO">0163 - BANCO DEL TESORO</option>
                                <option <?= ($user['bank'] == '0164 - BANCO DE DESARROLLO DEL MICROEMPRESARIO' && session()->get('group') != 1) ? 'selected' : '' ?> value="0164 - BANCO DE DESARROLLO DEL MICROEMPRESARIO">0164 - BANCO DE DESARROLLO DEL MICROEMPRESARIO</option>
                                <option <?= ($user['bank'] == '0166 - BANCO AGRICOLA DE VENEZUELA' && session()->get('group') != 1) ? 'selected' : '' ?> value="0166 - BANCO AGRICOLA DE VENEZUELA">0166 - BANCO AGRICOLA DE VENEZUELA</option>
                                <option <?= ($user['bank'] == '0168 - BANCRECER' && session()->get('group') != 1) ? 'selected' : '' ?> value="0168 - BANCRECER">0168 - BANCRECER</option>
                                <option <?= ($user['bank'] == '0169 - R4 BANCO MICROFINANCIERO' && session()->get('group') != 1) ? 'selected' : '' ?> value="0169 - MI BANCO">0169 - R4 BANCO MICROFINANCIERO</option>
                                <option <?= ($user['bank'] == '0171 - BANCO ACTIVO' && session()->get('group') != 1) ? 'selected' : '' ?> value="0171 - BANCO ACTIVO">0171 - BANCO ACTIVO</option>
                                <option <?= ($user['bank'] == '0172 - BANCAMIGA' && session()->get('group') != 1) ? 'selected' : '' ?> value="0172 - BANCAMIGA">0172 - BANCAMIGA</option>
                                <option <?= ($user['bank'] == '0173 - BANCO INTERNACIONAL DE DESARROLLO' && session()->get('group') != 1) ? 'selected' : '' ?> value="0173 - BANCO INTERNACIONAL DE DESARROLLO">0173 - BANCO INTERNACIONAL DE DESARROLLO</option>
                                <option <?= ($user['bank'] == '0174 - BANPLUS' && session()->get('group') != 1) ? 'selected' : '' ?> value="0174 - BANPLUS">0174 - BANPLUS</option>
                                <option <?= ($user['bank'] == '0175 - BANCO DIGITAL DE LOS TRABAJADORES' && session()->get('group') != 1) ? 'selected' : '' ?> value="0175 - BANCO DIGITAL DE LOS TRABAJADORES">0175 - BANCO DIGITAL DE LOS TRABAJADORES</option>
                                <option <?= ($user['bank'] == '0176 - NOVO BANCO' && session()->get('group') != 1) ? 'selected' : '' ?> value="0176 - NOVO BANCO">0176 - NOVO BANCO</option>
                                <option <?= ($user['bank'] == '0177 - BANCO DE LA FUERZA ARMADA NACIONAL BOLIVARIANA' && session()->get('group') != 1) ? 'selected' : '' ?> value="0177 - BANCO DE LA FUERZA ARMADA NACIONAL BOLIVARIANA">0177 - BANCO DE LA FUERZA ARMADA NACIONAL BOLIVARIANA</option>
                                <option <?= ($user['bank'] == '0190 - CITIBANK N.A.' && session()->get('group') != 1) ? 'selected' : '' ?> value="0190 - CITIBANK N.A.">0190 - CITIBANK N.A.</option>
                                <option <?= ($user['bank'] == '0191 - BANCO NACIONAL CRÉDITO' && session()->get('group') != 1) ? 'selected' : '' ?> value="0191 - BANCO NACIONAL CRÉDITO">0191 - BANCO NACIONAL CRÉDITO</option>
                            </select>
                            <small id="deposit-bank-error" class="text-danger d-none"></small>
                        </div>
                        
                        <div class="col-md-6 mb-1">
                            <label for="deposit-document" class="form-label"><?= translate('document'); ?></label>
                            <input type="text" class="form-control form-control-lg form-bingo" name="deposit-document" id="deposit-document" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('document')); ?>" autocomplete="off" value="<?= $user['document']; ?>">
                            <small id="deposit-document-error" class="text-danger d-none"></small>
                        </div>
                        
                        <div class="col-md-6 mb-1">
                            <label for="deposit-phone" class="form-label"><?= translate('phone'); ?></label>
                            <input type="number" class="form-control form-control-lg form-bingo" name="deposit-phone" id="deposit-phone" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('phone')); ?>" autocomplete="off" value="<?= $user['phone']; ?>">
                            <small id="deposit-phone-error" class="text-danger d-none"></small>
                        </div>

                        <div class="col-md-6 mb-1">
                            <label for="deposit-date" class="form-label"><?= translate('date'); ?></label>
                            <input type="date" class="form-control form-control-lg form-bingo" name="deposit-date" id="deposit-date" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('date')); ?>" autofocus autocomplete="off" value="<?php echo date('Y-m-d'); ?>">
                            <small id="deposit-date-error" class="text-danger d-none"></small>
                        </div>

                        <div class="col-md-6 mb-1">
                            <label for="deposit-amount" class="form-label"><?= translate('amount'); ?></label>
                            <input type="number" class="form-control form-control-lg form-bingo" name="deposit-amount" id="deposit-amount" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('amount')); ?>" autocomplete="off" value="0.00">
                            <small id="deposit-amount-error" class="text-danger d-none"></small>
                        </div>
                        
                        <div class="col-md-12 mb-1 position-relative">
                            <label for="deposit-reference" class="form-label"><?= translate('reference'); ?></label>
                            <input type="text" class="form-control form-control-lg form-bingo pe-5" name="deposit-reference" id="deposit-reference" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('reference')); ?>" autocomplete="off">
                            <span class="position-absolute top-50 mt-2 end-0 translate-middle-y me-5 text-primary cursor-pointer" style="font-size: 0.9rem;" onclick="pasteReference()"><?= translate('paste'); ?></span>
                            <small id="deposit-reference-error" class="text-danger d-none"></small>
                        </div>

                        <div class="col-md-12 mb-1">
                            <div class="col-md-12 mb-1 text-center">
                                <label for="voucherfileInput" class="form-label ps-0"><?= translate('voucher'); ?></label>
                                
                                <div class="cover position-relative d-inline-block">
                                    <!-- Imagen -->
                                    <img id="voucherImage" src="<?= site_url('uploads/vouchers/image.jpg'); ?>" alt="voucher" class="img-fluid img-thumbnail mx-auto d-block">

                                    <!-- Botón Editar -->
                                    <label for="voucherfileInput" class="btn btn-sm btn-primary position-absolute top-0 end-0 m-2 img-button"><i class="fa-duotone fa-solid fa-plus"></i></label>
                                    <input type="file" id="voucherfileInput" accept="image/*" class="d-none" onchange="previewvoucherImage(event)">

                                    <!-- Botón Eliminar (oculto si no hay imagen) -->
                                    <button type="button" id="removeVoucherBtn" class="btn btn-sm btn-primary position-absolute bottom-0 end-0 m-2 img-button d-none" onclick="removeVoucherImage()"><i class="fa-duotone fa-trash"></i></button>

                                    <input type="hidden" id="voucher_image_input" name="deposit-voucher">
                                </div>
                            </div>
                        </div>

                        <?php if (session()->get('group') == 1) : ?>
                            <div class="col-md-12 mb-1">
                                <label for="observation" class="form-label"><?= translate('observation'); ?></label>
                                <textarea class="form-control form-control-lg form-bingo" name="observation" id="observation" rows="2" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('observation')); ?>"></textarea>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary d-block w-50 btn-bingo mt-2" id="deposit-button"><?= translate('send'); ?></button>
                        </div>
                    </div>

                    <div class="row" id="deposit-paypal-amount" style="display: none;">
                        <div class="col-md-12 mb-1">
                            <label for="deposit-amount" class="form-label"><?= translate('amount'); ?></label>
                            <input type="number" class="form-control form-control-lg form-bingo" name="paypal-amount" id="paypal-amount" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('amount')); ?>" autocomplete="off" min="1" step="0.01">
                            <small id="deposit-amount-error" class="text-danger d-none"></small>
                        </div>
                    </div>

                    <div class="pt-2 text-center" id="paypal-button" style="display: none;"></div>
                    <div class="row" id="deposit-stripe-amount" style="display: none;">
                        <div class="col-md-12 mb-1">
                            <label for="stripe-amount" class="form-label"><?= translate('amount'); ?></label>
                            <input type="number" class="form-control form-control-lg form-bingo" name="stripe-amount" id="stripe-amount" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('amount')); ?>" autocomplete="off" min="1" step="0.01">
                            <small id="stripe-amount-error" class="text-danger d-none"></small>
                        </div>
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary d-block w-50 btn-bingo mt-2" id="stripe-button">Pagar con Stripe</button>
                        </div>
                    </div>
                </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    function getButtons() {
        var method = $("#deposit-method").val();

        if (method == "paypal") {
            $("#paypal-button").show();
            $("#deposit-paypal-amount").show();
            $("#deposit-stripe-amount").hide();
            $("#deposit-method-bank").hide();
            $("#deposit-info-bank").hide();
            $("#step-deposit-button").hide();
        } else if (method == "stripe") {
            $("#paypal-button").hide();
            $("#deposit-paypal-amount").hide();
            $("#deposit-stripe-amount").show();
            $("#deposit-method-bank").hide();
            $("#deposit-info-bank").hide();
            $("#step-deposit-button").hide();
        } else {
            $("#paypal-button").hide();
            $("#deposit-paypal-amount").hide();
            $("#deposit-stripe-amount").hide();
            $("#deposit-method-bank").show();
            $("#deposit-info-bank").show();
            $("#step-deposit-button").show();
        }
    };

    $(document).ready(function () {
        infobankGet();

        <?php if (session()->get('group') == 1) : ?>
        function loadDepositUserStats(userId) {
            const wrap = document.getElementById('deposit-user-stats-wrap');
            if (!wrap) {
                return;
            }

            if (!userId) {
                wrap.classList.add('d-none');
                return;
            }

            fetch(`<?= site_url('payments/userAccreditationStatsGet') ?>/${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success || !data.stats) {
                        wrap.classList.add('d-none');
                        return;
                    }

                    wrap.querySelector('.stat-manual-credits').textContent = formatNumber(data.stats.manual_credits || 0);
                    wrap.querySelector('.stat-user-spend').textContent = formatNumber(data.stats.user_spend || 0);
                    wrap.querySelector('.stat-total-prizes').textContent = formatNumber(data.stats.total_prizes || 0);
                    wrap.classList.remove('d-none');
                })
                .catch(() => wrap.classList.add('d-none'));
        }

        $('#deposit-user').on('change', function() {
            loadDepositUserStats(this.value);
        });

        if ($('#deposit-user').val()) {
            loadDepositUserStats($('#deposit-user').val());
        }
        <?php endif; ?>

        $('#deposit-step-button').on('click', function() {

            var button = $('#deposit-step-button');
            button.prop("disabled", true); 
    
            $('.text-danger').addClass('d-none').text('');
            $('.form-control').removeClass('is-invalid');

            var formData = {
                account: $('#deposit-account').val(),
                method: $('#deposit-method').val()
            };

            $.ajax({
                url: '<?= site_url('payments/depositStepSubmit') ?>',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (response.paypal) {
                            $("#paypal-button").show();
                            $("#deposit-stripe-amount").hide();
                            $("#deposit-method-bank").hide();
                            $("#deposit-info-bank").hide();
                            $("#step-deposit-button").hide();
                        } else {
                            $("#paypal-button").hide();
                            $("#deposit-method-bank").show();
                            $("#deposit-info-bank").show();
                            $("#step-deposit-button").show();
                            $('#step-deposit-1').hide();
                            $('#step-deposit-2').show();
                        }
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

        $('#deposit-form').on('submit', function (e) {
            e.preventDefault();

            var button = $('#deposit-button');
            button.prop("disabled", true);

            $('.text-danger').addClass('d-none').text('');
            $('.form-control').removeClass('is-invalid');

            $.ajax({
                url: '<?= site_url('payments/depositSubmit') ?>',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        
                        $('#modalDeposit').modal('hide');

                        if (response.newRecharge) {
                            updateTableDeposit(response.newRecharge);
                        }

                        Toastify({
                            text: "<?= translate('deposit sent successfully'); ?> Pendiente de aprobación por un administrador.",
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            style: { background: "#198754" },
                            stopOnFocus: true
                        }).showToast();
                    } else if (response.minMax) {
                        Toastify({
                            text: response.message,
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            style: { background: "#dc3545" },
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

        $('#stripe-button').on('click', function() {
            const button = $('#stripe-button');
            const amount = parseFloat($('#stripe-amount').val() || '0');
            $('#stripe-amount-error').addClass('d-none').text('');

            if (!amount || amount <= 0) {
                $('#stripe-amount-error').removeClass('d-none').text("<?= translate('the amount must be greater than 0'); ?>");
                return;
            }

            button.prop('disabled', true);
            $.ajax({
                url: '<?= site_url('payments/stripe/create') ?>',
                method: 'POST',
                dataType: 'json',
                data: {
                    amount: amount,
                    <?= csrf_token(); ?>: '<?= csrf_hash(); ?>'
                },
                success: function(response) {
                    if (response.success && response.url) {
                        window.location.href = response.url;
                        return;
                    }

                    Toastify({
                        text: response.message || "<?= translate('error processing payment'); ?>",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: { background: "#dc3545" },
                        stopOnFocus: true
                    }).showToast();
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || "<?= translate('there was an error in the request to the server'); ?>";
                    Toastify({
                        text: message,
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: { background: "#dc3545" },
                        stopOnFocus: true
                    }).showToast();
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        });
  
        paypal.Button.render({
            env: '<?= esc($paypalCredentials['env'], 'js') ?>',
            style: {
                label: 'paypal',
                size: 'medium',
                shape: 'pill',
                color: 'blue',
                tagline: false,
                fundingicons: false,
                layout: 'vertical'
            },

            funding: {
                allowed: [paypal.FUNDING.CARD, paypal.FUNDING.CREDIT],
                disallowed: []
            },

            client: {
                sandbox: '<?= esc($paypalCredentials['sandbox_client_id'], 'js') ?>',
                production: '<?= esc($paypalCredentials['production_client_id'], 'js') ?>'
            },

            commit: true,

            onError: function(err) {
                console.error('PayPal error:', err);
                Toastify({
                    text: "No se pudo abrir PayPal. Verifica que el Client ID coincida con el modo <?= esc($paypalCredentials['env'], 'js') ?>.",
                    duration: 4000,
                    gravity: "top",
                    position: "right",
                    style: { background: "#dc3545" },
                    stopOnFocus: true
                }).showToast();
            },

            onCancel: function() {
                Toastify({
                    text: "Pago cancelado en PayPal.",
                    duration: 2500,
                    gravity: "top",
                    position: "right",
                    style: { background: "#6c757d" },
                    stopOnFocus: true
                }).showToast();
            },

            payment: function (data, actions) {
                var amountPaypal = parseFloat($("#paypal-amount").val()) || 0;

                if (amountPaypal <= 0) {
                    Toastify({
                        text: "<?= translate('the amount must be greater than 0'); ?>",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: { background: "#dc3545" },
                        stopOnFocus: true
                    }).showToast();

                    return actions.reject();
                }

                return actions.payment.create({
                    transactions: [
                        {
                            amount: { total: amountPaypal.toFixed(2), currency: 'USD' },
                            description: "<?= translate('PAYMENTS BINGO CARDS'); ?>"
                        }
                    ]
                });
            },

            onAuthorize: function (data, actions) {
                return actions.payment.execute().then(function () {
                    var paymentID = data.paymentID;
                    var paymentToken = data.paymentToken;
                    var payerID = data.payerID;

                    $.ajax({
                        url: '<?= site_url('payments/depositPaypalSubmit') ?>',
                        type: 'POST',
                        data: {
                            amount: parseFloat($("#paypal-amount").val()).toFixed(2),
                            paymentID: paymentID,
                            paymentToken: paymentToken,
                            payerID: payerID
                        },
                        dataType: 'json',
                        success: function (response) {
                            if (response.success) {
                                $('#modalDeposit').modal('hide');

                                if (response.newRecharge) {
                                    $('#not-list').remove();

                                    let newRow = `
                                        <tr>
                                            <td>${response.newRecharge.type}</td>
                                            <td>${response.newRecharge.bank}</td>
                                            <td>${response.newRecharge.reference}</td>
                                            <td>${new Date(response.newRecharge.date).toLocaleDateString('es-VE')}</td>
                                            <td><?= systemGet('currency'); ?> ${parseFloat(response.newRecharge.amount).toFixed(2)}</td>
                                            <td><span class="badge bg-secondary"><?= translate('EARRING'); ?></span></td>
                                        </tr>`;
                                    $('#payments-list').append(newRow);
                                }

                                Toastify({
                                    text: "<?= translate('deposit by paypal sent successfully'); ?>",
                                    duration: 3000,
                                    gravity: "top",
                                    position: "right",
                                    style: { background: "#198754" },
                                    stopOnFocus: true
                                }).showToast();
                            } else {
                                Toastify({
                                    text: "<?= translate('error sending payment'); ?>",
                                    duration: 3000,
                                    gravity: "top",
                                    position: "right",
                                    style: { background: "#dc3545" },
                                    stopOnFocus: true
                                }).showToast();
                            }
                        },
                        error: function () {
                            Toastify({
                                text: "<?= translate('there was an error in the request to the server'); ?>",
                                duration: 3000,
                                gravity: "top",
                                position: "right",
                                style: { background: "#dc3545" },
                                stopOnFocus: true
                            }).showToast();
                        }
                    });
                });
            }

        }, '#paypal-button');
    });

    function updateTableDeposit(payment) {
        const tbody = $('#payments-tbody');
        
        $('#not-list').remove();
        
        const typeIcons = {
            'deposit': '<i class="fa-duotone fa-solid fa-arrow-down-to-line text-success"></i>',
            'retire': '<i class="fa-duotone fa-solid fa-arrow-up-from-bracket icon-danger"></i>',
            'transfer': '<i class="fa-duotone fa-solid fa-arrow-right-arrow-left text-info"></i>',
            'payment': '<i class="fa-duotone fa-solid fa-credit-card text-primary"></i>'
        };

        const typeIcon = typeIcons[payment.type] || '<i class="fa-duotone fa-solid fa-circle-question text-warning"></i>';
        const amountClass = payment.type === 'retire' ? 'icon-danger' : 'text-success';
        const amountSign = payment.type === 'retire' ? '-' : '+';

        let row = `
            <tr data-id="${payment.id}" data-type="${payment.type}">
                <td class="text-center">
                    ${typeIcon}
                    <br>
                    <small class="text-muted">${payment.type_Tra}</small>
                </td>
                <td>
                    <strong>${escapeHtml(payment.reference)}</strong>
                    <br>
                    <small class="text-muted">${payment.date_formatted}</small>
                </td>
        `;

        <?php if (session()->get('group') == 1) : ?>
        row += `
                <td>
                    <strong>${escapeHtml(payment.user_code)}</strong>
                    <br>
                    <small class="text-muted">${escapeHtml(payment.user_name)}</small>
                </td>
        `;
        <?php endif; ?>

        row += `<td>${payment.bank}</td>`;

        // Manejar el monto según el tipo de transacción
        let amountHtml = '';
        if (payment.type === 'retire') {
            amountHtml = `
                <strong class="icon-danger">
                    -<?= systemGet('currency'); ?> ${formatNumber(payment.amount)}
                </strong>
            `;
        } else if (payment.type === 'transfer') {
            amountHtml = `
                <div>
                    <strong class="icon-danger d-block">
                        -<?= systemGet('currency'); ?> ${formatNumber(payment.amount)}
                    </strong>
                    <strong class="text-success d-block">
                        +<?= systemGet('currency'); ?> ${formatNumber(payment.amount)}
                    </strong>
                </div>
            `;
        } else {
            // Depósito u otro tipo de ingreso
            amountHtml = `
                <strong class="text-success">
                    +<?= systemGet('currency'); ?> ${formatNumber(payment.amount)}
                </strong>
            `;
        }

        row += `
            <td class="text-center">
                ${amountHtml}
            </td>
            <td class="text-center">
                <small>${payment.created_at}</small>
            </td>
            <td class="text-center" id="${payment.type}-${payment.id}">
                <span class="status-badge" data-status="${payment.status_raw}">
                    ${payment.status_formatted}
                </span>
            </td>
        `;

        <?php if (session()->get('group') == 1) : ?>
        row += `
            <td class="text-center">
                <a class="btn btn-primary btn-modal text-white" onclick="requestGet('${payment.type}', '${payment.id}')">
                    <i class="fa-duotone fa-solid fa-eye"></i>
                </a>
            </td>
        `;
        <?php endif; ?>

        row += `</tr>`;

        tbody.prepend(row);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatNumber(num) {
        return parseFloat(num).toFixed(2);
    }

    function infobankGet() {
        
        const bankId = document.getElementById('deposit-account').value;

        const infoBankDiv = document.getElementById('deposit-info-bank');
        
        infoBankDiv.style.display = 'none';

        if (!bankId) {
            infoBankDiv.innerHTML = '';
            return;
        } else {
            fetch(`<?= site_url('payments/infobankGet') ?>/${bankId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('<?= translate('error getting data'); ?>');
                }
                return response.json();
            })
            .then(data => {
                infoBankDiv.innerHTML = `
                <div class="card shadow-sm p-3 mb-3" style="border-radius: 12px; width: 85%; background:#fff;">
                    <div class="d-flex align-items-center mb-2">
                        <!-- Logo -->
                        <div style="flex:0 0 70px; text-align:center;">
                            ${data.logo_url}
                        </div>
                        <!-- Nombre banco -->
                        <div style="flex:1; padding-left:10px;">
                            <h6 class="mb-0"><strong>${data.bank}</strong></h6>
                        </div>
                    </div>

                    <!-- Datos -->
                    <div class="mt-2">
                        <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                            <small><strong><?= translate('account'); ?>:</strong> ${data.account}</small>
                            <i class="fa-duotone fa-copy text-primary cursor-pointer" onclick="copyText('<?= translate('account'); ?>', '${data.account}')"></i>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                            <small><strong><?= translate('holder'); ?>:</strong> ${data.holder}</small>
                            <i class="fa-duotone fa-copy text-primary cursor-pointer" onclick="copyText('<?= translate('holder'); ?>', '${data.holder}')"></i>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                            <small><strong><?= translate('document'); ?>:</strong> ${data.document}</small>
                            <i class="fa-duotone fa-copy text-primary cursor-pointer" onclick="copyText('<?= translate('document'); ?>', '${data.document}')"></i>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <small><strong><?= translate('phone'); ?>:</strong> ${data.phone}</small>
                            <i class="fa-duotone fa-copy text-primary cursor-pointer" onclick="copyText('<?= translate('phone'); ?>', '${data.phone}')"></i>
                        </div>
                    </div>
                </div>`;
                infoBankDiv.style.display = 'block';
            })
            .catch(error => {
                Toastify({
                    text: "<?= translate('bank details could not be loaded'); ?>",
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    style: { background: "#dc3545" },
                    stopOnFocus: true
                }).showToast();
            });
                
            /*.then(data => {
                document.getElementById('deposit-info-bank').innerHTML = `<div class="row"><div class="col-md-12 px-3 pt-2"><h6 class="help-block"><i class="fa-duotone fa-solid fa-building-columns"></i> <?= translate('bank'); ?>: ${data.bank} <span class="float-end"><i class="fa-duotone fa-solid fa-copy"></i></span></h6><h6 class="help-block"><?= translate('holder'); ?>: ${data.holder} - <?= translate('account'); ?>: ${data.account} <span class="float-end"><i class="fa-duotone fa-solid fa-copy"></i></span></h6><h6 class="help-block"><?= translate('document'); ?>: ${data.document} - <?= translate('phone'); ?>: ${data.phone} <span class="float-end"><i class="fa-duotone fa-solid fa-copy"></i></span></h6></div></div>`})
            .catch(error => {
                Toastify({
                    text: "<?= translate('bank details could not be loaded'); ?>",
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    style: { background: "#dc3545" },
                    stopOnFocus: true
                }).showToast();
            });*/
        }
    }

    function copyText(data, text) {
        navigator.clipboard.writeText(text).then(() => {
            Toastify({
                text: data + " " + text,
                duration: 3000,
                gravity: "top",
                position: "right",
                style: { background: "#198754" },
                stopOnFocus: true
            }).showToast();
        });
    }

    async function pasteReference() {
        try {
            const text = await navigator.clipboard.readText();
            document.getElementById("deposit-reference").value = text;
        } catch (err) {
            console.log("No se pudo acceder al portapapeles");
        }
    }

    function previewvoucherImage(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const output = document.getElementById('voucherImage');
            output.src = reader.result;
            document.getElementById('voucher_image_input').value = reader.result;

            // Mostrar botón eliminar
            document.getElementById('removeVoucherBtn').classList.remove('d-none');
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    function removeVoucherImage() {
        document.getElementById('voucherImage').src = "<?= site_url('uploads/vouchers/image.jpg'); ?>";
        document.getElementById('voucher_image_input').value = ""; 
        document.getElementById('voucherfileInput').value = "";  

        // Ocultar botón eliminar
        document.getElementById('removeVoucherBtn').classList.add('d-none');
    }

    // Al cargar si ya hay imagen, mostrar botón eliminar
    window.addEventListener("DOMContentLoaded", () => {
        if (document.getElementById('voucherImage').src) {
            document.getElementById('removeVoucherBtn').classList.remove('d-none');
        }
    });
</script>