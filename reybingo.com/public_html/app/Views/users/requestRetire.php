<div class="modal-dialog modal-dialog-centered max-w-40">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2"><i class="fa-duotone fa-arrow-up-from-bracket"></i> <?= translate('process retire request'); ?></h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body pt-0">
            <div class="card shadow-sm p-2">
                <div class="row">
                    <div class="col-md-12 mb-1">
                        <h6 class="mb-1"><strong><?= translate('deposit details'); ?>:</strong> #<?= esc(str_pad($retire['id'], 4, '0', STR_PAD_LEFT)) ?></h6>
                        <h6 class="mb-1"><strong><?= translate('player'); ?>:</strong> <?= esc($user['code']) ?> - <?= esc($user['firstname']) ?> <?= esc($user['lastname']) ?></h6>
                        <h6 class="mb-1"><strong><?= translate('bank'); ?>:</strong> <?= esc($retire['bank']) ?></h6>
                    </div>

                    <?php
                    $isStoreRetire = ($retire['bank'] === 'Punto de Venta' || ($retire['account_type'] ?? '') === 'store_pickup');
                    $isAdmin = (session()->get('group') == 1);
                    ?>
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                            <?php if ($isStoreRetire && ! $isAdmin && (int)$retire['status'] !== 2): ?>
                                <small class="mb-0"><strong>Código de Retiro:</strong> <span class="badge bg-warning-subtle text-dark border">Se revelará y enviará al ser Aprobada</span></small>
                            <?php else: ?>
                                <small class="mb-0"><strong><?= translate('account'); ?> / Código:</strong> <?= esc($retire['account']) ?></small>
                                <i class="fa-duotone fa-copy text-primary cursor-pointer" onclick="copyText('<?= translate('account'); ?>', '<?= esc($retire['account']) ?>')"></i>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                            <small class="mb-0"><strong><?= translate('account type'); ?>:</strong> <?= esc(bingo_account_type_label($retire['account_type'] ?? '') ?: '—') ?></small>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                            <small class="mb-0"><strong><?= translate('amount'); ?>:</strong> <?= systemGet('currency'); ?> <?= esc($retire['amount']) ?></small>
                            <i class="fa-duotone fa-copy text-primary cursor-pointer" onclick="copyText('<?= translate('amount'); ?>', '<?= esc($retire['amount']) ?>')"></i>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                            <small class="mb-0"><strong><?= translate('document'); ?>:</strong> <?= esc($retire['document']) ?></small>
                            <i class="fa-duotone fa-copy text-primary cursor-pointer" onclick="copyText('<?= translate('document'); ?>', '<?= esc($retire['document']) ?>')"></i>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                            <small class="mb-0"><strong><?= translate('phone'); ?>:</strong> <?= esc($retire['phone']) ?></small>
                            <i class="fa-duotone fa-copy text-primary cursor-pointer" onclick="copyText('<?= translate('phone'); ?>', '<?= esc($retire['phone']) ?>')"></i>
                        </div>
                        <small class="mb-0"><strong><?= translate('request date'); ?>:</strong> <?= esc(date('d/m/Y h:i A', strtotime($retire['created_at']))) ?></small> <small class="mb-0 float-end"><strong><?= translate('status'); ?>:</strong> <?= $status ?></small> 
                        <?php if ($retire['observation'] != '') : ?>
                            <br />
                            <small class="mb-0"><strong><?= translate('observation'); ?>:</strong> <?= esc($retire['observation']) ?></small>
                        <?php endif; ?>
                    </div>
                
                    <?php if ($retire['status'] == 1) : ?>
                        <div class="col-md-12 mb-1 mt-2">
                            <label for="observation" class="form-label"><?= translate('observation'); ?></label>
                            <textarea class="form-control form-control-lg form-bingo" name="observation" id="observation" rows="2" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('observation')); ?>"></textarea>
                        </div>
                    <?php endif; ?>

                    <div class="text-center mt-2">
                        <?= translate('available in wallet'); ?> <?= systemGet('currency'); ?> <span class="wallet-withdraw-value fw-bold"><?= number_format($user['wallet_withdraw'] ?? $user['wallet'] ?? 0, 2); ?></span>
                    </div>
                </div>
            </div>

            <?php if (session()->get('group') == 1) : ?>
                <!-- ACCIONES ADMINISTRADOR -->
                <?php if ($retire['status'] == 1) : ?>
                    <div class="col-md-12 text-center mt-3">
                        <button type="button" class="btn btn-info text-white btn-bingo inline me-1" onclick="statusSubmit('<?= $type ?>', '<?= $retire['id'] ?>', 'review');">
                            <i class="fa-duotone fa-solid fa-magnifying-glass"></i> Revisar
                        </button>
                        <button type="button" class="btn btn-success btn-bingo inline me-1" onclick="statusSubmit('<?= $type ?>', '<?= $retire['id'] ?>', 'approve');">
                            <i class="fa-duotone fa-solid fa-check"></i> <?= translate('approve'); ?>
                        </button>
                        <button type="button" class="btn btn-danger btn-bingo inline" onclick="statusSubmit('<?= $type ?>', '<?= $retire['id'] ?>', 'refuse');">
                            <i class="fa-duotone fa-solid fa-xmark"></i> <?= translate('refuse'); ?>
                        </button>
                    </div>
                <?php elseif ($retire['status'] == 3) : ?>
                    <div class="col-md-12 text-center mt-3">
                        <button type="button" class="btn btn-success btn-bingo inline me-2" onclick="statusSubmit('<?= $type ?>', '<?= $retire['id'] ?>', 'approve');">
                            <i class="fa-duotone fa-solid fa-check"></i> <?= translate('approve'); ?>
                        </button>
                        <button type="button" class="btn btn-danger btn-bingo inline" onclick="statusSubmit('<?= $type ?>', '<?= $retire['id'] ?>', 'refuse');">
                            <i class="fa-duotone fa-solid fa-xmark"></i> <?= translate('refuse'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            <?php else : ?>
                <!-- ACCIONES / ESTADO PARA EL USUARIO -->
                <?php if ($retire['status'] == 1) : ?>
                    <div class="col-md-12 text-center mt-3">
                        <div class="alert alert-warning py-1 small mb-2 text-start">
                            <i class="fa-duotone fa-solid fa-info-circle"></i> Tu solicitud está <strong>Pendiente</strong>. El saldo se encuentra apartado. Puedes cancelarla ahora si deseas usar tu saldo de retiro.
                        </div>
                        <button type="button" class="btn btn-danger btn-bingo inline" onclick="statusSubmit('<?= $type ?>', '<?= $retire['id'] ?>', 'cancel');">
                            <i class="fa-duotone fa-solid fa-ban"></i> Cancelar Solicitud
                        </button>
                    </div>
                <?php elseif ($retire['status'] == 3) : ?>
                    <div class="alert alert-info py-2 small mt-3 mb-0 text-center">
                        <i class="fa-duotone fa-solid fa-magnifying-glass"></i> Tu solicitud está <strong>En revisión</strong> por administración. Ya no puede ser cancelada.
                    </div>
                <?php elseif ($retire['status'] == 2) : ?>
                    <div class="alert alert-success py-2 small mt-3 mb-0 text-center">
                        <i class="fa-duotone fa-solid fa-circle-check"></i> Solicitud <strong>Aprobada</strong>.
                        <?php if ($retire['bank'] === 'Punto de Venta' || ($retire['account_type'] ?? '') === 'store_pickup') : ?>
                            <br>Código de retiro: <strong class="fs-6 text-primary"><?= esc($retire['account']) ?></strong> (también enviado a tu correo).
                        <?php endif; ?>
                    </div>
                <?php elseif ($retire['status'] == 0) : ?>
                    <div class="alert alert-secondary py-2 small mt-3 mb-0 text-center">
                        <i class="fa-duotone fa-solid fa-circle-xmark"></i> Solicitud <strong>Rechazada / Cancelada</strong>. El saldo fue reintegrado a tu billetera.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    function statusSubmit(type, id, action) {
        const statusElement = document.getElementById(`${type}-${id}`);
        const obsEl = document.getElementById('observation');
        const observation = obsEl ? obsEl.value : '';

        fetch('<?= site_url('payments/statusSubmit') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ type, id, action, observation }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#modalRequest').modal('hide');

                if (type == 'retire') {
                    if (statusElement) {
                        switch (action) {
                            case 'review':
                                statusElement.innerHTML = '<span class="status-badge" data-status="3"><span class="badge bg-info text-white"><i class="fa-duotone fa-solid fa-magnifying-glass"></i> En revisión</span></span>';
                                break;
                            case 'approve':
                                statusElement.innerHTML = '<span class="status-badge" data-status="2"><span class="badge bg-success"><i class="fa-duotone fa-solid fa-check-double"></i> <?= translate('approved'); ?></span></span>';
                                break;
                            case 'refuse':
                            case 'cancel':
                                statusElement.innerHTML = '<span class="status-badge" data-status="0"><span class="badge bg-danger"><i class="fa-duotone fa-solid fa-xmark"></i> <?= translate('rejected'); ?></span></span>';
                                break;
                        }
                    }

                    if (typeof updateWalletUI === 'function' && data.wallet_withdraw !== undefined) {
                        updateWalletUI();
                    }

                    Toastify({
                        text: data.message || "Operación procesada exitosamente",
                        duration: 3500,
                        gravity: "top",
                        position: "right",
                        style: { background: (action === 'refuse' || action === 'cancel') ? "#dc3545" : (action === 'review' ? "#0dcaf0" : "#198754") },
                        stopOnFocus: true
                    }).showToast();
                } else if (type == 'deposit') {
                    switch (action) {
                        case 'approve':
                        case 'verify':
                            if (statusElement) {
                                statusElement.innerHTML = '<span class="status-badge" data-status="2"><span class="badge bg-success"><i class="fa-duotone fa-solid fa-check-double"></i> <?= translate('approved'); ?></span></span>';
                            }
                            Toastify({
                                text: "<?= translate('pay approved successfully'); ?>",
                                duration: 3000,
                                gravity: "top",
                                position: "right",
                                style: { background: "#198754" },
                                stopOnFocus: true
                            }).showToast();
                            break;
                        case 'refuse':
                            if (statusElement) {
                                statusElement.innerHTML = '<span class="status-badge" data-status="0"><span class="badge bg-danger"><i class="fa-duotone fa-solid fa-xmark"></i> <?= translate('rejected'); ?></span></span>';
                            }
                            Toastify({
                                text: "<?= translate('pay refused successfully'); ?>",
                                duration: 3000,
                                gravity: "top",
                                position: "right",
                                style: { background: "#dc3545" },
                                stopOnFocus: true
                            }).showToast();
                            break;
                    }
                }
            } else {
                Toastify({
                    text: data.error || "Ocurrió un error al procesar la solicitud",
                    duration: 3500,
                    gravity: "top",
                    position: "right",
                    style: { background: "#dc3545" },
                    stopOnFocus: true
                }).showToast();
                console.error('error updating status:', data.error);
            }
        })
        .catch(error => {
            console.error('request error:', error);
        });
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
</script>