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

<div class="card store-panel-card h-100">
    <div class="card-body p-3">
        <!-- Encabezado de la Sección -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="fa-duotone fa-solid fa-clock-rotate-left text-primary me-2"></i>
                    Historial Completo de Movimientos
                </h5>
                <small class="text-muted">
                    Consulta todas las recargas, retiros pagados, comisiones y acreditaciones realizadas por tu Punto de Venta.
                </small>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-success" id="btn-export-movements" onclick="exportStoreMovements();">
                    <i class="fa-duotone fa-solid fa-file-excel me-1"></i> Descargar Excel / CSV
                </button>
            </div>
        </div>

        <!-- Tarjetas de Resumen Estadístico -->
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; background: linear-gradient(135deg, rgba(13,110,253,0.08) 0%, rgba(13,110,253,0.02) 100%); border-left: 4px solid #0d6efd !important;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Saldo Recargable</small>
                            <h5 class="mb-0 fw-bold text-primary"><?= esc($currency); ?> <?= number_format((float) ($walletSummary['recharge'] ?? 0), 2); ?></h5>
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
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Recargas Jugadores</small>
                            <h5 class="mb-0 fw-bold text-info"><?= esc($currency); ?> <?= number_format((float) ($stats['total_recharges_amount'] ?? 0), 2); ?></h5>
                            <small class="text-muted"><?= (int) ($stats['total_recharges_count'] ?? 0); ?> realizadas</small>
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
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Retiros Pagados</small>
                            <h5 class="mb-0 fw-bold text-danger"><?= esc($currency); ?> <?= number_format((float) ($stats['total_retires_amount'] ?? 0), 2); ?></h5>
                            <small class="text-muted"><?= (int) ($stats['total_retires_count'] ?? 0); ?> pagados</small>
                        </div>
                        <div class="text-danger fs-3">
                            <i class="fa-duotone fa-solid fa-money-bill-transfer"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px; background: linear-gradient(135deg, rgba(255,193,7,0.12) 0%, rgba(255,193,7,0.03) 100%); border-left: 4px solid #ffc107 !important;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Comisiones Ganadas</small>
                            <h5 class="mb-0 fw-bold text-warning text-dark"><?= esc($currency); ?> <?= number_format((float) ($stats['total_commissions_amount'] ?? 0), 2); ?></h5>
                            <small class="text-muted"><?= (int) ($stats['total_commissions_count'] ?? 0); ?> registros</small>
                        </div>
                        <div class="text-warning fs-3">
                            <i class="fa-duotone fa-solid fa-sack-dollar"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barra de Filtros -->
        <div class="card border-0 bg-light p-3 mb-3" style="border-radius: 12px;">
            <form id="form-filter-movements" onsubmit="return false;">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label for="filter-date-from" class="form-label small fw-bold mb-1">Fecha Desde</label>
                        <input type="date" class="form-control form-control-sm form-bingo" id="filter-date-from" value="<?= esc($filters['date_from'] ?? ''); ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="filter-date-to" class="form-label small fw-bold mb-1">Fecha Hasta</label>
                        <input type="date" class="form-control form-control-sm form-bingo" id="filter-date-to" value="<?= esc($filters['date_to'] ?? ''); ?>">
                    </div>
                    <div class="col-12 col-md-2">
                        <label for="filter-type" class="form-label small fw-bold mb-1">Tipo de Movimiento</label>
                        <select class="form-control form-control-sm form-bingo" id="filter-type">
                            <option value="all" <?= ($filters['type'] ?? '') === 'all' ? 'selected' : ''; ?>>Todos los tipos</option>
                            <option value="recharge" <?= ($filters['type'] ?? '') === 'recharge' ? 'selected' : ''; ?>>Recargas a Jugadores</option>
                            <option value="retire" <?= ($filters['type'] ?? '') === 'retire' ? 'selected' : ''; ?>>Pagos de Retiros</option>
                            <option value="credit" <?= ($filters['type'] ?? '') === 'credit' ? 'selected' : ''; ?>>Acreditaciones de Saldo</option>
                            <option value="commission" <?= ($filters['type'] ?? '') === 'commission' ? 'selected' : ''; ?>>Comisiones Ganadas</option>
                            <option value="debit" <?= ($filters['type'] ?? '') === 'debit' ? 'selected' : ''; ?>>Débitos / Ajustes</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="filter-search" class="form-label small fw-bold mb-1">Buscar (Cédula / Nombre / Código)</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control form-bingo" id="filter-search" placeholder="Cédula, nombre o código..." value="<?= esc($filters['search'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-12 col-md-1 d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-primary btn-bingo w-100 py-1" onclick="applyStoreMovementsFilter();" title="Filtrar">
                            <i class="fa-duotone fa-solid fa-magnifying-glass"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary w-100 py-1" onclick="resetStoreMovementsFilter();" title="Limpiar">
                            <i class="fa-duotone fa-solid fa-rotate-left"></i>
                        </button>
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
                'movements' => $movements ?? [],
                'stats' => $stats ?? [],
                'currency' => $currency,
            ]) ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    function getFilterParams() {
        return {
            date_from: $('#filter-date-from').val(),
            date_to: $('#filter-date-to').val(),
            type: $('#filter-type').val(),
            search: $('#filter-search').val()
        };
    }

    function applyStoreMovementsFilter() {
        const params = getFilterParams();
        $('#store-movements-loading').removeClass('d-none');
        $('#store-movements-container').addClass('opacity-50');

        $.ajax({
            url: '<?= site_url('store/movementsListGet'); ?>',
            method: 'GET',
            data: params,
            success: function(html) {
                $('#store-movements-container').html(html).removeClass('opacity-50');
            },
            error: function() {
                Toastify({
                    text: 'Error al consultar movimientos.',
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
    }

    function resetStoreMovementsFilter() {
        $('#filter-date-from').val('');
        $('#filter-date-to').val('');
        $('#filter-type').val('all');
        $('#filter-search').val('');
        applyStoreMovementsFilter();
    }

    function exportStoreMovements() {
        const params = getFilterParams();
        const queryString = $.param(params);
        window.location.href = '<?= site_url('store/exportMovements'); ?>?' + queryString;
    }

    $(function() {
        $('#filter-search').on('keyup', function(e) {
            if (e.key === 'Enter') {
                applyStoreMovementsFilter();
            }
        });
        $('#filter-type, #filter-date-from, #filter-date-to').on('change', function() {
            applyStoreMovementsFilter();
        });
    });
</script>
