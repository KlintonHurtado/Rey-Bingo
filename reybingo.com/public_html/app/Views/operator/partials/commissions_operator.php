<?php
$operatorCommissions = $operatorCommissions ?? [];
$currency = esc(systemGet('currency') ?? '$');
$user = $user ?? [];
$operatorId = (int) ($user['id'] ?? session()->get('id') ?? 0);

// Cargar desglose inicial
$breakdown = function_exists('bingo_fetch_operator_detailed_commissions_breakdown')
    ? bingo_fetch_operator_detailed_commissions_breakdown($operatorId)
    : ['stats' => [], 'items' => [], 'stores' => []];

$stats = $breakdown['stats'] ?? [];
$items = $breakdown['items'] ?? [];
$stores = $breakdown['stores'] ?? ($stores ?? []);

$ggrRate = (float) ($stats['ggr']['rate'] ?? 0) * 100;
$recRate = (float) ($stats['recharge']['rate'] ?? 0) * 100;
$withRate = (float) ($stats['withdraw']['rate'] ?? 0) * 100;

// Tasas globales PV configuradas en Financiero
$globalPvGgrRate = (float) (systemGet('rateStoreGgrAffiliate') ?? systemGet('rateStoreGgrCommission') ?? 0) * 100;
$globalPvRecRate = (float) (systemGet('rateStoreCommission') ?? 0) * 100;
$globalPvWithRate = (float) (systemGet('rateStorePrizeCommission') ?? 0) * 100;

$ggrMarginPct = max(0, $ggrRate - $globalPvGgrRate);
$recMarginPct = max(0, $recRate - $globalPvRecRate);
$withMarginPct = max(0, $withRate - $globalPvWithRate);

$ggrOpEarned = (float) ($stats['ggr']['operator_earned'] ?? 0);
$recOpEarned = (float) ($stats['recharge']['operator_earned'] ?? 0);
$withOpEarned = (float) ($stats['withdraw']['operator_earned'] ?? 0);

$ggrStoresEarned = (float) ($stats['ggr']['stores_earned'] ?? 0);
$recStoresEarned = (float) ($stats['recharge']['stores_earned'] ?? 0);
$withStoresEarned = (float) ($stats['withdraw']['stores_earned'] ?? 0);

