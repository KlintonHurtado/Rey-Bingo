<div class="modal-dialog modal-dialog-centered max-w-50">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2" id="bank-modal-title"><i class="fa-duotone fa-solid fa-building-columns"></i>
                <?= translate('add bank'); ?></h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i
                    class="fa-duotone fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body pt-0">
            <?php echo form_open(site_url() . 'home/bankSubmit', array('enctype' => 'multipart/form-data', 'id' => 'bank-form')); ?>

            <?= csrf_field() ?>
            <input type="hidden" id="bank-id" name="bank-id" value="">
            <input type="hidden" id="bank-action" name="bank-action" value="add">

            <div class="row">
                <div class="col-md-3 my-auto text-center">
                    <div class="col-md-12">
                        <div class="logo-picture">
                            <img id="logoBankImage" src="<?= site_url('uploads/banks/image.jpg'); ?>" alt="logo banco">
                            <label for="bankfileInput" class="edit-button logo"><i
                                    class="fa-duotone fa-edit"></i></label>
                            <input type="file" id="bankfileInput" accept="image/*" style="display: none;"
                                onchange="previewbanklogoImage(event)">
                            <input type="hidden" id="bank_logo_image_input" name="bank-logo">
                        </div>
                    </div>
                </div>

                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-12 mb-1">
                            <label for="name-bank" class="form-label"><?= translate('bank'); ?></label>
                            <select class='form-control form-control-lg form-bingo' name="name-bank" id="name-bank">
                                <option value=""><?= translate('select a'); ?> <?= strtolower(translate('bank')); ?>
                                </option>
                                <option value="0102 - BANCO DE VENEZUELA">0102 - BANCO DE VENEZUELA</option>
                                <option value="0104 - VENEZOLANO DE CRÉDITO">0104 - VENEZOLANO DE CRÉDITO</option>
                                <option value="0105 - BANCO MERCANTIL">0105 - BANCO MERCANTIL</option>
                                <option value="0108 - BANCO PROVINCIAL">0108 - BANCO PROVINCIAL</option>
                                <option value="0114 - BANCO DEL CARIBE">0114 - BANCO DEL CARIBE</option>
                                <option value="0115 - BANCO EXTERIOR">0115 - BANCO EXTERIOR</option>
                                <option value="0116 - BANCO OCCIDENTAL DE DESCUENTO">0116 - BANCO OCCIDENTAL DE
                                    DESCUENTO</option>
                                <option value="0128 - BANCO CARONI">0128 - BANCO CARONI</option>
                                <option value="0134 - BANESCO">0134 - BANESCO</option>
                                <option value="0137 - BANCO SOFITASA">0137 - BANCO SOFITASA</option>
                                <option value="0138 - BANCO PLAZA">0138 - BANCO PLAZA</option>
                                <option value="0146 - BANCO DE LA GENTE EMPRENDEDORA BANGENTE">0146 - BANCO DE LA GENTE
                                    EMPRENDEDORA BANGENTE</option>
                                <option value="0149 - BANCO DEL PUEBLO SOBERANO">0149 - BANCO DEL PUEBLO SOBERANO
                                </option>
                                <option value="0151 - BFC BANCO FONDO COMUN C.A.">0151 - BFC BANCO FONDO COMUN C.A.
                                </option>
                                <option value="0156 - 100%BANCO">0156 - 100%BANCO</option>
                                <option value="0157 - DELSUR">0157 - DELSUR</option>
                                <option value="0163 - BANCO DEL TESORO">0163 - BANCO DEL TESORO</option>
                                <option value="0164 - BANCO DE DESARROLLO DEL MICROEMPRESARIO">0164 - BANCO DE
                                    DESARROLLO DEL MICROEMPRESARIO</option>
                                <option value="0166 - BANCO AGRICOLA DE VENEZUELA">0166 - BANCO AGRICOLA DE VENEZUELA
                                </option>
                                <option value="0168 - BANCRECER">0168 - BANCRECER</option>
                                <option value="0169 - R4 BANCO MICROFINANCIERO">0169 - R4 BANCO MICROFINANCIERO</option>
                                <option value="0171 - BANCO ACTIVO">0171 - BANCO ACTIVO</option>
                                <option value="0172 - BANCAMIGA">0172 - BANCAMIGA</option>
                                <option value="0173 - BANCO INTERNACIONAL DE DESARROLLO">0173 - BANCO INTERNACIONAL DE
                                    DESARROLLO</option>
                                <option value="0174 - BANPLUS">0174 - BANPLUS</option>
                                <option value="0175 - BANCO DIGITAL DE LOS TRABAJADORES">0175 - BANCO DIGITAL DE LOS
                                    TRABAJADORES</option>
                                <option value="0176 - NOVO BANCO">0176 - NOVO BANCO</option>
                                <option value="0177 - BANCO DE LA FUERZA ARMADA NACIONAL BOLIVARIANA">0177 - BANCO DE LA
                                    FUERZA ARMADA NACIONAL BOLIVARIANA</option>
                                <option value="0190 - CITIBANK N.A.">0190 - CITIBANK N.A.</option>
                                <option value="0191 - BANCO NACIONAL CRÉDITO">0191 - BANCO NACIONAL CRÉDITO</option>
                            </select>
                            <small id="name-bank-error" class="text-danger d-none"></small>
                        </div>

                        <div class="col-md-6 mb-1">
                            <label for="account-bank" class="form-label"><?= translate('account'); ?></label>
                            <input type="text" class="form-control form-control-lg form-bingo" name="account-bank"
                                id="account-bank" placeholder="<?= translate('account'); ?>" autocomplete="off">
                            <small id="account-bank-error" class="text-danger d-none"></small>
                        </div>

                        <div class="col-md-6 mb-1">
                            <label for="holder-bank" class="form-label"><?= translate('holder'); ?></label>
                            <input type="text" class="form-control form-control-lg form-bingo" name="holder-bank"
                                id="holder-bank" placeholder="<?= translate('holder'); ?>" autocomplete="off">
                            <small id="holder-bank-error" class="text-danger d-none"></small>
                        </div>

                        <div class="col-md-6 mb-1">
                            <label for="document-bank" class="form-label"><?= translate('document'); ?></label>
                            <input type="number" class="form-control form-control-lg form-bingo" name="document-bank"
                                id="document-bank" placeholder="<?= translate('document'); ?>" autocomplete="off">
                            <small id="document-bank-error" class="text-danger d-none"></small>
                        </div>

                        <div class="col-md-6 mb-1">
                            <label for="phone-bank" class="form-label"><?= translate('phone'); ?></label>
                            <input type="text" class="form-control form-control-lg form-bingo" name="phone-bank"
                                id="phone-bank" placeholder="<?= translate('phone'); ?>" autocomplete="off">
                            <small id="phone-bank-error" class="text-danger d-none"></small>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary d-block w-50 btn-bingo mt-3"
                        id="bank-button"><?= translate('add'); ?></button>
                </div>
            </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    <?php if (isset($isUpdate) && $isUpdate && $bankData): ?>
        $(document).ready(function () {
            $('#bank-id').val('<?= $bankData['id'] ?>');
            $('#bank-action').val('update');
            $('#name-bank').val('<?= esc($bankData['name']) ?>');
            $('#account-bank').val('<?= esc($bankData['account']) ?>');
            $('#holder-bank').val('<?= esc($bankData['holder']) ?>');
            $('#document-bank').val('<?= esc($bankData['document']) ?>');
            $('#phone-bank').val('<?= esc($bankData['phone']) ?>');
            $('#logoBankImage').attr('src', '<?= esc($logo_url) ?>');
            $('#bank-modal-title').html('<i class="fa-duotone fa-solid fa-building-columns"></i> <?= translate('update bank'); ?>');
            $('#bank-button').text('<?= translate('update'); ?>');
        });
    <?php endif; ?>


    $(document).ready(function () {
        $('#bank-form').on('submit', function (e) {
            e.preventDefault();

            var button = $('#bank-button');
            button.prop("disabled", true);

            $('.text-danger').addClass('d-none').text('');
            $('.form-control').removeClass('is-invalid');

            $.ajax({
                url: '<?= site_url('home/bankSubmit') ?>',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $('#modalBank').modal('hide');

                        if (response.newdataBank) {
                            const bank = response.newdataBank;
                            const action = $('#bank-action').val();

                            if (action === 'add') {
                                updateBanks(bank);
                                $('#not-list').remove();

                                let newRow = `
                                    <tr id="bank-row-${bank.id}">
                                        <td class="text-left">
                                            <div class="p-2 d-flex flex-row align-items-center" style="border-radius: 10px; width: 100%;">
                                                <div style="flex: 0 0 50px; text-align:center;">
                                                    ${bank.logo_url}
                                                </div>
                                                <div style="flex: 1; padding-left: 5px;">
                                                    <h6 class="mb-1"><strong><?= translate('name bank'); ?>:</strong> ${bank.name}</h6>
                                                    <small class="mb-0"><strong><?= translate("account"); ?>:</strong> ${bank.account} - <strong><?= translate("holder"); ?>:</strong> ${bank.holder} <br />
                                                    <small class="mb-0"><strong><?= translate("document"); ?>:</strong> ${bank.document} - <strong><?= translate("phone"); ?>:</strong> ${bank.phone}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center"><span class="badge bg-primary p-2 text-uppercase fs-6"><?= translate('not'); ?></span></td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <button type="button" style="width: 40px; height: 40px; font-size: 1rem;" class="btn btn-sm btn-info" onclick="updateBank(${bank.id})" title="<?= translate('update'); ?>"><i class="fa-duotone fa-edit"></i></button>
                                                <button type="button" style="width: 40px; height: 40px; font-size: 1rem;" class="btn btn-sm btn-danger" onclick="deleteBank(${bank.id})" title="<?= translate('delete'); ?>"><i class="fa-duotone fa-solid fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>`;
                                $('#settings-list').append(newRow);

                                Toastify({
                                    text: "<?= translate('bank'); ?> <?= translate('added successfully'); ?>",
                                    duration: 3000,
                                    gravity: "top",
                                    position: "right",
                                    style: { background: "#198754" },
                                    stopOnFocus: true
                                }).showToast();
                            } else if (action === 'update') {
                                updateBankRow(bank);
                                updateBankSelect(bank);

                                Toastify({
                                    text: "<?= translate('bank'); ?> <?= translate('updated successfully'); ?>",
                                    duration: 3000,
                                    gravity: "top",
                                    position: "right",
                                    style: { background: "#198754" },
                                    stopOnFocus: true
                                }).showToast();
                            }
                        }
                    } else {
                        if (response.error) {
                            Toastify({
                                text: response.error,
                                duration: 3000,
                                gravity: "top",
                                position: "right",
                                style: { background: "#dc3545" },
                                stopOnFocus: true
                            }).showToast();
                        }

                        if (response.errors) {
                            $.each(response.errors, function (field, message) {
                                $('#' + field + '-error').text(message).removeClass('d-none');
                                $('#' + field).addClass('is-invalid');
                            });
                        }
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
                },
                complete: function () {
                    button.prop("disabled", false);
                }
            });
        });
    });

    function updateBanks(bank) {
        const select = document.getElementById('bank');

        if (!select.querySelector(`option[value="${bank.id}"]`)) {
            const option = document.createElement('option');
            option.value = bank.id;
            option.textContent = bank.name;
            select.appendChild(option);
        }
    }

    function updateBankRow(bank) {
        const row = document.getElementById(`bank-row-${bank.id}`);
        if (row) {
            row.innerHTML = `
                <td class="text-left">
                    <h1 class="h5 help-block"><i class="fa-duotone fa-solid fa-building-columns"></i> <?= translate('name bank'); ?>: ${bank.name}</h1>
                    <h1 class="h5 help-block"><?= translate('account'); ?>: ${bank.account} - <?= translate('holder'); ?>: ${bank.holder}</h1>
                    <h1 class="h5 help-block"><?= translate('document'); ?>: ${bank.document} - <?= translate('phone'); ?>: ${bank.phone}</h1>
                </td>
                <td class="text-center"><span class="badge bg-primary p-2 text-uppercase fs-6"><?= translate('not'); ?></span></td>
                <td class="text-center">
                    <div class="btn-group" role="group">
                        <button type="button" style="width: 40px; height: 40px; font-size: 1rem;" class="btn btn-sm btn-info" onclick="updateBank(${bank.id})" title="<?= translate('update'); ?>"><i class="fa-duotone fa-edit"></i></button>
                        <button type="button" style="width: 40px; height: 40px; font-size: 1rem;" class="btn btn-sm btn-danger" onclick="deleteBank(${bank.id})" title="<?= translate('delete'); ?>"><i class="fa-duotone fa-solid fa-trash"></i></button>
                    </div>
                </td>
            `;
        }
    }

    function updateBankSelect(bank) {
        const select = document.getElementById('bank');
        const option = select.querySelector(`option[value="${bank.id}"]`);
        if (option) {
            option.textContent = bank.name;
        }
    }

    function previewbanklogoImage(event) {
        const reader = new FileReader();
        reader.onload = function () {
            const output = document.getElementById('logoBankImage');
            output.src = reader.result;

            document.getElementById('bank_logo_image_input').value = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }
</script>