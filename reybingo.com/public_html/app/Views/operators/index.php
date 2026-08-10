<?= view('games/partials/admin_nav_cluster', [
    'activeNav' => 'operators',
    'showHome' => true,
    'showStatistics' => false,
    'showUsers' => false,
]) ?>

<a class="btn btn-small btn-logout" href="<?= site_url('logout'); ?>"><i class="fa-duotone fa-solid fa-arrow-right-from-arc"></i></a>

<div class="container admin-stores-page">
    <div class="row d-flex justify-content-center">
        <div class="col-md-12">
            <div class="card mb-3 admin-stores-card">
                <div class="card-body p-3">
                    <div class="admin-stores-header d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div class="flex-grow-1">
                            <h5 class="mb-1"><i class="fa-duotone fa-solid fa-user-tie"></i> <?= translate('operator management'); ?></h5>
                            <p class="text-muted small mb-0"><?= translate('manage operators and their points of sale'); ?></p>
                        </div>
                        <button type="button" class="btn btn-primary btn-modal-add text-white stores-add-btn flex-shrink-0" onclick="operatorAdd();">
                            <i class="fa-duotone fa-solid fa-plus"></i>
                        </button>
                    </div>

                    <div class="table-responsive" id="operators-list">
                        <?= view('operators/list', ['operators' => $operators ?? []]); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalOperatorPay" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header pb-2">
                <h6 class="modal-title" id="operator-pay-modal-title">
                    <i class="fa-duotone fa-solid fa-money-bill-wave text-success me-1"></i>
                    <span>Pagar a Operador</span>
                </h6>
                <button class="btn-close me-1" type="button" data-bs-dismiss="modal" aria-label="close"><i class="fa-duotone fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body pt-0">
                <div class="mb-3 p-3 rounded" style="background: rgba(25, 135, 84, 0.08); border: 1px solid rgba(25, 135, 84, 0.2);">
                    <div class="mb-2">
                        <span class="text-muted small d-block">Operador:</span>
                        <strong id="operator-pay-name" class="fs-6">-</strong>
                    </div>
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">Cédula:</span>
                        <strong id="operator-pay-document" class="text-dark">-</strong>
                    </div>
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">Saldo disponible actual:</span>
                        <strong id="operator-pay-balance" class="text-success">USD 0.00</strong>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted">Ganancias acumuladas:</span>
                        <strong id="operator-pay-earnings" class="text-primary">USD 0.00</strong>
                    </div>
                </div>

                <input type="hidden" id="operator-pay-id" value="">
                
                <div class="mb-3">
                    <label for="operator-pay-amount" class="form-label font-weight-bold">Monto a Pagar / Cargar Saldo</label>
                    <div class="input-group">
                        <span class="input-group-text"><?= systemGet('currency'); ?></span>
                        <input type="number" step="0.01" min="0.01" class="form-control form-control-lg form-bingo" id="operator-pay-amount" placeholder="0.00" autocomplete="off">
                    </div>
                    <small id="operator-pay-amount-error" class="text-danger d-none"></small>
                </div>

                <div class="d-flex justify-content-center mt-3">
                    <button type="button" class="btn btn-success btn-bingo w-50 py-2" id="operator-pay-submit-button">
                        <i class="fa-duotone fa-solid fa-check me-1"></i> Confirmar Pago
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function operatorRefreshList() {
        $.get('<?= site_url('users/operatorsListGet') ?>', function(html) {
            $('#operators-list').html(html);
        });
    }

    function operatorAdd() {
        $('#modalOperator').load('<?= site_url('users/addOperator') ?>', function() {
            $('#modalOperator').modal('show');
        });
    }

    function operatorEdit(operatorId) {
        $('#modalOperator').load('<?= site_url('users/addOperator/') ?>' + operatorId, function() {
            $('#modalOperator').modal('show');
        });
    }

    function operatorPay(operatorId, name, code, documentNum, balance, earnings) {
        const currency = '<?= esc(systemGet('currency'), 'js'); ?>';
        $('#operator-pay-id').val(operatorId);
        $('#operator-pay-name').text(name + (code ? ' (' + code + ')' : ''));
        $('#operator-pay-document').text(documentNum || '-');
        $('#operator-pay-balance').text(currency + ' ' + parseFloat(balance).toFixed(2));
        $('#operator-pay-earnings').text(currency + ' ' + parseFloat(earnings).toFixed(2));
        $('#operator-pay-amount').val('').removeClass('is-invalid');
        $('#operator-pay-amount-error').addClass('d-none').text('');
        $('#modalOperatorPay').modal('show');
    }

    $(document).on('click', '#operator-pay-submit-button', function() {
        const $btn = $(this);
        const operatorId = $('#operator-pay-id').val();
        const amount = parseFloat($('#operator-pay-amount').val());

        $('#operator-pay-amount-error').addClass('d-none').text('');
        $('#operator-pay-amount').removeClass('is-invalid');

        if (!amount || amount <= 0 || isNaN(amount)) {
            $('#operator-pay-amount').addClass('is-invalid');
            $('#operator-pay-amount-error').text('Ingrese un monto válido mayor a 0.').removeClass('d-none');
            return;
        }

        $btn.prop('disabled', true);
        $.post('<?= site_url('users/operatorPaySubmit'); ?>', {
            operator_id: operatorId,
            amount: amount,
            <?= csrf_token(); ?>: '<?= csrf_hash(); ?>'
        }, function(res) {
            if (res && res.success) {
                $('#modalOperatorPay').modal('hide');
                operatorRefreshList();
                Toastify({
                    text: res.message || 'Pago realizado con éxito.',
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    style: { background: '#198754' }
                }).showToast();
            } else {
                const msg = (res && res.message) ? res.message : 'Error al procesar el pago.';
                $('#operator-pay-amount').addClass('is-invalid');
                $('#operator-pay-amount-error').text(msg).removeClass('d-none');
                Toastify({
                    text: msg,
                    duration: 3500,
                    gravity: 'top',
                    position: 'right',
                    style: { background: '#dc3545' }
                }).showToast();
            }
        }, 'json').fail(function() {
            Toastify({
                text: 'Error en la respuesta del servidor.',
                duration: 3500,
                gravity: 'top',
                position: 'right',
                style: { background: '#dc3545' }
            }).showToast();
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });

    function operatorDeactivate(operatorId, activate) {
        const title = activate ? '<?= esc(translate('activate operator'), 'js'); ?>' : '<?= esc(translate('deactivate operator'), 'js'); ?>';
        const text = activate
            ? '<?= esc(translate('are you sure you want to activate this operator?'), 'js'); ?>'
            : '<?= esc(translate('are you sure you want to deactivate this operator?'), 'js'); ?>';

        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<?= esc(translate('yes'), 'js'); ?>',
            cancelButtonText: '<?= esc(translate('cancel'), 'js'); ?>'
        }).then(function(result) {
            if (!result.isConfirmed) {
                return;
            }

            $.post('<?= site_url('users/operatorDeactivate') ?>', {
                <?= csrf_token() ?>: '<?= csrf_hash() ?>',
                operator_id: operatorId,
                status: activate ? 1 : 2
            }, function(response) {
                if (response.success) {
                    operatorRefreshList();
                    Toastify({
                        text: response.message,
                        duration: 3000,
                        gravity: 'top',
                        position: 'right',
                        style: { background: '#198754' },
                        stopOnFocus: true
                    }).showToast();
                }
            }, 'json');
        });
    }

    function operatorDelete(operatorId) {
        Swal.fire({
            title: '<?= esc(translate('delete operator'), 'js'); ?>',
            text: '<?= esc(translate('are you sure you want to delete this operator?'), 'js'); ?>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<?= esc(translate('yes'), 'js'); ?>',
            cancelButtonText: '<?= esc(translate('cancel'), 'js'); ?>'
        }).then(function(result) {
            if (!result.isConfirmed) {
                return;
            }

            $.post('<?= site_url('users/operatorDelete') ?>', {
                <?= csrf_token() ?>: '<?= csrf_hash() ?>',
                operator_id: operatorId
            }, function(response) {
                if (response.success) {
                    operatorRefreshList();
                    Toastify({
                        text: response.message,
                        duration: 3000,
                        gravity: 'top',
                        position: 'right',
                        style: { background: '#198754' },
                        stopOnFocus: true
                    }).showToast();
                }
            }, 'json');
        });
    }
</script>