$totalProfit = (float) ($stats['total_operator_profit'] ?? ($ggrOpEarned + $recOpEarned + $withOpEarned));
?>
<div class="operator-pane-inner operator-pane-inner-commissions" id="operator-commissions-main-root">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
        <div class="d-flex align-items-center gap-2">
            <div class="operator-panel-pane-icon operator-panel-pane-icon-commissions" style="width: 36px; height: 36px; font-size: 1.1rem;">
                <i class="fa-duotone fa-solid fa-chart-line"></i>
            </div>
            <div>
                <h5 class="mb-0 fw-bold text-dark" style="font-size: 1.05rem;"><?= translate('operator commissions panel'); ?></h5>
                <p class="small text-muted mb-0" style="font-size: 0.78rem;">Ganancias del Operador calculadas sobre el margen diferencial de cada Punto de Venta.</p>
            </div>
        </div>
        <div>
            <button type="button" class="btn btn-sm btn-success" id="btn-export-op-commissions" onclick="exportOperatorCommissions();" style="padding: 4px 10px; font-size: 0.82rem;">
                <i class="fa-duotone fa-solid fa-file-excel me-1"></i> Descargar Excel
            </button>
        </div>
    </div>

    <!-- 3 Tarjetas Compactas de Resumen con Margen Diferencial -->
    <div class="row g-2 mb-2">
        <!-- 1. Tasa GGR Afiliados -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm p-2 h-100" style="border-radius: 10px; background: linear-gradient(135deg, rgba(255,193,7,0.12) 0%, rgba(255,193,7,0.02) 100%); border-left: 3.5px solid #ffc107 !important;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-uppercase fw-bold text-muted" style="font-size: 0.70rem; letter-spacing: 0.3px;">Tasa GGR Afiliados</span>
                    <i class="fa-duotone fa-solid fa-chart-pie text-warning" style="font-size: 1rem;"></i>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-1">
                    <span class="fw-bold text-dark" style="font-size: 1.15rem; line-height: 1;"><?= number_format($ggrRate, 2); ?>%</span>
                    <span class="badge bg-warning-subtle text-dark border border-warning fw-semibold py-0 px-1" style="font-size: 0.68rem;" title="Margen diferencial base configurado en Financiero">
                        Dif: +<?= number_format($ggrMarginPct, 2); ?>%
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-1 border-top border-warning-subtle" style="font-size: 0.72rem;">
                    <div>
                        <span class="text-muted">Tiendas:</span>
                        <strong class="text-secondary"><?= $currency; ?> <span id="op-comm-stat-ggr-stores"><?= number_format($ggrStoresEarned, 2); ?></span></strong>
                    </div>
                    <div class="text-end">
                        <span class="text-success fw-semibold">Operador:</span>
                        <strong class="text-success">+<?= $currency; ?> <span id="op-comm-stat-ggr-profit"><?= number_format($ggrOpEarned, 2); ?></span></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Tasa Recargas -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm p-2 h-100" style="border-radius: 10px; background: linear-gradient(135deg, rgba(13,202,240,0.12) 0%, rgba(13,202,240,0.02) 100%); border-left: 3.5px solid #0dcaf0 !important;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-uppercase fw-bold text-muted" style="font-size: 0.70rem; letter-spacing: 0.3px;">Tasa Recargas</span>
                    <i class="fa-duotone fa-solid fa-mobile-screen text-info" style="font-size: 1rem;"></i>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-1">
                    <span class="fw-bold text-dark" style="font-size: 1.15rem; line-height: 1;"><?= number_format($recRate, 2); ?>%</span>
                    <span class="badge bg-info-subtle text-info-emphasis border border-info fw-semibold py-0 px-1" style="font-size: 0.68rem;" title="Margen diferencial base configurado en Financiero">
                        Dif: +<?= number_format($recMarginPct, 2); ?>%
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-1 border-top border-info-subtle" style="font-size: 0.72rem;">
                    <div>
                        <span class="text-muted">Tiendas:</span>
                        <strong class="text-secondary"><?= $currency; ?> <span id="op-comm-stat-rec-stores"><?= number_format($recStoresEarned, 2); ?></span></strong>
                    </div>
                    <div class="text-end">
                        <span class="text-info fw-semibold">Operador:</span>
                        <strong class="text-info">+<?= $currency; ?> <span id="op-comm-stat-rec-profit"><?= number_format($recOpEarned, 2); ?></span></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Tasa Retiros -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm p-2 h-100" style="border-radius: 10px; background: linear-gradient(135deg, rgba(220,53,69,0.12) 0%, rgba(220,53,69,0.02) 100%); border-left: 3.5px solid #dc3545 !important;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-uppercase fw-bold text-muted" style="font-size: 0.70rem; letter-spacing: 0.3px;">Tasa Retiros</span>
                    <i class="fa-duotone fa-solid fa-money-bill-transfer text-danger" style="font-size: 1rem;"></i>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-1">
                    <span class="fw-bold text-dark" style="font-size: 1.15rem; line-height: 1;"><?= number_format($withRate, 2); ?>%</span>
                    <span class="badge bg-danger-subtle text-danger border border-danger fw-semibold py-0 px-1" style="font-size: 0.68rem;" title="Margen diferencial base configurado en Financiero">
                        Dif: +<?= number_format($withMarginPct, 2); ?>%
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-1 border-top border-danger-subtle" style="font-size: 0.72rem;">
                    <div>
                        <span class="text-muted">Tiendas:</span>
                        <strong class="text-secondary"><?= $currency; ?> <span id="op-comm-stat-with-stores"><?= number_format($withStoresEarned, 2); ?></span></strong>
                    </div>
                    <div class="text-end">
                        <span class="text-danger fw-semibold">Operador:</span>
                        <strong class="text-danger">+<?= $currency; ?> <span id="op-comm-stat-with-profit"><?= number_format($withOpEarned, 2); ?></span></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros de Comisiones -->
    <div class="store-movements-filters-bar mb-3 p-3" style="background: rgba(98, 54, 255, 0.04); border: 1px solid rgba(98, 54, 255, 0.12); border-radius: 14px;">
        <form id="form-filter-op-commissions" onsubmit="return false;">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-sm-6 col-md-2">
                    <label for="op-comm-filter-date-from" class="form-label text-dark fw-semibold mb-1" style="font-size: 0.82rem; width: 100%; padding-left: 0; margin: 0 0 4px 0;">Fecha Desde</label>
                    <input type="date" class="form-control store-filter-input" id="op-comm-filter-date-from">
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label for="op-comm-filter-date-to" class="form-label text-dark fw-semibold mb-1" style="font-size: 0.82rem; width: 100%; padding-left: 0; margin: 0 0 4px 0;">Fecha Hasta</label>
                    <input type="date" class="form-control store-filter-input" id="op-comm-filter-date-to">
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="op-comm-filter-store" class="form-label text-dark fw-semibold mb-1" style="font-size: 0.82rem; width: 100%; padding-left: 0; margin: 0 0 4px 0;">Punto de Venta</label>
                    <select class="form-select form-control store-filter-input" id="op-comm-filter-store">
                        <option value="all">Todos los Puntos de Venta</option>
                        <option value="operator">Directo Operador</option>
                        <?php foreach ($stores ?? [] as $st) : ?>
                            <option value="<?= (int) $st['id'] ?>"><?= esc($st['business_name'] ?: ($st['firstname'] . ' ' . $st['lastname'])) ?> (<?= esc($st['code'] ?: $st['username']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label for="op-comm-filter-rate" class="form-label text-dark fw-semibold mb-1" style="font-size: 0.82rem; width: 100%; padding-left: 0; margin: 0 0 4px 0;">Tipo de Tasa</label>
                    <select class="form-select form-control store-filter-input" id="op-comm-filter-rate">
                        <option value="all">Todas las tasas</option>
                        <option value="ggr">Solo GGR Afiliados</option>
                        <option value="recharge">Solo Recargas</option>
                        <option value="withdraw">Solo Retiros / Premios</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="op-comm-filter-search" class="form-label text-dark fw-semibold mb-1" style="font-size: 0.82rem; width: 100%; padding-left: 0; margin: 0 0 4px 0;">Buscar (PV / Ref)</label>
                    <div class="d-flex gap-1">
                        <input type="text" class="form-control store-filter-input flex-grow-1" id="op-comm-filter-search" placeholder="Punto de venta o ref..." autocomplete="off">
                        <button type="button" class="btn btn-primary" onclick="applyOperatorCommissionsFilter();" title="Buscar / Filtrar" style="background: #6236ff; border-color: #6236ff; border-radius: 10px; padding: 6px 14px; min-width: 42px;">
                            <i class="fa-duotone fa-solid fa-magnifying-glass"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetOperatorCommissionsFilter();" title="Limpiar Filtros" style="border-radius: 10px; padding: 6px 12px; min-width: 38px;">
                            <i class="fa-duotone fa-solid fa-rotate-left"></i>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Indicador de Carga -->
    <div id="operator-commissions-loading" class="text-center py-4 d-none">
        <i class="fa-duotone fa-solid fa-spinner fa-spin fs-2 text-primary"></i>
        <p class="small text-muted mt-2">Calculando comisiones y diferenciales...</p>
    </div>

    <!-- Tabla Detallada de Comisiones con Diferencial -->
    <div id="operator-commissions-table-container">
        <?= view('operator/partials/commissions_operator_table', [
            'items' => $items,
            'stats' => $stats,
            'currency' => $currency
        ]); ?>
    </div>
</div>

<script type="text/javascript">
    function getOperatorCommissionsFilterParams() {
        return {
            date_from: $('#op-comm-filter-date-from').val() || '',
            date_to: $('#op-comm-filter-date-to').val() || '',
            store_id: $('#op-comm-filter-store').val() || 'all',
            rate_type: $('#op-comm-filter-rate').val() || 'all',
            search: $('#op-comm-filter-search').val() || ''
        };
    }

    window.applyOperatorCommissionsFilter = function() {
        const params = getOperatorCommissionsFilterParams();
        $('#operator-commissions-loading').removeClass('d-none');
        $('#operator-commissions-table-container').addClass('opacity-50');

        $.ajax({
            url: '<?= site_url('operator/operatorCommissionsGet'); ?>',
            method: 'GET',
            data: params,
            dataType: 'json',
            success: function(res) {
                if (res && res.html) {
                    $('#operator-commissions-table-container').html(res.html).removeClass('opacity-50');
                }
                if (res && res.stats) {
                    const st = res.stats;
                    const formatNum = function(num) {
                        return parseFloat(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    };
                    if (st.ggr) {
                        $('#op-comm-stat-ggr-stores').text(formatNum(st.ggr.stores_earned));
                        $('#op-comm-stat-ggr-profit').text(formatNum(st.ggr.operator_earned));
                    }
                    if (st.recharge) {
                        $('#op-comm-stat-rec-stores').text(formatNum(st.recharge.stores_earned));
                        $('#op-comm-stat-rec-profit').text(formatNum(st.recharge.operator_earned));
                    }
                    if (st.withdraw) {
                        $('#op-comm-stat-with-stores').text(formatNum(st.withdraw.stores_earned));
                        $('#op-comm-stat-with-profit').text(formatNum(st.withdraw.operator_earned));
                    }
                }
            },
            error: function() {
                Toastify({
                    text: 'Error al consultar comisiones del operador.',
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    style: { background: '#dc3545' }
                }).showToast();
                $('#operator-commissions-table-container').removeClass('opacity-50');
            },
            complete: function() {
                $('#operator-commissions-loading').addClass('d-none');
            }
        });
    };

    window.resetOperatorCommissionsFilter = function() {
        $('#op-comm-filter-date-from').val('');
        $('#op-comm-filter-date-to').val('');
        $('#op-comm-filter-store').val('all');
        $('#op-comm-filter-rate').val('all');
        $('#op-comm-filter-search').val('');
        applyOperatorCommissionsFilter();
    };

    window.exportOperatorCommissions = function() {
        const params = getOperatorCommissionsFilterParams();
        const queryString = $.param(params);
        window.location.href = '<?= site_url('operator/exportOperatorCommissions'); ?>?' + queryString;
    };

    $(document).ready(function() {
        $('#op-comm-filter-store, #op-comm-filter-rate, #op-comm-filter-date-from, #op-comm-filter-date-to').on('change', function() {
            applyOperatorCommissionsFilter();
        });
        $('#op-comm-filter-search').on('keyup', function(e) {
            if (e.key === 'Enter') {
                applyOperatorCommissionsFilter();
            }
        });
    });
</script>
