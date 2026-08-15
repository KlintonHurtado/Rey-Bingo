<?= view('store/partials/open', [
    'imagePath' => $imagePath,
    'walletSummary' => $walletSummary,
    'pendingPrizes' => $pendingPrizes ?? ($pendingCount ?? 0),
    'activeNav' => 'commissions',
]) ?>

<?php
$currency = esc(systemGet('currency') ?? '$');
$storeId = (int) ($user['id'] ?? session()->get('id') ?? 0);
if ($storeId <= 0 && function_exists('bingo_get_effective_store_id')) {
    $storeId = bingo_get_effective_store_id();
}

$breakdown = function_exists('bingo_fetch_store_detailed_commissions_breakdown')
    ? bingo_fetch_store_detailed_commissions_breakdown($storeId)
    : ['stats' => [], 'items' => []];

$stats = $breakdown['stats'] ?? [];
$items = $breakdown['items'] ?? [];

$ggrRate = (float) ($stats['ggr']['rate'] ?? 0) * 100;
$recRate = (float) ($stats['recharge']['rate'] ?? 0) * 100;
$withRate = (float) ($stats['withdraw']['rate'] ?? 0) * 100;

$ggrEarned = (float) ($stats['ggr']['total_earned'] ?? 0);
$recEarned = (float) ($stats['recharge']['total_earned'] ?? 0);
$withEarned = (float) ($stats['withdraw']['total_earned'] ?? 0);

$totalCommissions = (float) ($stats['total_commissions_earned'] ?? ($ggrEarned + $recEarned + $withEarned));
?>

