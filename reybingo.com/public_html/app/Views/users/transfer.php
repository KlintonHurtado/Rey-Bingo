<div class="modal-dialog modal-dialog-centered max-w-40">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2"><i class="fa-duotone fa-solid fa-money-bill-transfer"></i> <?= translate('transfer between wallets'); ?></h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body pt-0">
            <?php echo form_open(site_url() . 'payments/transferSubmit', array('enctype' => 'multipart/form-data', 'id' => 'transfer-form'));?>
                
                <?= csrf_field() ?>
                
                <div class="row">
                    <div class="col-md-12 mb-1">
                        <label for="user" class="form-label"><?= translate('bgc player'); ?></label>
                        <input type="text" class="form-control form-control-lg form-bingo" name="user" id="user" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('code')); ?>" autocomplete="off">
                        <small id="user-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-12 d-flex justify-content-center" id="user-info"></div>

                    <div class="col-md-12 mb-1">
                        <label for="amount" class="form-label"><?= translate('amount'); ?></label>
                        <input type="number" class="form-control form-control-lg form-bingo" name="amount" id="amount" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('amount')); ?>" autocomplete="off" value="0.00">
                        <small id="amount-error" class="text-danger d-none"></small>
                    </div>

                    <div class="col-md-12 mb-1">
                        <label for="modal-note" class="form-label"><?= translate('note'); ?></label>
                        <textarea class="form-control form-control-lg form-bingo" name="note" id="note" rows="2" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('note')); ?>"></textarea>
                    </div>
                </div>

                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary d-block w-50 btn-bingo mt-2" id="transfer-button"><?= translate('send'); ?></button>
                </div>
            <?= form_close(); ?>

            <hr />

            <div class="text-center">
                Disponible para transferir: <?= systemGet('currency'); ?> <span class="available-wallet"><?= number_format(wallet_withdrawable(wallet_service()->normalizeUser($user)), 2); ?></span>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $('#transfer-form').on('submit', function (e) {
            e.preventDefault();

            var available = $('#available-wallet');

            var button = $('#transfer-button');
            button.prop("disabled", true);

            $('.text-danger').addClass('d-none').text('');
            $('.form-control').removeClass('is-invalid');

            $.ajax({
                url: '<?= site_url('payments/transferSubmit') ?>',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        
                        $('#modalTransfer').modal('hide');

                        available.html(response.wallet);

                        if (response.newTransfer) {
                            updateTableTransfer(response.newTransfer);
                        }

                        Toastify({
                            text: "<?= translate('transfer sent successfully'); ?>",
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
    });

    function updateTableTransfer(payment) {
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

        var user_id = '<?= session()->get('id'); ?>';

        // Manejar el monto según el tipo de transacción
        let amountHtml = '';
        if (payment.type === 'retire') {
            amountHtml = `
                <strong class="icon-danger">
                    -<?= systemGet('currency'); ?> ${formatNumber(payment.amount)}
                </strong>
            `;
        } else if (payment.type === 'transfer') {
            <?php if (session()->get('group') == 1) : ?>
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
            <?php else: ?>
                if (user_id == payment.user_id) {
                    amountHtml = `
                        <div>
                            <strong class="icon-danger d-block">
                                -<?= systemGet('currency'); ?> ${formatNumber(payment.amount)}
                            </strong>
                        </div>
                    `;
                } else {
                    amountHtml = `
                        <div>
                            <strong class="text-success d-block">
                                +<?= systemGet('currency'); ?> ${formatNumber(payment.amount)}
                            </strong>
                        </div>
                    `;
                }
            <?php endif; ?>
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

    function transferUserGet() {
        const userInput = document.getElementById('user');
        const userError = document.getElementById('user-error');
        const infoUserDiv = document.getElementById('user-info');

        const userCode = userInput.value.trim();
        const bgcFormat = /^BGC-[A-Z]\d{5}$/; // Ejemplo: BGC-A00003

        // Limpiar mensajes e info
        userError.classList.add('d-none');
        userError.textContent = '';
        infoUserDiv.innerHTML = '';
        infoUserDiv.style.display = 'none';

        // Validar campo vacío
        if (!userCode) {
            userError.textContent = '<?= translate("please enter a BGC code"); ?>';
            userError.classList.remove('d-none');
            return;
        }

        // Validar formato
        if (!bgcFormat.test(userCode)) {
            userError.textContent = '<?= translate("invalid BGC code format"); ?>';
            userError.classList.remove('d-none');
            return;
        }

        // Petición al servidor
        fetch(`<?= site_url('payments/transferUserGet/') ?>${encodeURIComponent(userCode)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('<?= translate("error getting data"); ?>');
                }
                return response.json();
            })
            .then(data => {
                if (!data || !data.firstname) {
                    userError.textContent = '<?= translate("no user found with this BGC code"); ?>';
                    userError.classList.remove('d-none');
                    return;
                }

                // Mostrar card de usuario
                infoUserDiv.innerHTML = `
                    <div class="card shadow-sm p-2 d-flex flex-row align-items-center" style="border-radius: 10px; width: 85%;">
                        <div style="flex: 0 0 80px; text-align:center;">
                            <img src="${data.image}" alt="img" class="img-fluid" style="width:70px; height:70px; object-fit:cover;">
                        </div>
                        <div style="flex: 1; padding-left: 5px;">
                            <h6 class="mb-1">${data.firstname} ${data.lastname}</h6>
                            <small class="mb-0"><strong><?= translate("document"); ?>:</strong> ${data.document} <br />
                            <small class="mb-0"><strong><?= translate("email"); ?>:</strong> ${data.email}</small>
                        </div>
                    </div>`;
                infoUserDiv.style.display = 'block';
            })
            .catch(error => {
                userError.textContent = '<?= translate("user details could not be loaded"); ?>';
                userError.classList.remove('d-none');
            });
    }

    var searchTimer;
    document.getElementById('user').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            transferUserGet();
        }, 500);
    });

    function validateAmount() {
        const amountInput = document.getElementById('amount');
        const amountError = document.getElementById('amount-error');
        
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

    document.getElementById('amount').addEventListener('input', validateAmount);
</script>