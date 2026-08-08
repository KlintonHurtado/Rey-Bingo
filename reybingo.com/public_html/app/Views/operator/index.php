<a class="btn btn-small btn-profile" href="<?= site_url('profile'); ?>"><img src="<?= $imagePath ?>" alt="img"></a>
<a class="btn btn-small btn-lock" href="<?= site_url('password'); ?>"><i class="fa-duotone fa-solid fa-lock"></i></a>
<a class="btn btn-small btn-logout" href="<?= site_url('logout'); ?>"><i class="fa-duotone fa-solid fa-arrow-right-from-arc"></i></a>

<div class="store-panel-fit operator-panel-fit">
    <div class="store-panel-shell">
        <aside class="store-panel-sidebar">
            <div class="card store-panel-card store-panel-sidebar-card">
                <div class="card-body">
                    <h5 class="store-sidebar-title mb-2"><i class="fa-duotone fa-solid fa-user-tie"></i> <?= translate('operator panel'); ?></h5>
                    <p class="small text-muted mb-0 operator-panel-sidebar-note"><?= translate('operator panel description'); ?></p>

                    <div class="store-panel-sidebar-divider"></div>

                    <ul class="nav store-panel-tabs store-panel-nav operator-panel-tabs mb-0">
                        <li class="nav-item">
                            <button
                                type="button"
                                class="nav-link operator-panel-tab operator-tool-tab active"
                                data-operator-tab="stores-list"
                            >
                                <i class="fa-duotone fa-solid fa-store"></i>
                                <span><?= translate('points of sale'); ?></span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button
                                type="button"
                                class="nav-link operator-panel-tab operator-tool-tab"
                                data-operator-tab="commissions-operator"
                            >
                                <i class="fa-duotone fa-solid fa-chart-line"></i>
                                <span><?= translate('operator commissions panel'); ?></span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button
                                type="button"
                                class="nav-link operator-panel-tab operator-tool-tab"
                                data-operator-tab="withdraw-operator"
                            >
                                <i class="fa-duotone fa-solid fa-arrow-up-from-bracket"></i>
                                <span><?= translate('withdraw operator earnings'); ?></span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button
                                type="button"
                                class="nav-link operator-panel-tab operator-tool-tab"
                                data-operator-tab="commissions-stores"
                            >
                                <i class="fa-duotone fa-solid fa-coins"></i>
                                <span><?= translate('stores commissions panel'); ?></span>
                            </button>
                        </li>
                        <li class="nav-item operator-panel-tab-affiliate">
                            <button
                                type="button"
                                class="nav-link operator-panel-tab operator-tool-tab"
                                data-operator-tab="affiliate"
                            >
                                <i class="fa-duotone fa-solid fa-link"></i>
                                <span><?= translate('operator affiliate link'); ?></span>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>

        <main class="store-panel-page-content operator-panel-page-content">
            <div
                class="card store-panel-card operator-panel-pane is-active"
                id="operator-pane-stores-list"
            >
                <div class="card-body operator-panel-pane-body">
                    <div class="operator-pane-inner operator-pane-inner-stores-list">
                        <div class="operator-panel-pane-head mb-4">
                            <div class="operator-panel-pane-icon">
                                <i class="fa-duotone fa-solid fa-store"></i>
                            </div>
                            <div>
                                <h5 class="mb-1"><?= translate('points of sale'); ?></h5>
                                <p class="small text-muted mb-0"><?= translate('operator store balance actions hint'); ?></p>
                            </div>
                        </div>

                        <?php if (! empty($stores)) : ?>
                            <div class="operator-stores-search-wrap mb-4" style="max-width: 480px;">
                                <label class="visually-hidden" for="operator-stores-search"><?= translate('search point of sale'); ?></label>
                                <div class="operator-stores-search">
                                    <i class="fa-duotone fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                    <input
                                        type="search"
                                        class="form-control form-bingo operator-stores-search-input"
                                        id="operator-stores-search"
                                        placeholder="<?= translate('search point of sale'); ?>"
                                        autocomplete="off"
                                    >
                                </div>
                            </div>

                            <div class="operator-stores-grid-scroll flex-grow-1">
                                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3" id="operator-stores-grid">
                                    <?php foreach ($stores as $store) : ?>
                                        <?php
                                        $storeId = (int) $store['id'];
                                        $wallet = wallet_summary_payload($store);
                                        $searchText = strtolower(trim(
                                            ($store['business_name'] ?? '') . ' '
                                            . ($store['address_line'] ?? '') . ' '
                                            . ($store['code'] ?? '')
                                        ));
                                        ?>
                                        <div class="col operator-store-card-item" data-search="<?= esc($searchText); ?>">
                                            <div class="card h-100 store-list-subcard shadow-sm" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 16px; overflow: hidden; display: flex; flex-direction: column;">
                                                <div class="card-body p-3 flex-grow-1">
                                                    <div class="d-flex align-items-center gap-2 mb-3">
                                                        <div class="d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; background: rgba(98, 54, 255, 0.1); border-radius: 12px; color: #6236ff; font-size: 1.2rem;">
                                                            <i class="fa-duotone fa-solid fa-store"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-bold" style="font-size: 1rem;"><?= esc($store['business_name'] ?? '-'); ?></h6>
                                                            <small class="text-muted" style="font-size: 0.8rem;"><?= esc($store['code'] ?? ''); ?></small>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex flex-column gap-2 mb-3">
                                                        <div class="p-2 rounded" style="background: rgba(98, 54, 255, 0.05); border: 1px solid rgba(98, 54, 255, 0.08);">
                                                            <span class="d-block text-muted small" style="font-size: 0.75rem;"><?= translate('available store balance'); ?></span>
                                                            <strong class="js-operator-store-balance" data-store-id="<?= $storeId; ?>" style="color: #6236ff; font-size: 1.15rem;">
                                                                <?= systemGet('currency'); ?>
                                                                <span class="js-operator-store-balance-value"><?= number_format((float) ($wallet['recharge'] ?? 0), 2); ?></span>
                                                            </strong>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="p-3 pt-0 mt-auto">
                                                    <?php
                                                    $ggrPct = bingo_store_ggr_commission_rate($store) * 100;
                                                    $rechargePct = bingo_store_commission_rate($store) * 100;
                                                    $prizePct = bingo_store_prize_commission_rate($store) * 100;
                                                    $ggrIsCustom = isset($store['ggr_commission_rate']) && $store['ggr_commission_rate'] !== null && $store['ggr_commission_rate'] !== '';
                                                    $rechargeIsCustom = isset($store['store_commission_rate']) && $store['store_commission_rate'] !== null && $store['store_commission_rate'] !== '';
                                                    $prizeIsCustom = isset($store['store_prize_commission_rate']) && $store['store_prize_commission_rate'] !== null && $store['store_prize_commission_rate'] !== '';
                                                    ?>
                                                    <div class="d-flex gap-2 mb-2">
                                                        <button
                                                            type="button"
                                                            class="btn btn-success flex-fill d-flex align-items-center justify-content-center gap-1 py-2 js-operator-store-balance-btn"
                                                            style="font-size: 0.82rem; font-weight: 600;"
                                                            data-action="add"
                                                            data-store-id="<?= $storeId; ?>"
                                                            data-store-name="<?= esc($store['business_name'] ?? '-', 'attr'); ?>"
                                                            data-balance="<?= esc(number_format((float) ($wallet['recharge'] ?? 0), 2, '.', ''), 'attr'); ?>"
                                                        >
                                                            <i class="fa-duotone fa-solid fa-plus"></i>
                                                            <?= translate('add store balance'); ?>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="btn btn-danger flex-fill d-flex align-items-center justify-content-center gap-1 py-2 js-operator-store-balance-btn"
                                                            style="font-size: 0.82rem; font-weight: 600;"
                                                            data-action="remove"
                                                            data-store-id="<?= $storeId; ?>"
                                                            data-store-name="<?= esc($store['business_name'] ?? '-', 'attr'); ?>"
                                                            data-balance="<?= esc(number_format((float) ($wallet['recharge'] ?? 0), 2, '.', ''), 'attr'); ?>"
                                                        >
                                                            <i class="fa-duotone fa-solid fa-minus"></i>
                                                            <?= translate('remove store balance'); ?>
                                                        </button>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-primary btn-bingo w-100 d-flex align-items-center justify-content-center gap-2 py-2 js-operator-store-info-btn"
                                                        style="font-size: 0.9rem; font-weight: 600;"
                                                        data-name="<?= esc($store['business_name'] ?? '-', 'attr'); ?>"
                                                        data-code="<?= esc($store['code'] ?? '', 'attr'); ?>"
                                                        data-email="<?= esc($store['email'] ?? '', 'attr'); ?>"
                                                        data-address="<?= esc($store['address_line'] ?? '', 'attr'); ?>"
                                                        data-balance="<?= esc(number_format((float) ($wallet['recharge'] ?? 0), 2, '.', ''), 'attr'); ?>"
                                                        data-ggr="<?= esc(number_format($ggrPct, 2, '.', ''), 'attr'); ?>"
                                                        data-recharge="<?= esc(number_format($rechargePct, 2, '.', ''), 'attr'); ?>"
                                                        data-prize="<?= esc(number_format($prizePct, 2, '.', ''), 'attr'); ?>"
                                                        data-ggr-source="<?= $ggrIsCustom ? 'custom' : 'global'; ?>"
                                                        data-recharge-source="<?= $rechargeIsCustom ? 'custom' : 'global'; ?>"
                                                        data-prize-source="<?= $prizeIsCustom ? 'custom' : 'global'; ?>"
                                                    >
                                                        <i class="fa-duotone fa-solid fa-store"></i>
                                                        <?= translate('manage point of sale'); ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Pagination Controls -->
                            <nav aria-label="Page navigation" class="mt-3">
                                <ul class="pagination justify-content-center mb-0" id="operator-stores-pagination">
                                    <!-- Rendered dynamically via JS -->
                                </ul>
                            </nav>

                            <div class="operator-stores-search-empty text-muted mt-4 d-none">
                                <?= translate('no points of sale found'); ?>
                            </div>
                        <?php else : ?>
                            <div class="operator-empty-state text-muted py-5 text-center">
                                <i class="fa-duotone fa-solid fa-store-slash d-block mb-3" style="font-size: 3rem; opacity: 0.4;"></i>
                                <?= translate('no points of sale assigned yet'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div
                class="card store-panel-card operator-panel-pane"
                id="operator-pane-commissions-operator"
            >
                <div class="card-body operator-panel-pane-body">
                    <?= view('operator/partials/commissions_operator', [
                        'operatorCommissions' => $operatorCommissions ?? [],
                    ]); ?>
                </div>
            </div>

            <div
                class="card store-panel-card operator-panel-pane"
                id="operator-pane-withdraw-operator"
            >
                <div class="card-body operator-panel-pane-body">
                    <?= view('operator/partials/withdraw_operator', [
                        'retires' => $retires ?? [],
                        'earningsSummary' => $earningsSummary ?? [],
                        'retireEnabled' => $retireEnabled ?? false,
                        'minimumRetire' => $minimumRetire ?? 0,
                        'maximumRetire' => $maximumRetire ?? 0,
                    ]); ?>
                </div>
            </div>

            <div
                class="card store-panel-card operator-panel-pane"
                id="operator-pane-commissions-stores"
            >
                <div class="card-body operator-panel-pane-body">
                    <?= view('operator/partials/commissions_stores', [
                        'storesCommissions' => $storesCommissions ?? [],
                    ]); ?>
                </div>
            </div>

            <div
                class="card store-panel-card operator-panel-pane <?= empty($stores) ? 'is-active' : '' ?>"
                id="operator-pane-affiliate"
            >
                <div class="card-body operator-panel-pane-body">
                    <div class="operator-pane-inner operator-pane-inner-affiliate">
                        <div class="operator-panel-pane-head mb-3">
                            <div class="operator-panel-pane-icon operator-panel-pane-icon-affiliate">
                                <i class="fa-duotone fa-solid fa-link"></i>
                            </div>
                            <div>
                                <h5 class="mb-1"><?= translate('operator affiliate link'); ?></h5>
                                <p class="small text-muted mb-0"><?= translate('operator store affiliate link description'); ?></p>
                            </div>
                        </div>

                        <label class="form-label" for="operator-affiliate-link-input"><?= translate('your affiliate link'); ?></label>
                        <div class="operator-affiliate-link-row">
                            <input
                                type="text"
                                class="form-control form-bingo operator-affiliate-link-input"
                                id="operator-affiliate-link-input"
                                value="<?= esc($affiliateLink ?? ''); ?>"
                                readonly
                            >
                            <button
                                type="button"
                                class="btn btn-primary btn-bingo operator-affiliate-copy-btn"
                                id="operator-affiliate-copy-btn"
                            >
                                <i class="fa-duotone fa-solid fa-copy"></i>
                                <?= translate('copy'); ?>
                            </button>
                        </div>
                        <p
                            class="operator-affiliate-copy-feedback"
                            id="operator-affiliate-copy-feedback"
                            role="status"
                            aria-live="polite"
                        ></p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<div class="modal fade" id="modalOperatorStoreBalance" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header pb-2">
                <h6 class="modal-title" id="operator-store-balance-modal-title">
                    <i class="fa-duotone fa-solid fa-wallet"></i>
                    <span></span>
                </h6>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="close"><i class="fa-duotone fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body pt-0">
                <p class="small text-muted mb-2" id="operator-store-balance-modal-store"></p>
                <p class="small mb-3">
                    <?= translate('available store balance'); ?>:
                    <strong id="operator-store-balance-modal-current">0.00</strong>
                </p>
                <input type="hidden" id="operator-store-balance-store-id" value="">
                <input type="hidden" id="operator-store-balance-action" value="">
                <div class="mb-2">
                    <label for="operator-store-balance-amount" class="form-label"><?= translate('amount'); ?></label>
                    <input type="number" step="0.01" min="0.01" class="form-control form-control-lg form-bingo" id="operator-store-balance-amount" placeholder="0.00" autocomplete="off">
                    <small id="operator-store-balance-amount-error" class="text-danger d-none"></small>
                </div>
                <div class="d-flex justify-content-center mt-3">
                    <button type="button" class="btn btn-primary btn-bingo w-50" id="operator-store-balance-submit">
                        <?= translate('confirm'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalOperatorStoreInfo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header pb-2">
                <h6 class="modal-title">
                    <i class="fa-duotone fa-solid fa-store"></i>
                    <?= translate('manage point of sale'); ?>
                </h6>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="close"><i class="fa-duotone fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body pt-0">
                <p class="small text-muted mb-3"><?= translate('store info read only hint'); ?></p>

                <div class="mb-3 p-3 rounded" style="background: rgba(98, 54, 255, 0.06); border: 1px solid rgba(98, 54, 255, 0.1);">
                    <div class="mb-2">
                        <span class="d-block text-muted small"><?= translate('point of sale'); ?></span>
                        <strong id="operator-store-info-name">-</strong>
                    </div>
                    <div class="mb-2">
                        <span class="d-block text-muted small"><?= translate('code'); ?></span>
                        <strong id="operator-store-info-code">-</strong>
                    </div>
                    <div class="mb-2">
                        <span class="d-block text-muted small"><?= translate('email'); ?></span>
                        <strong id="operator-store-info-email">-</strong>
                    </div>
                    <div class="mb-2">
                        <span class="d-block text-muted small"><?= translate('address'); ?></span>
                        <strong id="operator-store-info-address">-</strong>
                    </div>
                    <div>
                        <span class="d-block text-muted small"><?= translate('available store balance'); ?></span>
                        <strong id="operator-store-info-balance" style="color: #6236ff;">-</strong>
                    </div>
                </div>

                <h6 class="mb-2"><?= translate('store commission rates'); ?></h6>
                <div class="row g-2">
                    <div class="col-12">
                        <div class="p-2 rounded" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);">
                            <span class="d-block text-muted small"><?= translate('store ggr affiliate rate'); ?></span>
                            <strong id="operator-store-info-ggr">0.00%</strong>
                            <small class="d-block text-muted" id="operator-store-info-ggr-source"></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 rounded" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);">
                            <span class="d-block text-muted small"><?= translate('store recharge commission rate'); ?></span>
                            <strong id="operator-store-info-recharge">0.00%</strong>
                            <small class="d-block text-muted" id="operator-store-info-recharge-source"></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 rounded" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);">
                            <span class="d-block text-muted small"><?= translate('store prize commission rate'); ?></span>
                            <strong id="operator-store-info-prize">0.00%</strong>
                            <small class="d-block text-muted" id="operator-store-info-prize-source"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (bingo_ggr_affiliate_active()) : ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php endif; ?>
<script type="text/javascript">
    $(function() {
        let currentPage = 1;
        const pageSize = 6;
        const currencyLabel = <?= json_encode((string) (systemGet('currency') ?? '')); ?>;
        const labelsStoreBalance = {
            add: <?= json_encode(translate('add store balance')); ?>,
            remove: <?= json_encode(translate('remove store balance')); ?>,
            confirmRequired: <?= json_encode(translate('amount is required')); ?>,
            rateCustom: <?= json_encode(translate('custom rate')); ?>,
            rateGlobal: <?= json_encode(translate('use global rate')); ?>,
            noData: <?= json_encode(translate('not available')); ?>,
        };

        function operatorStoreRateSourceLabel(source) {
            return source === 'custom' ? labelsStoreBalance.rateCustom : labelsStoreBalance.rateGlobal;
        }

        $(document).on('click', '.js-operator-store-info-btn', function() {
            const $btn = $(this);
            const name = $btn.data('name') || '-';
            const code = $btn.data('code') || '-';
            const email = $btn.data('email') || labelsStoreBalance.noData;
            const address = $btn.data('address') || labelsStoreBalance.noData;
            const balance = parseFloat($btn.data('balance')) || 0;
            const ggr = parseFloat($btn.data('ggr')) || 0;
            const recharge = parseFloat($btn.data('recharge')) || 0;
            const prize = parseFloat($btn.data('prize')) || 0;

            $('#operator-store-info-name').text(name);
            $('#operator-store-info-code').text(code);
            $('#operator-store-info-email').text(email);
            $('#operator-store-info-address').text(address);
            $('#operator-store-info-balance').text(currencyLabel + ' ' + balance.toFixed(2));
            $('#operator-store-info-ggr').text(ggr.toFixed(2) + '%');
            $('#operator-store-info-recharge').text(recharge.toFixed(2) + '%');
            $('#operator-store-info-prize').text(prize.toFixed(2) + '%');
            $('#operator-store-info-ggr-source').text(operatorStoreRateSourceLabel($btn.data('ggr-source')));
            $('#operator-store-info-recharge-source').text(operatorStoreRateSourceLabel($btn.data('recharge-source')));
            $('#operator-store-info-prize-source').text(operatorStoreRateSourceLabel($btn.data('prize-source')));
            $('#modalOperatorStoreInfo').modal('show');
        });

        $(document).on('click', '.js-operator-store-balance-btn', function() {
            const $btn = $(this);
            const action = $btn.data('action');
            const storeId = $btn.data('store-id');
            const storeName = $btn.data('store-name') || '';
            const balance = parseFloat($btn.data('balance')) || 0;

            $('#operator-store-balance-store-id').val(storeId);
            $('#operator-store-balance-action').val(action);
            $('#operator-store-balance-amount').val('').removeClass('is-invalid');
            $('#operator-store-balance-amount-error').addClass('d-none').text('');
            $('#operator-store-balance-modal-title span').text(action === 'remove' ? labelsStoreBalance.remove : labelsStoreBalance.add);
            $('#operator-store-balance-modal-store').text(storeName);
            $('#operator-store-balance-modal-current').text(currencyLabel + ' ' + balance.toFixed(2));
            $('#modalOperatorStoreBalance').modal('show');
        });

        $('#operator-store-balance-submit').on('click', function() {
            const $btn = $(this);
            const storeId = $('#operator-store-balance-store-id').val();
            const action = $('#operator-store-balance-action').val();
            const amount = parseFloat($('#operator-store-balance-amount').val());

            $('#operator-store-balance-amount-error').addClass('d-none').text('');
            $('#operator-store-balance-amount').removeClass('is-invalid');

            if (!amount || amount <= 0 || isNaN(amount)) {
                $('#operator-store-balance-amount').addClass('is-invalid');
                $('#operator-store-balance-amount-error').text(labelsStoreBalance.confirmRequired).removeClass('d-none');
                return;
            }

            $btn.prop('disabled', true);
            $.post('<?= site_url('operator/adjustStoreBalance'); ?>', {
                store_id: storeId,
                action: action,
                amount: amount,
                <?= csrf_token(); ?>: '<?= csrf_hash(); ?>'
            }, function(res) {
                if (res && res.success) {
                    $('#modalOperatorStoreBalance').modal('hide');
                    const newBalance = parseFloat(res.balance);
                    const formatted = (isNaN(newBalance) ? 0 : newBalance).toFixed(2);
                    const $cardBalance = $('.js-operator-store-balance[data-store-id="' + storeId + '"] .js-operator-store-balance-value');
                    $cardBalance.text(formatted);
                    $('.js-operator-store-balance-btn[data-store-id="' + storeId + '"]').attr('data-balance', formatted);
                    $('.js-operator-store-info-btn').each(function() {
                        const $infoBtn = $(this);
                        // Match by sibling balance buttons on same card
                        const $card = $infoBtn.closest('.store-list-subcard');
                        if ($card.find('.js-operator-store-balance-btn[data-store-id="' + storeId + '"]').length) {
                            $infoBtn.attr('data-balance', formatted);
                        }
                    });
                    Toastify({
                        text: res.message || 'OK',
                        duration: 3000,
                        gravity: 'top',
                        position: 'right',
                        style: { background: '#198754' }
                    }).showToast();
                } else {
                    const msg = (res && res.message) ? res.message : '<?= esc(translate('error processing request'), 'js'); ?>';
                    $('#operator-store-balance-amount').addClass('is-invalid');
                    $('#operator-store-balance-amount-error').text(msg).removeClass('d-none');
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
                    text: '<?= esc(translate('there was an error in the request to the server.'), 'js'); ?>',
                    duration: 3500,
                    gravity: 'top',
                    position: 'right',
                    style: { background: '#dc3545' }
                }).showToast();
            }).always(function() {
                $btn.prop('disabled', false);
            });
        });

        function updateOperatorStoresPagination() {
            const $search = $('#operator-stores-search');
            if (! $search.length) {
                return;
            }

            const query = $search.val().toLowerCase().trim();
            const $cards = $('.operator-store-card-item');
            
            // 1. Filter cards by search query
            let visibleCards = [];
            $cards.each(function() {
                const text = ($(this).data('search') || '').toString();
                const match = ! query || text.indexOf(query) !== -1;
                if (match) {
                    visibleCards.push($(this));
                } else {
                    $(this).addClass('d-none');
                }
            });

            const totalVisible = visibleCards.length;
            $('.operator-stores-search-empty').toggleClass('d-none', totalVisible > 0 || ! query);

            // 2. Calculate pages
            const totalPages = Math.ceil(totalVisible / pageSize) || 1;
            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            // 3. Show only cards for the active page
            const startIndex = (currentPage - 1) * pageSize;
            const endIndex = startIndex + pageSize;

            visibleCards.forEach((card, idx) => {
                if (idx >= startIndex && idx < endIndex) {
                    card.removeClass('d-none');
                } else {
                    card.addClass('d-none');
                }
            });

            // 4. Render pagination controls
            const $pagination = $('#operator-stores-pagination');
            $pagination.empty();

            if (totalPages <= 1) {
                return; // No pagination controls needed if 1 page or less
            }

            // Previous button
            const prevDisabled = currentPage === 1 ? 'disabled' : '';
            $pagination.append(`
                <li class="page-item ${prevDisabled}">
                    <button class="page-link" type="button" data-page="${currentPage - 1}" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </button>
                </li>
            `);

            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                const activeClass = i === currentPage ? 'active' : '';
                $pagination.append(`
                    <li class="page-item ${activeClass}">
                        <button class="page-link" type="button" data-page="${i}">${i}</button>
                    </li>
                `);
            }

            // Next button
            const nextDisabled = currentPage === totalPages ? 'disabled' : '';
            $pagination.append(`
                <li class="page-item ${nextDisabled}">
                    <button class="page-link" type="button" data-page="${currentPage + 1}" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </button>
                </li>
            `);
        }

        // Handle page clicks
        $(document).on('click', '#operator-stores-pagination button', function(e) {
            e.preventDefault();
            const page = parseInt($(this).data('page'));
            if (! page || $(this).closest('li').hasClass('disabled') || $(this).closest('li').hasClass('active')) {
                return;
            }
            currentPage = page;
            updateOperatorStoresPagination();
        });

        // Search input triggers pagination update
        $(document).on('input', '#operator-stores-search', function() {
            currentPage = 1;
            updateOperatorStoresPagination();
        });

        // Initialize pagination
        updateOperatorStoresPagination();

        $('.operator-panel-tab').on('click', function() {
            const target = $(this).data('operator-tab');
            if (!target) {
                return;
            }

            $('.operator-panel-tab').removeClass('active');
            $(this).addClass('active');
            $('.operator-panel-pane').removeClass('is-active');
            $('#operator-pane-' + target).addClass('is-active');
        });

        $('#operator-affiliate-copy-btn').on('click', function() {
            const linkInput = document.getElementById('operator-affiliate-link-input');
            const feedbackEl = document.getElementById('operator-affiliate-copy-feedback');
            if (!linkInput) {
                return;
            }

            const linkText = (linkInput.value || '').trim();
            if (!linkText) {
                return;
            }

            function operatorShowCopyFeedback() {
                if (!feedbackEl) {
                    return;
                }

                feedbackEl.textContent = '<?= esc(translate('link copied'), 'js'); ?>';
                feedbackEl.classList.add('is-visible');

                window.clearTimeout(feedbackEl._hideTimer);
                feedbackEl._hideTimer = window.setTimeout(function() {
                    feedbackEl.classList.remove('is-visible');
                }, 3000);
            }

            function operatorCopyFallback() {
                linkInput.select();
                linkInput.setSelectionRange(0, 99999);
                document.execCommand('copy');
                operatorShowCopyFeedback();
            }

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(linkText).then(operatorShowCopyFeedback).catch(operatorCopyFallback);
                return;
            }

            operatorCopyFallback();
        });

        <?php if (bingo_ggr_affiliate_active()) : ?>
        // Chart del panel GGR del operador (si existe canvas)
        const operatorGgrChartEl = document.getElementById('operator-ggr-chart');
        if (operatorGgrChartEl) {
            const operatorChartData = <?= json_encode(($operatorCommissions['ggr_dashboard']['chart'] ?? [])); ?>;
            if (operatorChartData.length) {
                new Chart(operatorGgrChartEl, {
                    type: 'line',
                    data: {
                        labels: operatorChartData.map(r => r.label),
                        datasets: [
                            {
                                label: 'GGR',
                                data: operatorChartData.map(r => Number(r.ggr) || 0),
                                borderColor: '#0d6efd',
                                backgroundColor: 'rgba(13, 110, 253, 0.12)',
                                tension: 0.3,
                                pointRadius: 5,
                                fill: false
                            },
                            {
                                label: '<?= esc(translate('commission'), 'js'); ?>',
                                data: operatorChartData.map(r => Number(r.commission) || 0),
                                borderColor: '#198754',
                                backgroundColor: 'rgba(25, 135, 84, 0.12)',
                                tension: 0.3,
                                pointRadius: 5,
                                fill: false
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        }
        <?php endif; ?>

        window.operatorStoresGgrChart = null;
        window.renderOperatorStoresGgrChart = function(chartData) {
            const canvas = document.getElementById('operator-stores-ggr-chart');
            if (! canvas || typeof Chart === 'undefined') {
                return;
            }
            if (window.operatorStoresGgrChart) {
                window.operatorStoresGgrChart.destroy();
                window.operatorStoresGgrChart = null;
            }
            const rows = Array.isArray(chartData) ? chartData : [];
            if (! rows.length) {
                return;
            }
            window.operatorStoresGgrChart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: rows.map(r => r.label),
                    datasets: [
                        {
                            label: 'GGR',
                            data: rows.map(r => Number(r.ggr) || 0),
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.12)',
                            tension: 0.3,
                            pointRadius: 5,
                            fill: false
                        },
                        {
                            label: '<?= esc(translate('commission'), 'js'); ?>',
                            data: rows.map(r => Number(r.commission) || 0),
                            borderColor: '#198754',
                            backgroundColor: 'rgba(25, 135, 84, 0.12)',
                            tension: 0.3,
                            pointRadius: 5,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        };

        <?php if (bingo_ggr_affiliate_active()) : ?>
        if (document.getElementById('operator-stores-ggr-chart')) {
            window.renderOperatorStoresGgrChart(<?= json_encode(($storesCommissions['chart'] ?? [])); ?>);
        }
        <?php endif; ?>

        function loadOperatorStoresCommissions(dateFrom, dateTo) {
            const $paneBody = $('#operator-pane-commissions-stores .operator-panel-pane-body');
            if (! $paneBody.length) {
                return;
            }
            $paneBody.css('opacity', '0.55');
            $.ajax({
                url: '<?= site_url('operator/storesCommissionsGet'); ?>',
                method: 'GET',
                dataType: 'json',
                data: {
                    date_from: dateFrom || '',
                    date_to: dateTo || '',
                    <?= csrf_token(); ?>: '<?= csrf_hash(); ?>'
                }
            }).done(function(res) {
                if (! res || ! res.success || ! res.html) {
                    Toastify({
                        text: (res && res.message) ? res.message : '<?= esc(translate('error processing request'), 'js'); ?>',
                        duration: 3000,
                        gravity: 'top',
                        position: 'right',
                        style: { background: '#dc3545' }
                    }).showToast();
                    return;
                }
                $paneBody.html(res.html);
                window.renderOperatorStoresGgrChart(res.chart || []);
            }).fail(function() {
                Toastify({
                    text: '<?= esc(translate('there was an error in the request to the server.'), 'js'); ?>',
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    style: { background: '#dc3545' }
                }).showToast();
            }).always(function() {
                $paneBody.css('opacity', '1');
            });
        }

        let operatorStoresFilterTimer = null;

        function applyOperatorStoresDateFilter() {
            const from = $('#operator-stores-date-from').val() || '';
            const to = $('#operator-stores-date-to').val() || '';
            if (from && to && from > to) {
                Toastify({
                    text: '<?= esc(translate('invalid date range'), 'js'); ?>',
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    style: { background: '#dc3545' }
                }).showToast();
                return;
            }
            loadOperatorStoresCommissions(from, to);
        }

        $(document).on('change', '#operator-stores-date-from, #operator-stores-date-to', function() {
            window.clearTimeout(operatorStoresFilterTimer);
            operatorStoresFilterTimer = window.setTimeout(applyOperatorStoresDateFilter, 200);
        });

        $(document).on('click', '#operator-stores-commissions-clear', function() {
            const from = '<?= date('Y-m-d', strtotime('-30 days')); ?>';
            const to = '<?= date('Y-m-d'); ?>';
            $('#operator-stores-date-from').val(from);
            $('#operator-stores-date-to').val(to);
            loadOperatorStoresCommissions(from, to);
        });
    });
</script>
