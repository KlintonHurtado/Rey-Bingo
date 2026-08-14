<div class="modal-dialog modal-dialog-centered max-w-40">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2"><i class="fa-duotone fa-arrow-up-from-bracket"></i> <?= translate('request retire'); ?></h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body pt-0">
            <?php
                $kycVerified = wallet_kyc_allows_withdraw($user);
                $kycMessage = wallet_kyc_withdraw_message($user);
                $kycActionLabel = wallet_kyc_action_label($user);
            ?>
            <?php if (! empty($pendingRetire)): ?>
                <div class="alert alert-info border-0 mb-3 p-3" role="alert" style="border-radius: 14px; background: #eef2ff; color: #1e1b4b;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="fa-duotone fa-solid fa-clock-rotate-left fs-3 mt-1 text-primary"></i>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-1 text-primary">Tienes una solicitud de retiro en proceso</h6>
                            <p class="small mb-2 text-muted">
                                Tu solicitud está siendo procesada. No es posible enviar una nueva solicitud hasta que esta sea completada.
                            </p>
                            <div class="p-2 rounded bg-white border mb-1" style="font-size: 0.88rem;">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Monto:</span>
                                    <strong class="text-success"><?= systemGet('currency') ?? '$'; ?> <?= number_format((float) ($pendingRetire['amount'] ?? 0), 2); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Método:</span>
                                    <strong class="text-dark"><?= esc($pendingRetire['bank'] ?? '-'); ?></strong>
                                </div>
                                <?php if (($pendingRetire['bank'] ?? '') === 'Punto de Venta'): ?>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Código de Retiro:</span>
                                        <strong class="text-primary small">Enviado a tu correo</strong>
                                    </div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Estado:</span>
                                    <span class="badge bg-warning text-dark">En Revisión</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (! $kycVerified): ?>
                <div id="retire-kyc-alert" class="alert alert-warning border-0 mb-3 py-3" role="alert" style="border-radius: 12px;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="fa-duotone fa-solid fa-id-card fs-4 mt-1"></i>
                        <div class="flex-grow-1">
                            <strong class="d-block mb-1">Verificación de identidad requerida</strong>
                            <p class="small mb-2"><?= esc($kycMessage); ?></p>
                            <a href="<?= site_url('kyc'); ?>" class="btn btn-sm btn-primary btn-bingo">
                                <?= esc($kycActionLabel); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php echo form_open(site_url() . 'payments/retireSubmit', array('enctype' => 'multipart/form-data', 'id' => 'retire-form'));?>
                
                <?= csrf_field() ?>
                
                <div class="row">
                    <div class="col-md-12 mb-1">
                        <label for="retire-receiver" class="form-label"><?= translate('receiver bank'); ?></label>
                        <select class='form-control form-control-lg form-bingo' name="retire-receiver" id="retire-receiver" onchange="retirebankGet();"<?= ! empty($pendingRetire) ? ' disabled' : ''; ?>>
                            <option value=""><?= translate('receiver bank'); ?></option>
                            <option value="store">Punto de Venta (Cobro en Efectivo)</option>
                            <option value="0"><?= translate('new bank'); ?></option>
                            <?php if (isset($user['bank']) && !empty($user['bank'])): ?>
                                <option value="<?= $user['bank'] ?>"><?= $user['bank'] ?></option>
                            <?php endif; ?>
                        </select>
                        <small id="retire-receiver-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-12 d-flex justify-content-center" id="retire-info-bank"></div>
                </div>

                <div class="row" id="new-bank" style="display: none;">
                    <div class="col-md-12 mb-1">
                        <label for="retire-bank" class="form-label"><?= translate('home bank'); ?></label>
                        <select class='form-control form-control-lg form-bingo' name="retire-bank" id="retire-bank"<?= ! empty($pendingRetire) ? ' disabled' : ''; ?>>
                                <option value=""><?= translate('select a'); ?> <?= strtolower(translate('bank')); ?></option>
                                <!-- BANCOS -->
                                <option value="BANCO PICHINCHA">BANCO PICHINCHA</option>
                                <option value="BANCO GUAYAQUIL">BANCO GUAYAQUIL</option>
                                <option value="BANCO DEL PACIFICO">BANCO DEL PACÍFICO</option>
                                <option value="BANCO DEL AUSTRO">BANCO DEL AUSTRO</option>

                                <!-- COOPERATIVAS -->
                                <option value="COOP. JEP">COOP. JEP</option>
                                <option value="COOP. POLICIA NACIONAL">COOP. POLICÍA NACIONAL</option>
                                <option value="COOP. ALIANZA DEL VALLE">COOP. ALIANZA DEL VALLE</option>
                                <option value="COOP. 29 DE OCTUBRE">COOP. 29 DE OCTUBRE</option>
                                <option value="COOP. 15 DE ABRIL">COOP. 15 DE ABRIL</option>
                                <option value="COOP. OSCUS">COOP. OSCUS</option>
                                <option value="COOP. ANDALUCIA">COOP. ANDALUCÍA</option>
                                <option value="COOP. COOPROGRESO">COOP. COOPROGRESO</option>
                                <option value="COOP. TULCAN">COOP. TULCÁN</option>
                                <option value="COOP. RIOBAMBA">COOP. RIOBAMBA</option>
                                <option value="COOP. SAN FRANCISCO">COOP. SAN FRANCISCO</option>
                                <option value="COOP. CACPECO">COOP. CACPECO</option>
                                <option value="COOP. MUSHUC RUNA">COOP. MUSHUC RUNA</option>
                                <option value="COOP. ATUNTAQUI">COOP. ATUNTAQUI</option>
                                <option value="COOP. 23 DE JULIO">COOP. 23 DE JULIO</option>
                                <option value="COOP. COMERCIO">COOP. COMERCIO</option>
                                <option value="COOP. AMBATO">COOP. AMBATO</option>
                                <option value="COOP. SANTA ROSA">COOP. SANTA ROSA</option>
                                <option value="COOP. MANANTIAL">COOP. MANANTIAL</option>
                                <option value="COOP. COTOPAXI">COOP. COTOPAXI</option>
                                <option value="COOP. PADRE JULIAN LORENTE">COOP. PADRE JULIÁN LORENTE</option>
                                <option value="COOP. ARTESANOS">COOP. ARTESANOS</option>
                                <option value="COOP. LA DOLOROSA">COOP. LA DOLOROSA</option>
                                <option value="COOP. 9 DE OCTUBRE">COOP. 9 DE OCTUBRE</option>
                                <option value="COOP. SAN ANTONIO">COOP. SAN ANTONIO</option>
                                <option value="COOP. CHIBULEO">COOP. CHIBULEO</option>
                                <option value="COOP. SANTA ANA">COOP. SANTA ANA</option>
                                <option value="COOP. SAN JOSE">COOP. SAN JOSÉ</option>
                                <option value="COOP. VILCABAMBA">COOP. VILCABAMBA</option>
                                <option value="COOP. GUARANDA">COOP. GUARANDA</option>
                                <option value="COOP. EL SAGRARIO">COOP. EL SAGRARIO</option>
                                <option value="COOP. CAMARA DE COMERCIO DE QUITO">COOP. CÁMARA DE COMERCIO DE QUITO</option>
                        </select>
                        <small id="retire-bank-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-12 mb-1">
                        <label for="retire-account-type" class="form-label"><?= translate('account type'); ?></label>
                        <select class="form-control form-control-lg form-bingo" name="retire-account-type" id="retire-account-type">
                            <option value=""><?= translate('select a'); ?> <?= strtolower(translate('account type')); ?></option>
                            <option value="savings"><?= translate('savings account'); ?></option>
                            <option value="checking"><?= translate('checking account'); ?></option>
                        </select>
                        <small id="retire-account-type-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-12 mb-1">
                        <label for="retire-account" class="form-label">Número de cuenta</label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="retire-account" id="retire-account" placeholder="Ingrese número de cuenta" autocomplete="off">
                        <small id="retire-account-error" class="text-danger d-none"></small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-1">
                            <label for="retire-document" class="form-label"><?= translate('document'); ?></label>
                            <input type="text" class="form-control form-control-lg form-bingo" name="retire-document" id="retire-document" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('document')); ?>" autocomplete="off">
                            <small id="retire-document-error" class="text-danger d-none"></small>
                        </div>
                        
                        <div class="col-md-6 mb-1">
                            <label for="retire-phone" class="form-label"><?= translate('phone'); ?></label>
                            <input type="number" class="form-control form-control-lg form-bingo" name="retire-phone" id="retire-phone" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('phone')); ?>" autocomplete="off">
                            <small id="retire-phone-error" class="text-danger d-none"></small>
                        </div>
                    </div>
                </div>

                <div class="row" id="current-bank" style="display: none;">
                    <div class="col-md-12 mb-1">
                        <label for="retire-amount" class="form-label"><?= translate('amount'); ?></label>
                        <input type="number" class="form-control form-control-lg form-bingo" name="retire-amount" id="retire-amount" placeholder="0.00" autocomplete="off">
                        <small id="retire-amount-error" class="text-danger d-none"></small>
                    </div>
                </div>

                <div class="row" id="save-account-bank" style="display: none;">
                    <div class="col-md-12 mb-1">
                        <label for="save-account" class="form-check-label ms-5"> <input class="form-check-input" type="checkbox" name="save-account" id="save-account" value="1"> <?= translate('add account as primary'); ?></label>
                    </div>
                </div>

                <div class="col-md-12">
                    <?php if (! empty($pendingRetire)): ?>
                        <button type="button" class="btn btn-secondary d-block w-50 mt-2" disabled>
                            <i class="fa-duotone fa-solid fa-clock-rotate-left me-1"></i> Solicitud en Proceso
                        </button>
                    <?php else: ?>
                        <button type="submit" class="btn btn-primary d-block w-50 btn-bingo mt-2" id="retire-button"<?= (! $kycVerified || wallet_withdrawable($user) <= 0) ? ' disabled' : ''; ?>><?= translate('send'); ?></button>
                    <?php endif; ?>
                </div>
            <?= form_close(); ?>

            <hr />

            <div class="text-center mb-2">
                Disponible para retirar (Ganancias): <?= systemGet('currency'); ?> <span class="available-wallet fw-bold"><?= number_format(wallet_withdrawable($user), 2); ?></span>
            </div>
            <?php if (wallet_withdrawable($user) <= 0): ?>
            <div class="alert alert-info border-0 py-2 px-3 mt-1" style="border-radius:10px; font-size:0.85rem;">
                <i class="fa-duotone fa-solid fa-circle-info me-1"></i>
                Tu saldo de <strong>bono</strong> (<?= systemGet('currency'); ?> <?= number_format($user['wallet_bonus'], 2); ?>) y de <strong>recarga</strong> (<?= systemGet('currency'); ?> <?= number_format($user['wallet_recharge'], 2); ?>) sirven para comprar cartones. Los premios de cartones con bono/ruleta van a <strong>saldo recarga</strong>; los de cartones con recarga/retiro van a <strong>saldo retiro</strong> (retirable).
                El saldo retirable es el que puedes solicitar para cobro a tu banco.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $('#retire-form').on('submit', function (e) {
            e.preventDefault();

            var button = $('#retire-button');
            button.prop("disabled", true); 

            $('.text-danger').addClass('d-none').text('');
            $('.form-control').removeClass('is-invalid');

            $.ajax({
                url: '<?= site_url('payments/retireSubmit') ?>',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $('#modalRetire').modal('hide');

                        if (response.newRetire) {
                            updateTableRetire(response.newRetire);
                        }

                        if (response.is_store) {
                            Swal.fire({
                                title: '¡Solicitud de Retiro Enviada!',
                                html: `
                                    <div class="my-2">
                                        <div class="mb-3">
                                            <i class="fa-duotone fa-solid fa-envelope-circle-check text-primary" style="font-size: 3rem;"></i>
                                        </div>
                                        <p class="mb-2 fw-semibold text-dark" style="font-size: 1rem;">
                                            Tu código de retiro ha sido enviado a tu correo electrónico registrado.
                                        </p>
                                        <p class="small text-muted mb-0">
                                            Por favor, revisa tu bandeja de entrada para ver tu código y cobrar en cualquier Punto de Venta presentando tu número de cédula.
                                        </p>
                                    </div>`,
                                icon: 'success',
                                confirmButtonText: 'Entendido',
                                confirmButtonColor: '#6236ff'
                            });
                        } else {
                            Toastify({
                                text: response.message || "<?= translate('retire request sent successfully'); ?>",
                                duration: 4000,
                                gravity: "top",
                                position: "right",
                                style: { background: "#198754" },
                                stopOnFocus: true
                            }).showToast();
                        }
                    } else if (response.minMax) {
                        Toastify({
                            text: response.message,
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            style: { background: "#dc3545" },
                            stopOnFocus: true
                        }).showToast();
                    } else if (response.kyc_required) {
                        const kycAlert = document.getElementById('retire-kyc-alert');
                        if (kycAlert) {
                            kycAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                            kycAlert.classList.add('border', 'border-danger');
                        }
                        Toastify({
                            text: response.message || 'Debes verificar tu identidad antes de retirar.',
                            duration: 5000,
                            gravity: "top",
                            position: "right",
                            style: { background: "#fd7e14" },
                            stopOnFocus: true
                        }).showToast();
                    } else if (response.errors) {
                        let firstError = '';
                        $.each(response.errors, function(field, message) {
                            $('#' + field + '-error').text(message).removeClass('d-none');
                            $('#' + field).addClass('is-invalid');
                            if (!firstError) firstError = message;
                        });
                        Toastify({
                            text: firstError || "Por favor verifica los campos obligatorios.",
                            duration: 4000,
                            gravity: "top",
                            position: "right",
                            style: { background: "#dc3545" },
                            stopOnFocus: true
                        }).showToast();
                    } else if (response.message) {
                        Toastify({
                            text: response.message,
                            duration: 4000,
                            gravity: "top",
                            position: "right",
                            style: { background: "#dc3545" },
                            stopOnFocus: true
                        }).showToast();
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

    function updateTableRetire(payment) {
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

    function retirebankGet() {
        
        const bankId = document.getElementById('retire-receiver').value;

        const infoBankDiv = document.getElementById('retire-info-bank');
        const newBankDiv = document.getElementById('new-bank');
        const currentBankDiv = document.getElementById('current-bank');
        const saveaccountBankDiv = document.getElementById('save-account-bank');

        infoBankDiv.innerHTML = '';
        infoBankDiv.style.display = 'none';
        newBankDiv.style.display = 'none';
        currentBankDiv.style.display = 'none';
        saveaccountBankDiv.style.display = 'none';

        if (!bankId) {
            return;
        }

        if (bankId === "0") {
            newBankDiv.style.display = 'block';
            currentBankDiv.style.display = 'block';
            saveaccountBankDiv.style.display = 'block';
        } else if (bankId === "store") {
            currentBankDiv.style.display = 'block';
            saveaccountBankDiv.style.display = 'none';
            infoBankDiv.innerHTML = `
                <div class="card shadow p-3 mb-3 border-0" style="border-radius: 12px; width: 100%; background: #ffffff; color: #212529;">
                    <div class="d-flex align-items-start gap-3">
                        <div class="text-center pt-1" style="font-size: 1.8rem; min-width: 40px; color: #0d6efd;">
                            <i class="fa-duotone fa-solid fa-store"></i>
                        </div>
                        <div>
                            <strong class="d-block fs-6" style="color: #0b5ed7; font-weight: 700; font-size: 1.05rem;">Retiro en Efectivo - Punto de Venta</strong>
                            <div class="mt-1" style="color: #495057; font-size: 0.88rem; line-height: 1.45;">
                                Ingresa el monto a retirar. Al enviar tu solicitud, te enviaremos un <strong style="color: #111827;">código de retiro alfanumérico a tu correo</strong>. Podrás cobrar tu dinero en efectivo en cualquier Punto de Venta presentando tu número de cédula y el código.
                            </div>
                        </div>
                    </div>
                </div>`;
            infoBankDiv.style.display = 'block';
        } else {
            currentBankDiv.style.display = 'block';
            saveaccountBankDiv.style.display = 'none';

            fetch(`<?= site_url('payments/retirebankGet') ?>`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('<?= translate('error getting data'); ?>');
                    }
                    return response.json();
                })
                .then(data => {
                    infoBankDiv.innerHTML = `
                        <div class="card shadow-sm p-2 d-flex flex-row align-items-center" style="border-radius: 10px; width: 85%;">
                            <div style="flex: 0 0 80px; text-align:center;">
                                <i class="fa-duotone fa-solid fa-building-columns fs-1"></i>
                            </div>
                            <div style="flex: 1; padding-left: 5px;">
                                <h6 class="mb-1"><strong><?= translate('bank'); ?>:</strong> ${data.bank}</h6>
                                <small class="mb-0"><strong><?= translate('account'); ?>:</strong> ${data.account}</small> <br />
                                <small class="mb-0"><strong><?= translate('account type'); ?>:</strong> ${data.account_type_label || '—'}</small> <br />
                                <small class="mb-0"><strong><?= translate('holder'); ?>:</strong> ${data.holder}</small> <br />
                                <small class="mb-0"><strong><?= translate('document'); ?>:</strong> ${data.document} - <strong><?= translate('phone'); ?>:</strong> ${data.phone}</small>
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
        }
    }

    function validateAmount() {
        const amountInput = document.getElementById('retire-amount');
        const amountError = document.getElementById('retire-amount-error');
        
        $.ajax({
            url: '<?= site_url('payments/availablewalletGet') ?>',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                const walletAmount = parseFloat(response.withdrawable ?? response.wallet?.withdraw ?? response.wallet);
                const enteredAmount = parseFloat(amountInput.value);

                if (isNaN(enteredAmount) || enteredAmount <= 0) {
                    amountError.textContent = '<?= translate('enter a valid amount'); ?>';
                    amountError.classList.remove('d-none');
                    amountInput.classList.add('is-invalid');
                } else if (enteredAmount > walletAmount) {
                    amountError.textContent = `<?= translate('the amount cannot exceed what is available'); ?>: <?= systemGet('currency'); ?> ${walletAmount.toFixed(2)}.`;
                    amountError.classList.remove('d-none');
                    amountInput.classList.add('is-invalid');
                } else {
                    amountError.textContent = '';
                    amountError.classList.add('d-none');
                    amountInput.classList.remove('is-invalid');
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
            }
        });
    }

    document.getElementById('retire-amount').addEventListener('input', validateAmount);
</script>