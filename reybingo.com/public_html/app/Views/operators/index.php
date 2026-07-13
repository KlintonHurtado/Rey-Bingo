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
