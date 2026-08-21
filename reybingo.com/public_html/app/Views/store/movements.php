<?= view('store/partials/open', [
    'imagePath' => $imagePath,
    'walletSummary' => $walletSummary,
    'pendingPrizes' => $pendingPrizes ?? 0,
    'activeNav' => 'movements',
]) ?>

<?php
$currency = systemGet('currency') ?? '$';
$stats = $stats ?? [];
?>

<div class="card store-panel-card h-100" style="min-height: 0; display: flex; flex-direction: column; overflow: hidden;">
    <div class="card-body p-3 store-movements-scroll-body" style="flex: 1; min-height: 0; overflow-y: auto; overflow-x: hidden;">
        <!-- Encabezado de la Sección -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="fa-duotone fa-solid fa-clock-rotate-left text-primary me-2"></i>
                    Historial de Movimientos Operativos
                </h5>
                <small class="text-muted">
                    Consulta todas las recargas a jugadores, pagos de retiros en efectivo y acreditaciones de saldo.
                </small>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-success" id="btn-export-movements" onclick="exportStoreMovements();">
                    <i class="fa-duotone fa-solid fa-file-excel me-1"></i> Descargar Excel
                </button>
            </div>
        </div>

        <!-- 4 Tarjetas de Resumen Estadístico Operativo -->
        <div class="row g-2 mb-3">
            <!-- 1. Saldo Disponible para Venta -->
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; background: linear-gradient(135deg, rgba(13,110,253,0.08) 0%, rgba(13,110,253,0.02) 100%); border-left: 4px solid #0d6efd !important;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Saldo para Venta</small>
                            <h5 class="mb-0 fw-bold text-primary"><?= esc($currency); ?> <?= number_format((float) ($walletSummary['recharge'] ?? 0), 2); ?></h5>
                            <small class="text-muted">Disponible</small>
                        </div>
                        <div class="text-primary fs-3">
                            <i class="fa-duotone fa-solid fa-wallet"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Total Recargas Realizadas -->
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; background: linear-gradient(135deg, rgba(13,202,240,0.08) 0%, rgba(13,202,240,0.02) 100%); border-left: 4px solid #0dcaf0 !important;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Total Recargas</small>
                            <h5 class="mb-0 fw-bold text-info"><?= esc($currency); ?> <span id="stat-total-recharges"><?= number_format((float) ($stats['total_recharges_amount'] ?? 0), 2); ?></span></h5>
                            <small class="text-muted"><span id="stat-count-recharges"><?= (int) ($stats['total_recharges_count'] ?? 0); ?></span> recargas</small>
                        </div>
                        <div class="text-info fs-3">
                            <i class="fa-duotone fa-solid fa-mobile-screen"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Total Retiros Pagados -->
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; background: linear-gradient(135deg, rgba(25,135,84,0.08) 0%, rgba(25,135,84,0.02) 100%); border-left: 4px solid #198754 !important;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Retiros Pagados</small>
                            <h5 class="mb-0 fw-bold text-success"><?= esc($currency); ?> <span id="stat-total-retires"><?= number_format((float) ($stats['total_retires_amount'] ?? 0), 2); ?></span></h5>
                            <small class="text-muted"><span id="stat-count-retires"><?= (int) ($stats['total_retires_count'] ?? 0); ?></span> pagos</small>
                        </div>
                        <div class="text-success fs-3">
                            <i class="fa-duotone fa-solid fa-money-bill-transfer"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Acreditaciones / Ingresos -->
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; background: linear-gradient(135deg, rgba(255,193,7,0.12) 0%, rgba(255,193,7,0.03) 100%); border-left: 4px solid #ffc107 !important;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Acreditaciones</small>
                            <h5 class="mb-0 fw-bold text-warning text-dark"><?= esc($currency); ?> <span id="stat-total-credits"><?= number_format((float) ($stats['total_credits_amount'] ?? 0), 2); ?></span></h5>
                            <small class="text-muted"><span id="stat-count-credits"><?= (int) ($stats['total_credits_count'] ?? 0); ?></span> ingresos</small>
                        </div>
                        <div class="text-warning fs-3">
                            <i class="fa-duotone fa-solid fa-hand-holding-dollar"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barra de Filtros -->
        <div class="store-movements-filters-bar mb-3 p-3" style="background: rgba(98, 54, 255, 0.04); border: 1px solid rgba(98, 54, 255, 0.12); border-radius: 14px;">
            <form id="form-filter-movements" onsubmit="return false;">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="filter-date-from" class="form-label text-dark fw-semibold mb-1" style="font-size: 0.82rem; width: 100%; padding-left: 0; margin: 0 0 4px 0;">Fecha Desde</label>
                        <input type="date" class="form-control store-filter-input" id="filter-date-from" value="<?= esc($filters['date_from'] ?? ''); ?>">
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="filter-date-to" class="form-label text-dark fw-semibold mb-1" style="font-size: 0.82rem; width: 100%; padding-left: 0; margin: 0 0 4px 0;">Fecha Hasta</label>
                        <input type="date" class="form-control store-filter-input" id="filter-date-to" value="<?= esc($filters['date_to'] ?? ''); ?>">
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="filter-type" class="form-label text-dark fw-semibold mb-1" style="font-size: 0.82rem; width: 100%; padding-left: 0; margin: 0 0 4px 0;">Tipo de Movimiento</label>
                        <select class="form-select form-control store-filter-input" id="filter-type">
                            <option value="all" <?= ($filters['type'] ?? '') === 'all' ? 'selected' : ''; ?>>Todos los tipos</option>
                            <option value="recharge" <?= ($filters['type'] ?? '') === 'recharge' ? 'selected' : ''; ?>>Recargas a Jugadores</option>
                            <option value="retire" <?= ($filters['type'] ?? '') === 'retire' ? 'selected' : ''; ?>>Pagos de Retiros</option>
                            <option value="credit" <?= ($filters['type'] ?? '') === 'credit' ? 'selected' : ''; ?>>Acreditaciones de Saldo</option>
                            <option value="debit" <?= ($filters['type'] ?? '') === 'debit' ? 'selected' : ''; ?>>Débitos / Ajustes</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="filter-search" class="form-label text-dark fw-semibold mb-1" style="font-size: 0.82rem; width: 100%; padding-left: 0; margin: 0 0 4px 0;">Buscar (Cédula / Nombre / Ref)</label>
                        <div class="d-flex gap-1">
                            <input type="text" class="form-control store-filter-input flex-grow-1" id="filter-search" placeholder="Cédula, nombre o código..." value="<?= esc($filters['search'] ?? ''); ?>">
                            <button type="button" class="btn btn-primary" onclick="applyStoreMovementsFilter();" title="Buscar / Filtrar" style="background: #6236ff; border-color: #6236ff; border-radius: 10px; padding: 6px 14px; min-width: 42px;">
                                <i class="fa-duotone fa-solid fa-magnifying-glass"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetStoreMovementsFilter();" title="Limpiar Filtros" style="border-radius: 10px; padding: 6px 12px; min-width: 38px;">
                                <i class="fa-duotone fa-solid fa-rotate-left"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Indicador de Carga -->
        <div id="store-movements-loading" class="text-center py-4 d-none">
            <i class="fa-duotone fa-solid fa-spinner fa-spin fs-2 text-primary"></i>
            <p class="small text-muted mt-2">Cargando movimientos...</p>
        </div>

        <!-- Contenedor de la Tabla -->
        <div id="store-movements-container">
            <?= view('store/movements_list', [
                'movements' => $movements,
                'stats' => $stats,
                'currency' => $currency
            ]); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    function getFilterParams() {
        return {
            date_from: $('#filter-date-from').val() || '',
            date_to: $('#filter-date-to').val() || '',
            type: $('#filter-type').val() || 'all',
            search: $('#filter-search').val() || ''
        };
    }

    window.applyStoreMovementsFilter = function() {
        const params = getFilterParams();
        $('#store-movements-loading').removeClass('d-none');
        $('#store-movements-container').addClass('opacity-50');

        $.ajax({
            url: '<?= site_url('store/movementsListGet'); ?>',
            method: 'GET',
            data: params,
            dataType: 'html',
            success: function(html) {
                $('#store-movements-container').html(html).removeClass('opacity-50');

                // Actualizar métricas dinámicas
                const wrapper = $('#store-movements-table-wrapper');
                if (wrapper.length) {
                    const formatNum = function(num) {
                        return parseFloat(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    };
                    const recAmt = wrapper.data('total-recharges-amount');
                    const recCount = wrapper.data('total-recharges-count');
                    const retAmt = wrapper.data('total-retires-amount');
                    const retCount = wrapper.data('total-retires-count');
                    const credAmt = wrapper.data('total-credits-amount');
                    const credCount = wrapper.data('total-credits-count');

                    if (recAmt !== undefined) $('#stat-total-recharges').text(formatNum(recAmt));
                    if (recCount !== undefined) $('#stat-count-recharges').text(recCount);
                    if (retAmt !== undefined) $('#stat-total-retires').text(formatNum(retAmt));
                    if (retCount !== undefined) $('#stat-count-retires').text(retCount);
                    if (credAmt !== undefined) $('#stat-total-credits').text(formatNum(credAmt));
                    if (credCount !== undefined) $('#stat-count-credits').text(credCount);
                }
            },
            error: function() {
                Toastify({
                    text: 'Error al filtrar movimientos.',
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    style: { background: '#dc3545' }
                }).showToast();
                $('#store-movements-container').removeClass('opacity-50');
            },
            complete: function() {
                $('#store-movements-loading').addClass('d-none');
            }
        });
    };

    window.resetStoreMovementsFilter = function() {
        $('#filter-date-from').val('');
        $('#filter-date-to').val('');
        $('#filter-type').val('all');
        $('#filter-search').val('');
        applyStoreMovementsFilter();
    };

    window.exportStoreMovements = function() {
        const params = getFilterParams();
        const queryString = $.param(params);
        window.location.href = '<?= site_url('store/exportMovements'); ?>?' + queryString;
    };

    $(document).ready(function() {
        $('#filter-type, #filter-date-from, #filter-date-to').on('change', function() {
            applyStoreMovementsFilter();
        });
        $('#filter-search').on('keyup', function(e) {
            if (e.key === 'Enter') {
                applyStoreMovementsFilter();
            }
        });
    });
</script>

<?= view('store/partials/close') ?>
