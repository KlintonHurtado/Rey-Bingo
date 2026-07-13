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
                        <?php if (bingo_ggr_affiliate_active()) : ?>
                        <li class="nav-item">
                            <button
                                type="button"
                                class="nav-link operator-panel-tab operator-tool-tab"
                                data-operator-tab="ggr-rates"
                            >
                                <i class="fa-duotone fa-solid fa-percent"></i>
                                <span><?= translate('operator ggr rates configuration'); ?></span>
                            </button>
                        </li>
                        <?php endif; ?>
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
                                <p class="small text-muted mb-0"><?= translate('operator enter store hint'); ?></p>
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
                                                            <strong style="color: #6236ff; font-size: 1.15rem;">
                                                                <?= systemGet('currency'); ?>
                                                                <?= number_format((float) ($wallet['recharge'] ?? 0), 2); ?>
                                                            </strong>
                                                        </div>
                                                        <div class="p-2 rounded" style="background: rgba(25, 135, 84, 0.05); border: 1px solid rgba(25, 135, 84, 0.08);">
                                                            <span class="d-block text-muted small" style="font-size: 0.75rem;"><?= translate('store commission rate'); ?></span>
                                                            <strong style="color: #198754; font-size: 1.15rem;">
                                                                <?= number_format(bingo_store_commission_rate($store) * 100, 2) ?>%
                                                            </strong>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="p-3 pt-0 mt-auto">
                                                    <a
                                                        href="<?= site_url('operator/enterStore/' . $storeId); ?>"
                                                        class="btn btn-primary btn-bingo w-100 d-flex align-items-center justify-content-center gap-2 py-2"
                                                        style="font-size: 0.9rem; font-weight: 600;"
                                                    >
                                                        <i class="fa-duotone fa-solid fa-arrow-right-to-bracket"></i>
                                                        <?= translate('manage point of sale'); ?>
                                                    </a>
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

            <?php if (bingo_ggr_affiliate_active()) : ?>
            <div
                class="card store-panel-card operator-panel-pane"
                id="operator-pane-ggr-rates"
            >
                <div class="card-body operator-panel-pane-body">
                    <?= view('operator/partials/ggr_rates', [
                        'stores' => $stores ?? [],
                        'user' => $user ?? [],
                    ]); ?>
                </div>
            </div>
            <?php endif; ?>

            <div
                class="card store-panel-card operator-panel-pane <?= empty($stores) ? 'is-active' : '' ?>"
                id="operator-pane-affiliate"
            >
                <div class="card-body operator-panel-pane-body">
                    <div class="operator-pane-inner operator-pane-inner-affiliate">
                        <div class="operator-panel-pane-head">
                            <div class="operator-panel-pane-icon operator-panel-pane-icon-affiliate">
                                <i class="fa-duotone fa-solid fa-link"></i>
                            </div>
                            <div>
                                <h5 class="mb-1"><?= translate('operator affiliate link'); ?></h5>
                                <p class="small text-muted mb-0"><?= translate('operator store affiliate link description'); ?></p>
                            </div>
                        </div>

                        <div class="operator-affiliate-layout">
                            <div class="operator-affiliate-qr-wrap">
                                <a
                                    href="<?= esc($affiliateLink ?? '#'); ?>"
                                    class="operator-affiliate-qr-link"
                                    title="<?= translate('create point of sale account'); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <img
                                        src="<?= site_url('operator/affiliateCode'); ?>"
                                        alt="<?= translate('operator affiliate link'); ?>"
                                        class="operator-affiliate-qr-img"
                                    >
                                </a>
                            </div>

                            <div class="operator-affiliate-content">
                                <p class="operator-affiliate-lead small text-muted mb-0">
                                    <?= translate('operator store affiliate link description'); ?>
                                </p>

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

                                <div class="operator-affiliate-actions">
                                    <a
                                        href="https://api.whatsapp.com/send?text=<?= rawurlencode('🎉 ' . translate('create point of sale account') . ' ' . APP_NAME . ' 👉 ' . ($affiliateLink ?? '')); ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="btn btn-success btn-bingo"
                                    >
                                        <i class="fa-brands fa-whatsapp"></i>
                                        <?= translate('share on whatsapp'); ?>
                                    </a>
                                </div>

                                <div class="operator-affiliate-stats">
                                    <div class="operator-affiliate-stat">
                                        <span><?= translate('affiliated stores'); ?></span>
                                        <strong><?= (int) ($referredStoresCount ?? 0); ?></strong>
                                    </div>

                                    <?php if (bingo_ggr_affiliate_active()) : ?>
                                    <?php $ggrDashboard = $ggrDashboard ?? []; ?>
                                    <div class="operator-affiliate-stat">
                                        <span><?= translate('ggr generated'); ?></span>
                                        <strong><?= systemGet('currency'); ?> <?= number_format((float) ($ggrDashboard['total_ggr'] ?? 0), 2); ?></strong>
                                    </div>
                                    <div class="operator-affiliate-stat">
                                        <span><?= translate('ggr commissions earned'); ?></span>
                                        <strong><?= systemGet('currency'); ?> <?= number_format((float) ($ggrDashboard['total_commission'] ?? 0), 2); ?></strong>
                                    </div>
                                    <div class="operator-affiliate-stat">
                                        <span><?= translate('operator ggr total rate'); ?></span>
                                        <strong><?= number_format(($ggrRate ?? 0) * 100, 2); ?>%</strong>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (bingo_ggr_affiliate_active()) : ?>
                                <p class="operator-affiliate-margin-note small text-muted mb-0">
                                    <strong><?= translate('operator ggr margin note'); ?>:</strong>
                                    <?= translate('operator ggr margin hint'); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php if (bingo_ggr_affiliate_active()) : ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php endif; ?>
