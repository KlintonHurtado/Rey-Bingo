<?= view('store/partials/open', [
    'imagePath' => $imagePath,
    'walletSummary' => $walletSummary,
    'pendingPrizes' => $pendingPrizes ?? 0,
    'activeNav' => 'funding',
]) ?>

<div class="card store-panel-card h-100">
    <div class="card-body store-tab-body">
        <div class="store-tab-form">
            <h6 class="store-tab-form-title"><i class="fa-duotone fa-solid fa-pen-to-square"></i> <?= translate('request balance'); ?></h6>

            <?php echo form_open(site_url('store/balanceRequestSubmit'), ['id' => 'store-funding-form']); ?>
                <?= csrf_field() ?>
                <div class="store-form-grid">
                    <div class="store-form-field">
                        <label for="funding-bank" class="form-label"><?= translate('bingo bank'); ?></label>
                        <select class="form-control form-bingo" name="bank" id="funding-bank" onchange="storeFundingBankInfo();">
                            <option value=""><?= translate('select a'); ?> <?= strtolower(translate('bank')); ?></option>
                            <?php foreach ($banks ?? [] as $bank) : ?>
                                <option value="<?= (int) $bank['id'] ?>"><?= esc($bank['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small id="funding-bank-error" class="text-danger d-none"></small>
                    </div>
                    <div id="store-funding-bank-info" class="store-funding-bank-info d-none"></div>
                    <div class="store-form-field">
                        <label for="funding-amount" class="form-label"><?= translate('amount'); ?></label>
                        <input type="number" class="form-control form-bingo" name="amount" id="funding-amount" min="0.01" step="0.01" placeholder="0.00" autocomplete="off">
                        <small id="funding-amount-error" class="text-danger d-none"></small>
                    </div>
                    <div class="store-form-field">
                        <label for="funding-reference" class="form-label"><?= translate('reference'); ?> <span class="text-muted">(<?= translate('optional'); ?>)</span></label>
                        <input type="text" class="form-control form-bingo" name="reference" id="funding-reference" placeholder="<?= translate('reference'); ?>" autocomplete="off">
                        <small id="funding-reference-error" class="text-danger d-none"></small>
                    </div>
                    <div class="store-form-field store-voucher-field">
                        <label class="form-label" for="funding-voucher-file"><?= translate('voucher'); ?></label>
                        <div class="store-voucher-upload cover position-relative">
                            <div id="funding-voucher-placeholder" class="store-voucher-placeholder">
                                <i class="fa-duotone fa-solid fa-receipt"></i>
                                <span><?= translate('enter a'); ?> <?= strtolower(translate('voucher')); ?></span>
                            </div>
                            <img src="" alt="<?= translate('voucher'); ?>" id="funding-voucher-preview" class="store-voucher-preview d-none">
                            <label for="funding-voucher-file" class="btn btn-sm btn-primary position-absolute top-0 end-0 m-2 img-button" title="<?= translate('voucher'); ?>"><i class="fa-duotone fa-solid fa-plus"></i></label>
                            <input type="file" id="funding-voucher-file" accept="image/*" class="d-none" onchange="storeFundingVoucherPreview(event)">
                            <button type="button" id="funding-voucher-remove" class="btn btn-sm btn-primary position-absolute bottom-0 end-0 m-2 img-button d-none" onclick="storeFundingVoucherRemove()" title="<?= translate('delete'); ?>"><i class="fa-duotone fa-trash"></i></button>
                            <input type="hidden" name="voucher" id="funding-voucher" value="">
                        </div>
                        <small id="funding-voucher-error" class="text-danger d-none"></small>
                    </div>
                </div>
                <div class="store-form-actions">
                    <button type="submit" class="btn btn-primary btn-bingo" id="store-funding-btn">
                        <i class="fa-duotone fa-solid fa-paper-plane"></i> <?= translate('request balance'); ?>
                    </button>
                </div>
            <?php echo form_close(); ?>
        </div>

        <div class="store-tab-history">
            <h6 class="store-tab-history-title"><i class="fa-duotone fa-solid fa-list"></i> <?= translate('balance requests history'); ?></h6>
            <div class="store-table-wrap" id="store-funding-list">
                <?= view('store/fundinglist', ['fundingRequests' => $fundingRequests ?? []]); ?>
            </div>
        </div>
    </div>
</div>

<?= view('store/partials/close') ?>

<?= view('store/partials/scripts_common') ?>

<script type="text/javascript">
    function storeRefreshFunding() {
        $.get('<?= site_url('store/fundingListGet') ?>', function(html) {
            $('#store-funding-list').html(html);
        });
    }

    function storeFundingBankInfo() {
        const bankId = document.getElementById('funding-bank').value;
        const infoBankDiv = document.getElementById('store-funding-bank-info');

        if (!bankId) {
            infoBankDiv.innerHTML = '';
            infoBankDiv.classList.add('d-none');
            return;
        }

        fetch(`<?= site_url('payments/infobankGet') ?>/${bankId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('<?= esc(translate('error getting data'), 'js'); ?>');
                }
                return response.json();
            })
            .then(data => {
                infoBankDiv.innerHTML = `
                    <div class="card shadow-sm p-3 store-bank-info-card">
                        <div class="d-flex align-items-center mb-2">
                            <div class="store-bank-info-logo">${data.logo_url}</div>
                            <div class="ps-2 flex-grow-1">
                                <h6 class="mb-0"><strong>${data.bank}</strong></h6>
                            </div>
                        </div>
                        <div class="store-bank-info-details">
                            <div class="store-bank-info-row">
                                <small><strong><?= translate('account'); ?>:</strong> ${data.account}</small>
                            </div>
                            <div class="store-bank-info-row">
                                <small><strong><?= translate('holder'); ?>:</strong> ${data.holder}</small>
                            </div>
                            <div class="store-bank-info-row">
                                <small><strong><?= translate('document'); ?>:</strong> ${data.document || '-'}</small>
                            </div>
                            <div class="store-bank-info-row">
                                <small><strong><?= translate('phone'); ?>:</strong> ${data.phone || '-'}</small>
                            </div>
                        </div>
                    </div>
                `;
                infoBankDiv.classList.remove('d-none');
            })
            .catch(() => {
                infoBankDiv.innerHTML = '';
                infoBankDiv.classList.add('d-none');
            });
    }

    function storeFundingVoucherPreview(event) {
        const file = event.target.files[0];
        if (!file) {
            return;
        }

        const reader = new FileReader();
        reader.onload = function() {
            const preview = document.getElementById('funding-voucher-preview');
            const placeholder = document.getElementById('funding-voucher-placeholder');
            preview.src = reader.result;
            preview.classList.remove('d-none');
            placeholder.classList.add('d-none');
            document.getElementById('funding-voucher').value = reader.result;
            document.getElementById('funding-voucher-remove').classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }

    function storeFundingVoucherRemove() {
        const preview = document.getElementById('funding-voucher-preview');
        const placeholder = document.getElementById('funding-voucher-placeholder');
        preview.src = '';
        preview.classList.add('d-none');
        placeholder.classList.remove('d-none');
        document.getElementById('funding-voucher').value = '';
        document.getElementById('funding-voucher-file').value = '';
        document.getElementById('funding-voucher-remove').classList.add('d-none');
    }

    $(function() {
        $('#store-funding-form').on('submit', function(e) {
            e.preventDefault();
            storeClearErrors('#store-funding-form');

            const $btn = $('#store-funding-btn');
            $btn.prop('disabled', true);

            $.ajax({
                url: '<?= site_url('store/balanceRequestSubmit') ?>',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        storeShowToast(response.message, 'success');
                        $('#funding-amount').val('');
                        $('#funding-reference').val('');
                        $('#funding-bank').val('');
                        storeFundingVoucherRemove();
                        $('#store-funding-bank-info').addClass('d-none').html('');
                        storeRefreshFunding();
                    } else if (response.errors) {
                        $.each(response.errors, function(field, message) {
                            $('#funding-' + field + '-error').text(message).removeClass('d-none');
                            $('#funding-' + field).addClass('is-invalid');
                        });
                    } else if (response.message) {
                        storeShowToast(response.message, 'error');
                    }
                },
                error: function() {
                    storeShowToast('<?= esc(translate('there was an error in the request to the server.'), 'js'); ?>', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });
    });
</script>
