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

                    <div class="store-sidebar-stats mt-3 mb-3">
                        <div class="store-balance-sidebar">
                            <span class="store-balance-label">Saldo Disponible del Operador</span>
                            <?php $opBalanceVal = function_exists('wallet_recharge_balance') ? wallet_recharge_balance($user) : (float) ($user['wallet'] ?? 0); ?>
                            <strong class="store-balance-amount" id="operator-header-balance" data-operator-balance="<?= esc(number_format($opBalanceVal, 2, '.', ''), 'attr'); ?>"><?= systemGet('currency'); ?> <?= number_format($opBalanceVal, 2) ?></strong>
                        </div>
                    </div>

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
                                data-operator-tab="account-details"
                            >
                                <i class="fa-duotone fa-solid fa-list-check"></i>
                                <span>Detalles y Movimientos</span>
                            </button>
                        </li>
                        <!--
                        <li class="nav-item">
                            <button
                                type="button"
                                class="nav-link operator-panel-tab operator-tool-tab"
                                data-operator-tab="balance-request"
                            >
                                <i class="fa-duotone fa-solid fa-hand-holding-dollar"></i>
                                <span>Pedir Saldo al Administrador</span>
                            </button>
                        </li>
                        -->
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
                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-info btn-bingo w-100 mt-2 d-flex align-items-center justify-content-center gap-2 py-2"
                                                        onclick="opGoToStoreDetails(<?= $storeId; ?>)"
                                                        style="font-size: 0.85rem; font-weight: 600;"
                                                    >
                                                        <i class="fa-duotone fa-solid fa-list-check"></i>
                                                        Ver detalles de movimientos
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
                        'user' => $user ?? [],
                        'stores' => $stores ?? [],
                        'operatorCommissions' => $operatorCommissions ?? [],
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

            <div
                class="card store-panel-card operator-panel-pane"
                id="operator-pane-balance-request"
            >
                <div class="card-body operator-panel-pane-body">
                    <div class="operator-pane-inner operator-pane-inner-full">
                        <div class="operator-panel-pane-head mb-4">
                            <div class="operator-panel-pane-icon">
                                <i class="fa-duotone fa-solid fa-hand-holding-dollar"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">Pedir Saldo al Administrador</h5>
                                <p class="small text-muted mb-0">Solicite recarga de saldo al administrador completando el formulario y adjuntando el comprobante de pago.</p>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-12 col-lg-5">
                                <div class="card p-3 shadow-sm border-0 bg-light">
                                    <h6 class="fw-bold mb-3"><i class="fa-duotone fa-solid fa-pen-to-square me-1"></i> Nueva Solicitud de Saldo</h6>
                                    <form id="operator-balance-request-form" enctype="multipart/form-data">
                                        <?= csrf_field() ?>
                                        <div class="mb-3">
                                            <label for="op-balance-bank" class="form-label small fw-semibold">Banco de depósito</label>
                                            <select class="form-select form-bingo" name="bank" id="op-balance-bank" required>
                                                <option value="">Seleccione un banco...</option>
                                                <?php foreach ($banks ?? [] as $bank) : ?>
                                                    <option value="<?= (int) $bank['id']; ?>"><?= esc($bank['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small id="op-balance-bank-error" class="text-danger d-none"></small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="op-balance-amount" class="form-label small fw-semibold">Monto a solicitar (<?= systemGet('currency'); ?>)</label>
                                            <input type="number" step="0.01" min="0.01" class="form-control form-bingo" name="amount" id="op-balance-amount" placeholder="0.00" required>
                                            <small id="op-balance-amount-error" class="text-danger d-none"></small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="op-balance-reference" class="form-label small fw-semibold">Nº Referencia <span class="text-muted">(opcional)</span></label>
                                            <input type="text" class="form-control form-bingo" name="reference" id="op-balance-reference" placeholder="Ej: 12345678">
                                            <small id="op-balance-reference-error" class="text-danger d-none"></small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Comprobante de Pago</label>
                                            <div class="store-voucher-upload cover position-relative text-center p-3 border rounded bg-white" style="min-height:120px;">
                                                <div id="op-voucher-placeholder" class="store-voucher-placeholder">
                                                    <i class="fa-duotone fa-solid fa-receipt fs-2 text-muted mb-2"></i><br>
                                                    <span class="small text-muted">Subir imagen de comprobante</span>
                                                </div>
                                                <img src="" alt="Comprobante" id="op-voucher-preview" class="store-voucher-preview d-none img-fluid rounded" style="max-height:150px;">
                                                <label for="op-voucher-file" class="btn btn-sm btn-primary position-absolute top-0 end-0 m-2" title="Seleccionar archivo"><i class="fa-duotone fa-solid fa-plus"></i></label>
                                                <input type="file" id="op-voucher-file" accept="image/*" class="d-none" onchange="opVoucherPreview(event)">
                                                <button type="button" id="op-voucher-remove" class="btn btn-sm btn-danger position-absolute bottom-0 end-0 m-2 d-none" onclick="opVoucherRemove()" title="Eliminar"><i class="fa-duotone fa-trash"></i></button>
                                                <input type="hidden" name="voucher" id="op-voucher-input" value="">
                                            </div>
                                            <small id="op-voucher-error" class="text-danger d-none"></small>
                                        </div>

                                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" id="op-balance-submit-btn">
                                            <i class="fa-duotone fa-solid fa-paper-plane me-1"></i> Enviar Solicitud de Saldo
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="col-12 col-lg-7">
                                <div class="card p-3 shadow-sm border-0">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold mb-0"><i class="fa-duotone fa-solid fa-list me-1"></i> Historial de Solicitudes de Saldo</h6>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="opRefreshBalanceRequests()">
                                            <i class="fa-duotone fa-solid fa-arrows-rotate me-1"></i> Actualizar
                                        </button>
                                    </div>
                                    <div id="op-balance-requests-list">
                                        <?= view('operator/partials/balance_history', ['operatorDeposits' => $operatorDeposits ?? []]); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="card store-panel-card operator-panel-pane"
                id="operator-pane-account-details"
            >
                <div class="card-body p-3 store-movements-scroll-body" style="flex: 1; min-height: 0; overflow-y: auto; overflow-x: hidden;">
                    <!-- Encabezado de la Sección -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="operator-panel-pane-icon">
                                <i class="fa-duotone fa-solid fa-clock-rotate-left"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold text-dark">Historial Completo de Movimientos</h5>
                                <small class="text-muted">Consulta todas las recargas, retiros, transferencias y movimientos de saldo de tu cuenta y tus Puntos de Venta (Las comisiones se gestionan en su respectiva sección).</small>
                            </div>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-success" id="btn-export-op-movements" onclick="exportOperatorMovements();">
                                <i class="fa-duotone fa-solid fa-file-excel me-1"></i> Descargar Excel / CSV
                            </button>
                        </div>
                    </div>

                    <!-- Tarjetas de Resumen Estadístico Operativo del Operador -->
                    <div class="row g-2 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; background: linear-gradient(135deg, rgba(13,110,253,0.08) 0%, rgba(13,110,253,0.02) 100%); border-left: 4px solid #0d6efd !important;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Saldo Disponible</small>
                                        <h5 class="mb-0 fw-bold text-primary"><?= systemGet('currency'); ?> <?= number_format((float) ($opBalanceVal ?? 0), 2); ?></h5>
                                        <small class="text-muted">Saldo Operativo</small>
                                    </div>
                                    <div class="text-primary fs-3">
                                        <i class="fa-duotone fa-solid fa-wallet"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; background: linear-gradient(135deg, rgba(13,202,240,0.08) 0%, rgba(13,202,240,0.02) 100%); border-left: 4px solid #0dcaf0 !important;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Total Recargas Realizadas</small>
                                        <h5 class="mb-0 fw-bold text-info"><?= systemGet('currency'); ?> <span id="op-stat-recharge">0.00</span></h5>
                                        <small class="text-muted">Jugadores y PVs</small>
                                    </div>
                                    <div class="text-info fs-3">
                                        <i class="fa-duotone fa-solid fa-mobile-screen"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; background: linear-gradient(135deg, rgba(220,53,69,0.08) 0%, rgba(220,53,69,0.02) 100%); border-left: 4px solid #dc3545 !important;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Total Retiros Pagados</small>
                                        <h5 class="mb-0 fw-bold text-danger"><?= systemGet('currency'); ?> <span id="op-stat-prize">0.00</span></h5>
                                        <small class="text-muted">Premios y Retiros en Efectivo</small>
                                    </div>
                                    <div class="text-danger fs-3">
                                        <i class="fa-duotone fa-solid fa-money-bill-transfer"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; background: linear-gradient(135deg, rgba(25,135,84,0.08) 0%, rgba(25,135,84,0.02) 100%); border-left: 4px solid #198754 !important;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Acreditaciones / Ingresos</small>
                                        <h5 class="mb-0 fw-bold text-success"><?= systemGet('currency'); ?> <span id="op-stat-credits">0.00</span></h5>
                                        <small class="text-muted">Fondos Admin y Retiros PV</small>
                                    </div>
                                    <div class="text-success fs-3">
                                        <i class="fa-duotone fa-solid fa-hand-holding-dollar"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Barra de Filtros con selector de Puntos de Venta -->
                    <div class="store-movements-filters-bar mb-3 p-3" style="background: rgba(98, 54, 255, 0.04); border: 1px solid rgba(98, 54, 255, 0.12); border-radius: 14px;">
                        <form id="form-filter-op-movements" onsubmit="return false;">
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-sm-6 col-md-2">
                                    <label for="op-filter-date-from" class="form-label text-dark fw-semibold mb-1" style="font-size: 0.82rem; width: 100%; padding-left: 0; margin: 0 0 4px 0;">Fecha Desde</label>
                                    <input type="date" class="form-control store-filter-input" id="op-filter-date-from">
                                </div>
                                <div class="col-12 col-sm-6 col-md-2">
                                    <label for="op-filter-date-to" class="form-label text-dark fw-semibold mb-1" style="font-size: 0.82rem; width: 100%; padding-left: 0; margin: 0 0 4px 0;">Fecha Hasta</label>
                                    <input type="date" class="form-control store-filter-input" id="op-filter-date-to">
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label for="op-filter-store" class="form-label text-dark fw-semibold mb-1" style="font-size: 0.82rem; width: 100%; padding-left: 0; margin: 0 0 4px 0;">Punto de Venta</label>
                                    <select class="form-select form-control store-filter-input" id="op-filter-store">
                                        <option value="all">Todos los Puntos de Venta</option>
                                        <option value="operator">Directo Operador</option>
                                        <?php foreach ($stores ?? [] as $st) : ?>
                                            <option value="<?= (int) $st['id'] ?>"><?= esc($st['business_name'] ?: ($st['firstname'] . ' ' . $st['lastname'])) ?> (<?= esc($st['code'] ?: $st['username']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-md-2">
                                    <label for="op-filter-type" class="form-label text-dark fw-semibold mb-1" style="font-size: 0.82rem; width: 100%; padding-left: 0; margin: 0 0 4px 0;">Tipo Movimiento</label>
                                    <select class="form-select form-control store-filter-input" id="op-filter-type">
                                        <option value="all">Todos los movimientos</option>
                                        <option value="recharge">Recargas a Jugadores</option>
                                        <option value="recharge_store">Recargas a Puntos de Venta</option>
                                        <option value="retire">Pagos de Retiros</option>
                                        <option value="credit">Acreditaciones / Retiros de PV</option>
                                        <option value="debit">Débitos / Ajustes</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-md-3">
                                    <label for="op-filter-search" class="form-label text-dark fw-semibold mb-1" style="font-size: 0.82rem; width: 100%; padding-left: 0; margin: 0 0 4px 0;">Buscar (Cédula / Ref / Nombre)</label>
                                    <div class="d-flex gap-1">
                                        <input type="text" class="form-control store-filter-input flex-grow-1" id="op-filter-search" placeholder="Cédula, nombre o código..." autocomplete="off">
                                        <button type="button" class="btn btn-primary" onclick="applyOperatorMovementsFilter();" title="Buscar / Filtrar" style="background: #6236ff; border-color: #6236ff; border-radius: 10px; padding: 6px 14px; min-width: 42px;">
                                            <i class="fa-duotone fa-solid fa-magnifying-glass"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="resetOperatorMovementsFilter();" title="Limpiar Filtros" style="border-radius: 10px; padding: 6px 12px; min-width: 38px;">
                                            <i class="fa-duotone fa-solid fa-rotate-left"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Indicador de Carga -->
                    <div id="operator-movements-loading" class="text-center py-4 d-none">
                        <i class="fa-duotone fa-solid fa-spinner fa-spin fs-2 text-primary"></i>
                        <p class="small text-muted mt-2">Cargando movimientos...</p>
                    </div>

                    <!-- Contenedor de la Tabla -->
                    <div id="operator-movements-container"></div>
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
                <div class="mb-3 p-2 rounded bg-light border">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">Saldo Disponible del Operador:</span>
                        <strong id="operator-store-balance-modal-operator" class="text-primary">0.00</strong>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted">Saldo Disponible del Punto de venta:</span>
                        <strong id="operator-store-balance-modal-current" class="text-dark">0.00</strong>
                    </div>
                </div>
                <div class="alert alert-info py-2 px-3 small mb-2" id="operator-store-balance-hint"></div>
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
    window.opVoucherPreview = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(evt) {
            $('#op-voucher-input').val(evt.target.result);
            $('#op-voucher-preview').attr('src', evt.target.result).removeClass('d-none');
            $('#op-voucher-placeholder').addClass('d-none');
            $('#op-voucher-remove').removeClass('d-none');
        };
        reader.readAsDataURL(file);
    };

    window.opVoucherRemove = function() {
        $('#op-voucher-file').val('');
        $('#op-voucher-input').val('');
        $('#op-voucher-preview').attr('src', '').addClass('d-none');
        $('#op-voucher-placeholder').removeClass('d-none');
        $('#op-voucher-remove').addClass('d-none');
    };

    window.opRefreshBalanceRequests = function() {
        $.get('<?= site_url('operator/balanceListGet') ?>', function(html) {
            $('#op-balance-requests-list').html(html);
        });
    };

    function getOperatorFilterParams() {
        return {
            date_from: $('#op-filter-date-from').val(),
            date_to: $('#op-filter-date-to').val(),
            store_id: $('#op-filter-store').val(),
            type: $('#op-filter-type').val(),
            search: $('#op-filter-search').val()
        };
    }

    window.applyOperatorMovementsFilter = function() {
        const params = getOperatorFilterParams();
        $('#operator-movements-loading').removeClass('d-none');
        $('#operator-movements-container').addClass('opacity-50');

        $.ajax({
            url: '<?= site_url('operator/movementsListGet'); ?>',
            method: 'GET',
            data: params,
            success: function(html) {
                $('#operator-movements-container').html(html).removeClass('opacity-50');
                const $w = $('#operator-movements-table-wrapper');
                if ($w.length) {
                    const r = parseFloat($w.data('total-recharges') || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const p = parseFloat($w.data('total-retires') || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const c = parseFloat($w.data('total-credits') || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    $('#op-stat-recharge').text(r);
                    $('#op-stat-prize').text(p);
                    $('#op-stat-credits').text(c);
                }
            },
            error: function() {
                Toastify({
                    text: 'Error al consultar movimientos del operador.',
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    style: { background: '#dc3545' }
                }).showToast();
                $('#operator-movements-container').removeClass('opacity-50');
            },
            complete: function() {
                $('#operator-movements-loading').addClass('d-none');
            }
        });
    };

    window.resetOperatorMovementsFilter = function() {
        $('#op-filter-date-from').val('');
        $('#op-filter-date-to').val('');
        $('#op-filter-store').val('all');
        $('#op-filter-type').val('all');
        $('#op-filter-search').val('');
        applyOperatorMovementsFilter();
    };

    window.exportOperatorMovements = function() {
        const params = getOperatorFilterParams();
        const queryString = $.param(params);
        window.location.href = '<?= site_url('operator/exportMovements'); ?>?' + queryString;
    };

    window.opGoToStoreDetails = function(storeId) {
        $('[data-operator-tab="account-details"]').trigger('click');
        $('#op-filter-store').val(storeId);
        applyOperatorMovementsFilter();
    };

    $(document).on('click', '[data-operator-tab="account-details"]', function() {
        applyOperatorMovementsFilter();
    });

    $(function() {
        $('#op-filter-search').on('keyup', function(e) {
            if (e.key === 'Enter') {
                applyOperatorMovementsFilter();
            }
        });
        $('#op-filter-store, #op-filter-type, #op-filter-date-from, #op-filter-date-to').on('change', function() {
            applyOperatorMovementsFilter();
        });
    });

    $(document).on('submit', '#operator-balance-request-form', function(e) {
        e.preventDefault();
        $('#op-balance-bank-error, #op-balance-amount-error, #op-balance-reference-error, #op-voucher-error').addClass('d-none').text('');
        
        var $btn = $('#op-balance-submit-btn');
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Enviando...');
        
        $.ajax({
            url: '<?= site_url('operator/balanceRequestSubmit') ?>',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fa-duotone fa-solid fa-paper-plane me-1"></i> Enviar Solicitud de Saldo');
                if (res.success) {
                    Toastify({
                        text: res.message || 'Solicitud enviada con éxito',
                        duration: 4000,
                        gravity: "top",
                        position: "right",
                        style: { background: "#198754" }
                    }).showToast();
                    $('#operator-balance-request-form')[0].reset();
                    opVoucherRemove();
                    opRefreshBalanceRequests();
                } else {
                    if (res.errors) {
                        if (res.errors.bank) $('#op-balance-bank-error').removeClass('d-none').text(res.errors.bank);
                        if (res.errors.amount) $('#op-balance-amount-error').removeClass('d-none').text(res.errors.amount);
                        if (res.errors.reference) $('#op-balance-reference-error').removeClass('d-none').text(res.errors.reference);
                        if (res.errors.voucher) $('#op-voucher-error').removeClass('d-none').text(res.errors.voucher);
                    }
                    if (res.message) {
                        Toastify({
                            text: res.message,
                            duration: 4000,
                            gravity: "top",
                            position: "right",
                            style: { background: "#dc3545" }
                        }).showToast();
                    }
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="fa-duotone fa-solid fa-paper-plane me-1"></i> Enviar Solicitud de Saldo');
                Toastify({
                    text: "Error al enviar la solicitud al servidor",
                    duration: 4000,
                    gravity: "top",
                    position: "right",
                    style: { background: "#dc3545" }
                }).showToast();
            }
        });
    });

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
            const storeBalance = parseFloat($btn.data('balance')) || 0;
            const operatorBalance = parseFloat($('#operator-header-balance').attr('data-operator-balance')) || 0;

            $('#operator-store-balance-store-id').val(storeId);
            $('#operator-store-balance-action').val(action);
            $('#operator-store-balance-amount').val('').removeClass('is-invalid');
            $('#operator-store-balance-amount-error').addClass('d-none').text('');
            $('#operator-store-balance-modal-title span').text(action === 'remove' ? labelsStoreBalance.remove : labelsStoreBalance.add);
            $('#operator-store-balance-modal-store').text(storeName);
            $('#operator-store-balance-modal-current').text(currencyLabel + ' ' + storeBalance.toFixed(2));
            $('#operator-store-balance-modal-operator').text(currencyLabel + ' ' + operatorBalance.toFixed(2));

            if (action === 'add') {
                $('#operator-store-balance-hint').html('<strong>Límite a añadir:</strong> máximo ' + currencyLabel + ' ' + operatorBalance.toFixed(2) + ' (Saldo disponible del Operador).');
            } else {
                $('#operator-store-balance-hint').html('<strong>Límite a retirar:</strong> máximo ' + currencyLabel + ' ' + storeBalance.toFixed(2) + ' (Saldo disponible del Punto de venta).');
            }

            $('#modalOperatorStoreBalance').modal('show');
        });

        $('#operator-store-balance-submit').on('click', function() {
            const $btn = $(this);
            const storeId = $('#operator-store-balance-store-id').val();
            const action = $('#operator-store-balance-action').val();
            const amount = parseFloat($('#operator-store-balance-amount').val());
            const storeBalance = parseFloat($('.js-operator-store-balance-btn[data-store-id="' + storeId + '"]').first().attr('data-balance')) || 0;
            const operatorBalance = parseFloat($('#operator-header-balance').attr('data-operator-balance')) || 0;

            $('#operator-store-balance-amount-error').addClass('d-none').text('');
            $('#operator-store-balance-amount').removeClass('is-invalid');

            if (!amount || amount <= 0 || isNaN(amount)) {
                $('#operator-store-balance-amount').addClass('is-invalid');
                $('#operator-store-balance-amount-error').text(labelsStoreBalance.confirmRequired).removeClass('d-none');
                return;
            }

            if (action === 'add' && amount > operatorBalance + 0.00001) {
                $('#operator-store-balance-amount').addClass('is-invalid');
                $('#operator-store-balance-amount-error').text('Saldo insuficiente del operador. Saldo disponible: ' + currencyLabel + ' ' + operatorBalance.toFixed(2)).removeClass('d-none');
                return;
            }

            if (action === 'remove' && amount > storeBalance + 0.00001) {
                $('#operator-store-balance-amount').addClass('is-invalid');
                $('#operator-store-balance-amount-error').text('El monto a retirar excede el saldo disponible del Punto de venta (' + currencyLabel + ' ' + storeBalance.toFixed(2) + ')').removeClass('d-none');
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
                    const newStoreBalance = parseFloat(res.balance);
                    const formattedStoreBalance = (isNaN(newStoreBalance) ? 0 : newStoreBalance).toFixed(2);
                    const $cardBalance = $('.js-operator-store-balance[data-store-id="' + storeId + '"] .js-operator-store-balance-value');
                    $cardBalance.text(formattedStoreBalance);
                    $('.js-operator-store-balance-btn[data-store-id="' + storeId + '"]').attr('data-balance', formattedStoreBalance);
                    $('.js-operator-store-info-btn').each(function() {
                        const $infoBtn = $(this);
                        const $card = $infoBtn.closest('.store-list-subcard');
                        if ($card.find('.js-operator-store-balance-btn[data-store-id="' + storeId + '"]').length) {
                            $infoBtn.attr('data-balance', formattedStoreBalance);
                        }
                    });

                    if (res.operatorBalance !== undefined) {
                        const newOpBalance = parseFloat(res.operatorBalance);
                        const formattedOpBalance = (isNaN(newOpBalance) ? 0 : newOpBalance).toFixed(2);
                        $('#operator-header-balance').attr('data-operator-balance', formattedOpBalance).text(currencyLabel + ' ' + formattedOpBalance);
                    }

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