<script type="text/javascript">
    $(function() {
        let currentPage = 1;
        const pageSize = 6;

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

        const operatorStoresGgrChartEl = document.getElementById('operator-stores-ggr-chart');
        if (operatorStoresGgrChartEl) {
            const storesChartData = <?= json_encode(($storesCommissions['chart'] ?? [])); ?>;
            if (storesChartData.length) {
                new Chart(operatorStoresGgrChartEl, {
                    type: 'line',
                    data: {
                        labels: storesChartData.map(r => r.label),
                        datasets: [
                            {
                                label: 'GGR',
                                data: storesChartData.map(r => Number(r.ggr) || 0),
                                borderColor: '#0d6efd',
                                backgroundColor: 'rgba(13, 110, 253, 0.12)',
                                tension: 0.3,
                                pointRadius: 5,
                                fill: false
                            },
                            {
                                label: '<?= esc(translate('commission'), 'js'); ?>',
                                data: storesChartData.map(r => Number(r.commission) || 0),
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

        window.saveOperatorStoreGgrRate = function(storeId) {
            const input = document.getElementById('operator-store-ggr-' + storeId);
            if (!input) {
                return;
            }
            const data = {
                store_id: storeId,
                ggr_rate: input.value
            };
            data['<?= csrf_token() ?>'] = '<?= csrf_hash() ?>';
            $.post('<?= site_url('operator/updateStoreGgrRate'); ?>', data, function(res) {
                Toastify({
                    text: res.message || 'OK',
                    duration: 3500,
                    gravity: 'top',
                    position: 'right',
                    style: { background: res.success ? '#198754' : '#dc3545' }
                }).showToast();
                if (res.success) {
                    const row = document.querySelector('tr[data-store-id="' + storeId + '"]');
                    if (row && res.margin_rate !== undefined) {
                        const marginEl = row.querySelector('.operator-store-margin-value');
                        if (marginEl) {
                            marginEl.textContent = (parseFloat(res.margin_rate) * 100).toFixed(2);
                        }
                    }
                }
            }, 'json');
        };

        $(document).on('input', '.operator-store-ggr-input', function() {
            const row = $(this).closest('tr');
            const operatorTotal = <?= json_encode((float) bingo_operator_commission_rate($user ?? [])); ?>;
            let storeRate = parseFloat($(this).val());
            if (isNaN(storeRate) || $(this).val() === '') {
                storeRate = Math.min(
                    <?= json_encode((float) (systemGet('rateStoreGgrCommission') ?? 0) * 100); ?>,
                    operatorTotal * 100
                ) / 100;
            } else {
                storeRate = Math.min(storeRate / 100, operatorTotal);
            }
            const margin = Math.max(0, operatorTotal - storeRate);
            row.find('.operator-store-margin-value').text((margin * 100).toFixed(2));
        });
    });
</script>