<div class="card store-panel-card h-100" style="min-height: 0; display: flex; flex-direction: column; overflow: hidden;">
    <div class="card-body p-3 store-movements-scroll-body" style="flex: 1; min-height: 0; overflow-y: auto; overflow-x: hidden;">
        <!-- Encabezado de Comisiones -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <div class="d-flex align-items-center gap-2">
                <div class="operator-panel-pane-icon operator-panel-pane-icon-commissions" style="width: 36px; height: 36px; font-size: 1.1rem; background: rgba(98, 54, 255, 0.12); color: #6236ff; display: flex; align-items: center; justify-content: center; border-radius: 10px;">
                    <i class="fa-duotone fa-solid fa-percent"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold text-dark" style="font-size: 1.05rem;">Comisiones del Punto de Venta</h5>
                    <p class="small text-muted mb-0" style="font-size: 0.78rem;">Comisiones generadas por GGR de afiliados, recargas realizadas y pagos de retiros en efectivo.</p>
                </div>
            </div>
            <div class="d-flex gap-1">
                <button type="button" class="btn btn-sm btn-success" id="btn-export-store-commissions" onclick="exportStoreCommissions();" style="padding: 5px 12px; font-size: 0.84rem;">
                    <i class="fa-duotone fa-solid fa-file-excel me-1"></i> Descargar Excel / CSV
                </button>
            </div>
        </div>

        <!-- 3 Tarjetas Compactas de Resumen de Tasas -->
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
                        <span class="badge bg-warning-subtle text-dark border border-warning fw-semibold py-0 px-1" style="font-size: 0.68rem;">
                            <?= (int) ($stats['ggr']['count'] ?? 0); ?> períodos
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-1 border-top border-warning-subtle" style="font-size: 0.72rem;">
                        <div>
                            <span class="text-muted">Base GGR:</span>
                            <strong class="text-secondary"><?= $currency; ?> <span id="store-stat-ggr-base"><?= number_format((float) ($stats['ggr']['total_base'] ?? 0), 2); ?></span></strong>
                        </div>
                        <div class="text-end">
                            <span class="text-success fw-semibold">Ganado:</span>
                            <strong class="text-success">+<?= $currency; ?> <span id="store-stat-ggr-earned"><?= number_format($ggrEarned, 2); ?></span></strong>
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
                        <span class="badge bg-info-subtle text-info-emphasis border border-info fw-semibold py-0 px-1" style="font-size: 0.68rem;">
                            <?= (int) ($stats['recharge']['count'] ?? 0); ?> recargas
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-1 border-top border-info-subtle" style="font-size: 0.72rem;">
                        <div>
                            <span class="text-muted">Base Recargas:</span>
                            <strong class="text-secondary"><?= $currency; ?> <span id="store-stat-rec-base"><?= number_format((float) ($stats['recharge']['total_base'] ?? 0), 2); ?></span></strong>
                        </div>
                        <div class="text-end">
                            <span class="text-info fw-semibold">Ganado:</span>
                            <strong class="text-info">+<?= $currency; ?> <span id="store-stat-rec-earned"><?= number_format($recEarned, 2); ?></span></strong>
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
                        <span class="badge bg-danger-subtle text-danger border border-danger fw-semibold py-0 px-1" style="font-size: 0.68rem;">
                            <?= (int) ($stats['withdraw']['count'] ?? 0); ?> retiros pagados
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-1 border-top border-danger-subtle" style="font-size: 0.72rem;">
                        <div>
                            <span class="text-muted">Base Retiros:</span>
                            <strong class="text-secondary"><?= $currency; ?> <span id="store-stat-with-base"><?= number_format((float) ($stats['withdraw']['total_base'] ?? 0), 2); ?></span></strong>
                        </div>
                        <div class="text-end">
                            <span class="text-danger fw-semibold">Ganado:</span>
                            <strong class="text-danger">+<?= $currency; ?> <span id="store-stat-with-earned"><?= number_format($withEarned, 2); ?></span></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barra de Filtros de Comisiones -->
        <div class="store-movements-filters-bar mb-3 p-3" style="background: rgba(98, 54, 255, 0.04); border: 1px solid rgba(98, 54, 255, 0.12); border-radius: 14px;">
            <form id="form-filter-store-commissions" onsubmit="return false;">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="store-comm-filter-date-from" class="form-label text-dark fw-semibold mb-1" style="font-size: 0.82rem; width: 100%; padding-left: 0; margin: 0 0 4px 0;">Fecha Desde</label>
                        <input type="date" class="form-control store-filter-input" id="store-comm-filter-date-from">
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="store-comm-filter-date-to" class="form-label text-dark fw-semibold mb-1" style="font-size: 0.82rem; width: 100%; padding-left: 0; margin: 0 0 4px 0;">Fecha Hasta</label>
                        <input type="date" class="form-control store-filter-input" id="store-comm-filter-date-to">
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="store-comm-filter-rate" class="form-label text-dark fw-semibold mb-1" style="font-size: 0.82rem; width: 100%; padding-left: 0; margin: 0 0 4px 0;">Tipo de Tasa</label>
                        <select class="form-select form-control store-filter-input" id="store-comm-filter-rate">
                            <option value="all">Todas las tasas</option>
                            <option value="ggr">Solo GGR Afiliados</option>
                            <option value="recharge">Solo Recargas</option>
                            <option value="withdraw">Solo Retiros / Premios</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="store-comm-filter-search" class="form-label text-dark fw-semibold mb-1" style="font-size: 0.82rem; width: 100%; padding-left: 0; margin: 0 0 4px 0;">Buscar (Jugador / Ref)</label>
                        <div class="d-flex gap-1">
                            <input type="text" class="form-control store-filter-input flex-grow-1" id="store-comm-filter-search" placeholder="Jugador, cédula o ref..." autocomplete="off">
                            <button type="button" class="btn btn-primary" onclick="applyStoreCommissionsFilter();" title="Buscar / Filtrar" style="background: #6236ff; border-color: #6236ff; border-radius: 10px; padding: 6px 14px; min-width: 42px;">
                                <i class="fa-duotone fa-solid fa-magnifying-glass"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetStoreCommissionsFilter();" title="Limpiar Filtros" style="border-radius: 10px; padding: 6px 12px; min-width: 38px;">
                                <i class="fa-duotone fa-solid fa-rotate-left"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Indicador de Carga -->
        <div id="store-commissions-loading" class="text-center py-4 d-none">
            <i class="fa-duotone fa-solid fa-spinner fa-spin fs-2 text-primary"></i>
            <p class="small text-muted mt-2">Cargando comisiones...</p>
        </div>

        <!-- Contenedor de la Tabla de Comisiones -->
        <div id="store-commissions-container" class="mb-2">
            <?= view('store/partials/commissions_store_table', [
                'items' => $items,
                'stats' => $stats,
                'currency' => $currency
            ]); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    function getStoreCommissionsFilterParams() {
        return {
            date_from: $('#store-comm-filter-date-from').val() || '',
            date_to: $('#store-comm-filter-date-to').val() || '',
            rate_type: $('#store-comm-filter-rate').val() || 'all',
            search: $('#store-comm-filter-search').val() || ''
        };
    }

    window.applyStoreCommissionsFilter = function() {
        const params = getStoreCommissionsFilterParams();
        $('#store-commissions-loading').removeClass('d-none');
        $('#store-commissions-container').addClass('opacity-50');

        $.ajax({
            url: '<?= site_url('store/storeCommissionsGet'); ?>',
            method: 'GET',
            data: params,
            dataType: 'json',
            success: function(res) {
                if (res && res.html) {
                    $('#store-commissions-container').html(res.html).removeClass('opacity-50');
                }
                if (res && res.stats) {
                    const st = res.stats;
                    const formatNum = function(num) {
                        return parseFloat(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    };
                    if (st.ggr) {
                        $('#store-stat-ggr-base').text(formatNum(st.ggr.total_base));
                        $('#store-stat-ggr-earned').text(formatNum(st.ggr.total_earned));
                    }
                    if (st.recharge) {
                        $('#store-stat-rec-base').text(formatNum(st.recharge.total_base));
                        $('#store-stat-rec-earned').text(formatNum(st.recharge.total_earned));
                    }
                    if (st.withdraw) {
                        $('#store-stat-with-base').text(formatNum(st.withdraw.total_base));
                        $('#store-stat-with-earned').text(formatNum(st.withdraw.total_earned));
                    }
                }
            },
            error: function() {
                Toastify({
                    text: 'Error al consultar comisiones de la tienda.',
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    style: { background: '#dc3545' }
                }).showToast();
                $('#store-commissions-container').removeClass('opacity-50');
            },
            complete: function() {
                $('#store-commissions-loading').addClass('d-none');
            }
        });
    };

    window.resetStoreCommissionsFilter = function() {
        $('#store-comm-filter-date-from').val('');
        $('#store-comm-filter-date-to').val('');
        $('#store-comm-filter-rate').val('all');
        $('#store-comm-filter-search').val('');
        applyStoreCommissionsFilter();
    };

    window.exportStoreCommissions = function() {
        const params = getStoreCommissionsFilterParams();
        const queryString = $.param(params);
        window.location.href = '<?= site_url('store/exportStoreCommissions'); ?>?' + queryString;
    };

    $(document).ready(function() {
        $('#store-comm-filter-rate, #store-comm-filter-date-from, #store-comm-filter-date-to').on('change', function() {
            applyStoreCommissionsFilter();
        });
        $('#store-comm-filter-search').on('keyup', function(e) {
            if (e.key === 'Enter') {
                applyStoreCommissionsFilter();
            }
        });
    });
</script>

<?= view('store/partials/close') ?>
