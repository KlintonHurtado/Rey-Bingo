<div class="modal-dialog modal-dialog-centered max-w-40">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2"><i class="fa-duotone fa-arrow-down-to-bracket"></i> <?= translate('process deposit request'); ?></h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body pt-0">
            <div class="card shadow-sm p-2">
                <div class="row">
                    <div class="col-md-12 mb-1">
                        <h6 class="mb-1"><strong><?= translate('deposit details'); ?>:</strong> #<?= esc(str_pad($deposit['id'], 4, '0', STR_PAD_LEFT)) ?></h6>
                        <h6 class="mb-1"><strong><?= translate('player'); ?>:</strong> <?= esc($user['code']) ?> - <?= esc($user['firstname']) ?> <?= esc($user['lastname']) ?></h6>
                        <h6 class="mb-1"><strong><?= translate('bank'); ?>:</strong> <?= esc($deposit['bank']) ?></h6>
                    </div>

                    <div class="col-md-6">
                        <small class="mb-0"><strong><?= translate('payment method'); ?>:</strong> <?= esc(translate($deposit['method'])) ?> <br /> <strong><?= translate('amount'); ?>:</strong> <?= systemGet('currency'); ?> <?= esc($deposit['amount']) ?></small> <br />
                        <small class="mb-0"><strong><?= translate('document'); ?>:</strong> <?= esc($deposit['document']) ?> <br /> <strong><?= translate('phone'); ?>:</strong> <?= esc($deposit['phone']) ?></small> <br />
                        <small class="mb-0"><strong><?= translate('reference'); ?>:</strong> <?= esc($deposit['reference']) ?> <br /> <strong><?= translate('date'); ?>:</strong> <?= esc(date('d/m/Y', strtotime($deposit['date']))) ?></small> <br />
                        <small class="mb-0"><strong><?= translate('status'); ?>:</strong> <?= $status ?></small> 
                        <?php if ($deposit['observation'] != '') : ?>
                            <br />
                            <small class="mb-0"><strong><?= translate('observation'); ?>:</strong> <br /> <?= esc($deposit['observation']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <?php if ($deposit['voucher'] != '') : ?>
                            <img src="<?= site_url('uploads/vouchers/' . $deposit['voucher']) ?>" alt="voucher" style="width:200px; max-height:200px; cursor:pointer;" class="img-thumbnail" onclick="modalVoucher('<?= $deposit['id'] ?>');">
                        <?php endif; ?>
                    </div>

                    <?php if ((int) $deposit['status'] === 1) : ?>
                        <div class="col-md-12">
                            <div class="alert alert-warning text-center py-2 px-3 mb-2 small">
                                <strong>Este depósito aún no está acreditado.</strong><br>
                                Los <strong><?= systemGet('currency'); ?> <?= number_format((float) $deposit['amount'], 2); ?></strong> se sumarán al saldo del jugador <u>solo cuando presione Aprobar</u>.
                                <br>
                                Saldo actual: <strong><?= systemGet('currency'); ?> <?= number_format(wallet_total($user), 2); ?></strong>
                                → Tras aprobar: <strong><?= systemGet('currency'); ?> <?= number_format(wallet_total($user) + (float) $deposit['amount'], 2); ?></strong>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (! empty($userStats)) : ?>
                        <div class="col-md-12">
                            <p class="small text-muted mb-2 text-center">Historial del jugador (solo depósitos ya aprobados)</p>
                            <?= view('users/partials/accreditation_user_stats', ['userStats' => $userStats]) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($deposit['status'] == 1) : ?>
                        <div class="col-md-12 mb-1">
                            <label for="observation" class="form-label"><?= translate('observation'); ?></label>
                            <textarea class="form-control form-control-lg form-bingo" name="observation" id="observation" rows="2" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('observation')); ?>"></textarea>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($deposit['status'] == 1) : ?>
                <div class="col-md-12 text-center">
                    <button type="submit" class="btn btn-primary w-25 btn-bingo inline mt-2" onclick="statusSubmit('<?= $type ?>', '<?= $deposit['id'] ?>', 'approve');"><?= translate('approve'); ?></button>
                    <button type="submit" class="btn btn-primary w-25 btn-bingo inline mt-2" onclick="statusSubmit('<?= $type ?>', '<?= $deposit['id'] ?>', 'refuse');"><?= translate('refuse'); ?></button>
                </div>

                <hr />

                <div class="text-center">
                    <small class="text-muted d-block">Saldo actual del jugador</small>
                    <?= translate('available in wallet'); ?> <?= systemGet('currency'); ?>
                    <strong class="available-wallet"><?= number_format(wallet_total($user), 2) ?></strong>
                    <div class="small text-muted mt-1">
                        Recarga: <?= number_format($user['wallet_recharge'], 2) ?> ·
                        Retiro: <?= number_format($user['wallet_withdraw'], 2) ?> ·
                        Bono: <?= number_format($user['wallet_bonus'], 2) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    function statusSubmit(type, id, action) {
        const statusElement = document.getElementById(`${type}-${id}`);
        const observation = document.getElementById('observation').value;
        if (!statusElement) {
            console.error(`element with ID ${type}-${id} not found`);
            return;
        }

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
                if (type == 'deposit') {
                    switch (action) {
                        case 'approve':

                            $('#modalRequest').modal('hide');

                            statusElement.innerHTML = '<span class="status-badge" data-status="1"><span class="badge bg-success"><i class="fa-duotone fa-solid fa-check-double"></i> <?= translate('approved'); ?></span></span>';

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

                            $('#modalRequest').modal('hide');

                            statusElement.innerHTML = '<span class="status-badge" data-status="0"><span class="badge bg-danger"><i class="fa-duotone fa-solid fa-xmark"></i> <?= translate('rejected'); ?></span></span>';

                            Toastify({
                                text: "<?= translate('pay refused successfully'); ?>",
                                duration: 3000,
                                gravity: "top",
                                position: "right",
                                style: { background: "#198754" },
                                stopOnFocus: true
                            }).showToast();
                            break;
                        default:
                            console.warn(`unknown action: ${action}`);
                    }
                } else if (type == 'retire') {
                    switch (action) {
                        case 'approve':

                            $('#modalRequest').modal('hide');

                            statusElement.innerHTML = '<span class="status-badge" data-status="1"><span class="badge bg-success"><i class="fa-duotone fa-solid fa-check-double"></i> <?= translate('approved'); ?></span></span>';

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

                            $('#modalRequest').modal('hide');

                            statusElement.innerHTML = '<span class="status-badge" data-status="0"><span class="badge bg-danger"><i class="fa-duotone fa-solid fa-xmark"></i> <?= translate('rejected'); ?></span></span>';

                            Toastify({
                                text: "<?= translate('pay refused successfully'); ?>",
                                duration: 3000,
                                gravity: "top",
                                position: "right",
                                style: { background: "#198754" },
                                stopOnFocus: true
                            }).showToast();
                            break;
                        default:
                            console.warn(`unknown action: ${action}`);
                    }
                }
            } else {
                if (data.refuse) {

                    $('#modalRequest').modal('hide');

                    statusElement.innerHTML = '<span class="status-badge" data-status="0"><span class="badge bg-danger"><i class="fa-duotone fa-solid fa-xmark"></i> <?= translate('rejected'); ?></span></span>';
                    Toastify({
                        text: data.error,
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: { background: "#dc3545" },
                        stopOnFocus: true
                    }).showToast();
                    console.error('error updating status:', data.error);
                } else if (data.error) {
                    Toastify({
                        text: data.error,
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: { background: "#dc3545" },
                        stopOnFocus: true
                    }).showToast();
                    console.error('error updating status:', data.error);
                } else {
                    console.error('error updating status:', data.error);
                }
            }
        })
        .catch(error => {
            console.error('request error:', error);
        });
    }
</script>