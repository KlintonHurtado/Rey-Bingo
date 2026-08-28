<?= view('games/partials/admin_nav_cluster', [
    'activeNav' => 'stores',
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
                            <h5 class="mb-1"><i class="fa-duotone fa-solid fa-store"></i> <?= translate('point of sale management'); ?></h5>
                            <p class="text-muted small mb-0"><?= translate('manage point of sale accounts'); ?></p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                            <button type="button" class="btn btn-primary btn-modal-add text-white stores-add-btn" onclick="storeAdd();">
                                <i class="fa-duotone fa-solid fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive" id="stores-list">
                        <?= view('stores/list', ['stores' => $stores ?? []]); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= view('partials/modal_commission_liquidation'); ?>

<script type="text/javascript">
    function storeRefreshList() {
        $.get('<?= site_url('users/storesListGet') ?>', function(html) {
            $('#stores-list').html(html);
        });
    }

    function storeAdd() {
        $('#modalStore').load('<?= site_url('users/addStore') ?>', function() {
            $('#modalStore').modal('show');
        });
    }

    function storeEdit(storeId) {
        $('#modalStore').load('<?= site_url('users/addStore/') ?>' + storeId, function() {
            $('#modalStore').modal('show');
        });
    }

    function storeDeactivate(storeId, activate) {
        const title = activate ? '<?= esc(translate('activate store'), 'js'); ?>' : '<?= esc(translate('deactivate store'), 'js'); ?>';
        const text = activate
            ? '<?= esc(translate('are you sure you want to activate this store?'), 'js'); ?>'
            : '<?= esc(translate('are you sure you want to deactivate this store?'), 'js'); ?>';

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

            $.post('<?= site_url('users/storeDeactivate') ?>', {
                <?= csrf_token() ?>: '<?= csrf_hash() ?>',
                store_id: storeId,
                status: activate ? 1 : 2
            }, function(response) {
                if (response.success) {
                    storeRefreshList();
                    Toastify({
                        text: response.message,
                        duration: 3000,
                        gravity: 'top',
                        position: 'right',
                        style: { background: '#198754' },
                        stopOnFocus: true
                    }).showToast();
                } else {
                    Toastify({
                        text: response.error || response.message,
                        duration: 3000,
                        gravity: 'top',
                        position: 'right',
                        style: { background: '#dc3545' },
                        stopOnFocus: true
                    }).showToast();
                }
            }, 'json');
        });
    }

    function storeDelete(storeId) {
        Swal.fire({
            title: '<?= esc(translate('delete store'), 'js'); ?>',
            text: '<?= esc(translate('are you sure you want to delete this store?'), 'js'); ?>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<?= esc(translate('yes'), 'js'); ?>',
            cancelButtonText: '<?= esc(translate('cancel'), 'js'); ?>'
        }).then(function(result) {
            if (!result.isConfirmed) {
                return;
            }

            $.post('<?= site_url('users/storeDelete') ?>', {
                <?= csrf_token() ?>: '<?= csrf_hash() ?>',
                store_id: storeId
            }, function(response) {
                if (response.success) {
                    storeRefreshList();
                    Toastify({
                        text: response.message,
                        duration: 3000,
                        gravity: 'top',
                        position: 'right',
                        style: { background: '#198754' },
                        stopOnFocus: true
                    }).showToast();
                } else {
                    Toastify({
                        text: response.error || response.message,
                        duration: 3000,
                        gravity: 'top',
                        position: 'right',
                        style: { background: '#dc3545' },
                        stopOnFocus: true
                    }).showToast();
                }
            }, 'json');
        });
    }
</script>
