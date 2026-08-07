<?php
$deposit = $deposit ?? null;
$user = $user ?? null;
$storeUser = $storeUser ?? null;
$userStats = $userStats ?? null;
$type = $type ?? 'deposit';
$statusHtml = $status ?? '';

if (! is_array($deposit) || empty($deposit['id'])) {
    ?>
<div class="modal-dialog modal-dialog-centered max-w-40">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2"><i class="fa-duotone fa-arrow-down-to-bracket"></i> <?= translate('deposit'); ?></h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="alert alert-warning mb-0">Depósito no encontrado.</div>
        </div>
    </div>
</div>
    <?php
    return;
}

$depositStatus = (int) ($deposit['status'] ?? 0);
$isPending = $depositStatus === 1;
$isStoreFunding = function_exists('bingo_deposit_is_store_funding') ? bingo_deposit_is_store_funding($deposit) : false;
$modalTitle = $isPending
    ? translate('process deposit request')
    : (translate('deposit details') ?: 'Detalle del depósito');

$voucherName = trim((string) ($deposit['voucher'] ?? ''));
$voucherOk = $voucherName !== '' && function_exists('bingo_voucher_exists') && bingo_voucher_exists($voucherName);
$voucherUrl = ($voucherOk && function_exists('bingo_voucher_url')) ? bingo_voucher_url($voucherName) : '';
?>
<div class="modal-dialog modal-dialog-centered max-w-40">
    <div class="modal-content">
        <div class="modal-header pb-2">
            <h6 class="modal-title ps-2"><i class="fa-duotone fa-arrow-down-to-bracket"></i> <?= esc($modalTitle); ?></h6>
            <button class="btn-close me-1" type="button" aria-label="close" data-bs-dismiss="modal"><i class="fa-duotone fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body pt-0">
            <div class="card shadow-sm p-2">
                <div class="row">
                    <div class="col-md-12 mb-1">
                        <h6 class="mb-1"><strong><?= translate('deposit details'); ?>:</strong> #<?= esc(str_pad((string) $deposit['id'], 4, '0', STR_PAD_LEFT)) ?></h6>
                        <?php if ($isStoreFunding) : ?>
                            <h6 class="mb-1"><strong><?= translate('store'); ?>:</strong> <?= esc(is_array($user) ? bingo_store_display_name($user) : '—'); ?></h6>
                            <h6 class="mb-1 text-muted small"><?= translate('store balance request admin note'); ?></h6>
                        <?php else : ?>
                            <h6 class="mb-1"><strong><?= translate('player'); ?>:</strong>
                                <?php if (is_array($user)) : ?>
                                    <?= esc($user['code'] ?? '') ?> - <?= esc($user['firstname'] ?? '') ?> <?= esc($user['lastname'] ?? '') ?>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </h6>
                            <?php if (! empty($storeUser) && is_array($storeUser)) : ?>
                                <h6 class="mb-1"><strong><?= translate('store'); ?>:</strong> <?= esc(bingo_store_display_name($storeUser)) ?></h6>
                            <?php endif; ?>
                        <?php endif; ?>
                        <h6 class="mb-1"><strong><?= translate('bank'); ?>:</strong> <?= esc($deposit['bank'] ?? '') ?></h6>
                    </div>

                    <div class="col-md-6">
                        <small class="mb-0"><strong><?= translate('payment method'); ?>:</strong> <?= esc(translate($deposit['method'] ?? '')) ?> <br /> <strong><?= translate('amount'); ?>:</strong> <?= systemGet('currency'); ?> <?= esc($deposit['amount'] ?? '') ?></small> <br />
                        <small class="mb-0"><strong><?= translate('document'); ?>:</strong> <?= esc($deposit['document'] ?? '') ?> <br /> <strong><?= translate('phone'); ?>:</strong> <?= esc($deposit['phone'] ?? '') ?></small> <br />
                        <small class="mb-0"><strong><?= translate('reference'); ?>:</strong> <?= esc($deposit['reference'] ?? '') ?> <br /> <strong><?= translate('date'); ?>:</strong> <?= esc(! empty($deposit['date']) ? date('d/m/Y', strtotime($deposit['date'])) : '—') ?></small> <br />
                        <small class="mb-0"><strong><?= translate('status'); ?>:</strong> <?= $statusHtml ?></small>
                        <?php if (! empty($deposit['observation'])) : ?>
                            <br />
                            <small class="mb-0"><strong><?= translate('observation'); ?>:</strong> <br /> <?= esc($deposit['observation']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 text-center">
                        <?php if ($voucherOk && $voucherUrl !== '') : ?>
                            <img src="<?= esc($voucherUrl) ?>" alt="voucher" style="width:100%; max-width:240px; max-height:240px; object-fit:contain; cursor:pointer;" class="img-thumbnail bg-white" onclick="modalVoucher('<?= (int) $deposit['id'] ?>');" onerror="this.classList.add('d-none'); this.nextElementSibling && this.nextElementSibling.classList.remove('d-none');">
                            <div class="alert alert-warning py-2 px-2 small mb-0 d-none">Comprobante registrado pero el archivo no está disponible en el servidor.</div>
                            <div class="small text-muted mt-1">Clic en la imagen para ampliar</div>
                        <?php elseif ($voucherName !== '') : ?>
                            <div class="alert alert-warning py-2 px-2 small mb-0">Comprobante registrado pero el archivo no está disponible en el servidor.</div>
                        <?php else : ?>
                            <div class="alert alert-secondary py-2 px-2 small mb-0">Sin comprobante.</div>
                        <?php endif; ?>
                    </div>

                    <?php if ($isPending && is_array($user)) : ?>
                        <div class="col-md-12">
                            <div class="alert alert-warning text-center py-2 px-3 mb-2 small">
                                <?php if ($isStoreFunding) : ?>
                                    <strong><?= translate('store balance request pending note'); ?></strong><br>
                                    Los <strong><?= systemGet('currency'); ?> <?= number_format((float) $deposit['amount'], 2); ?></strong> se acreditarán al saldo del Punto de venta <u>cuando presione Aprobar</u>.
                                <?php else : ?>
                                    <strong>Este depósito aún no está acreditado.</strong><br>
                                    Los <strong><?= systemGet('currency'); ?> <?= number_format((float) $deposit['amount'], 2); ?></strong> se sumarán al saldo del jugador <u>solo cuando presione Aprobar</u>.
                                <?php endif; ?>
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

                    <?php if ($isPending) : ?>
                        <div class="col-md-12 mb-1">
                            <label for="observation" class="form-label"><?= translate('observation'); ?></label>
                            <textarea class="form-control form-control-lg form-bingo" name="observation" id="observation" rows="2" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('observation')); ?>"></textarea>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isPending) : ?>
                <div class="col-md-12 text-center">
                    <button type="button" class="btn btn-primary w-25 btn-bingo inline mt-2" onclick="statusSubmit('<?= esc($type, 'js') ?>', '<?= (int) $deposit['id'] ?>', 'approve');"><?= translate('approve'); ?></button>
                    <button type="button" class="btn btn-primary w-25 btn-bingo inline mt-2" onclick="statusSubmit('<?= esc($type, 'js') ?>', '<?= (int) $deposit['id'] ?>', 'refuse');"><?= translate('refuse'); ?></button>
                </div>

                <hr />

                <?php if (is_array($user)) : ?>
                <div class="text-center">
                    <small class="text-muted d-block">Saldo actual del jugador</small>
                    <?= translate('available in wallet'); ?> <?= systemGet('currency'); ?>
                    <strong class="available-wallet"><?= number_format(wallet_total($user), 2) ?></strong>
                    <div class="small text-muted mt-1">
                        Recarga: <?= number_format((float) ($user['wallet_recharge'] ?? 0), 2) ?> ·
                        Retiro: <?= number_format((float) ($user['wallet_withdraw'] ?? 0), 2) ?> ·
                        Bono: <?= number_format((float) ($user['wallet_bonus'] ?? 0), 2) ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($isPending) : ?>
<script type="text/javascript">
    function statusSubmit(type, id, action) {
        const statusElement = document.getElementById(`${type}-${id}`);
        const observationEl = document.getElementById('observation');
        const observation = observationEl ? observationEl.value : '';
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
                            statusElement.innerHTML = '<span class="status-badge" data-status="2"><span class="badge bg-success"><i class="fa-duotone fa-solid fa-check-double"></i> <?= translate('approved'); ?></span></span>';
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
                } else if (data.error) {
                    Toastify({
                        text: data.error,
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: { background: "#dc3545" },
                        stopOnFocus: true
                    }).showToast();
                }
            }
        })
        .catch(error => {
            console.error('request error:', error);
        });
    }
</script>
<?php endif; ?>
