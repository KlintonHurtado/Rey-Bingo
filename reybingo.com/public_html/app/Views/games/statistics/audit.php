<?php
$currency = systemGet('currency') ?? '$';
$stats = $audit_stats ?? [];
$items = $stats['items'] ?? [];
$totalRecords = $stats['total_records'] ?? 0;
$totalPages = $stats['total_pages'] ?? 1;
$currentPage = $stats['current_page'] ?? 1;
$perPage = $stats['per_page'] ?? 30;
$actorGroup = $stats['actor_group'] ?? 'all';
$movementType = $stats['movement_type'] ?? 'all';
$startDate = $stats['start_date'] ?? date('Y-m-01');
$endDate = $stats['end_date'] ?? date('Y-m-d');
$search = $stats['search'] ?? '';

$groupNames = [
    0 => ['label' => 'Jugador', 'badge' => 'bg-success'],
    1 => ['label' => 'Admin', 'badge' => 'bg-primary'],
    2 => ['label' => 'Punto de Venta', 'badge' => 'bg-info text-dark'],
    3 => ['label' => 'Operador', 'badge' => 'bg-warning text-dark'],
];
?>

<div class="row mt-3">
    <div class="col-md-12 d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h4 class="mb-0">
            <i class="fa-duotone fa-solid fa-file-invoice-dollar text-primary me-2"></i>Auditoría Financiera Global
        </h4>
        <div class="d-flex gap-2">
            <a href="<?= site_url('games/exportFinancialAudit?actor_group=' . rawurlencode($actorGroup) . '&movement_type=' . rawurlencode($movementType) . '&startdate=' . rawurlencode($startDate) . '&enddate=' . rawurlencode($endDate) . '&search=' . rawurlencode($search)); ?>" 
               class="btn btn-sm btn-success btn-bingo" target="_blank">
                <i class="fa-duotone fa-solid fa-file-excel me-1"></i> Exportar Auditoría
            </a>
        </div>
    </div>
</div>

<!-- KPI Cards Resumen Financiero del Período -->
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="card bg-success text-white shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-uppercase fw-bold text-white-50">Total Entradas (+)</span>
                    <i class="fa-duotone fa-solid fa-circle-arrow-down fs-4 opacity-75"></i>
                </div>
                <h3 class="card-text mt-2 mb-0 fw-bold"><?= $currency; ?> <?= number_format((float) ($stats['total_income'] ?? 0), 2); ?></h3>
                <small class="text-white-50">Recargas, Premios, Bonos</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card bg-danger text-white shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-uppercase fw-bold text-white-50">Total Salidas (-)</span>
                    <i class="fa-duotone fa-solid fa-circle-arrow-up fs-4 opacity-75"></i>
                </div>
                <h3 class="card-text mt-2 mb-0 fw-bold"><?= $currency; ?> <?= number_format((float) ($stats['total_expense'] ?? 0), 2); ?></h3>
                <small class="text-white-50">Retiros, Compras Cartones</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card bg-primary text-white shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-uppercase fw-bold text-white-50">Balance Neto</span>
                    <i class="fa-duotone fa-solid fa-scale-balanced fs-4 opacity-75"></i>
                </div>
                <h3 class="card-text mt-2 mb-0 fw-bold"><?= $currency; ?> <?= number_format((float) ($stats['net_balance'] ?? 0), 2); ?></h3>
                <small class="text-white-50">Entradas menos Salidas</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card bg-dark text-white shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-uppercase fw-bold text-white-50">Movimientos</span>
                    <i class="fa-duotone fa-solid fa-list-check fs-4 opacity-75"></i>
                </div>
                <h3 class="card-text mt-2 mb-0 fw-bold"><?= number_format($totalRecords); ?></h3>
                <small class="text-white-50">Operaciones en el rango</small>
            </div>
        </div>
    </div>
</div>

<!-- Filtros de Auditoría -->
<div class="card shadow-sm mb-3">
    <div class="card-body p-3">
        <div class="row g-2 align-items-end">
            <!-- Filtro Rol / Actor -->
            <div class="col-12 col-md-3">
                <label for="auditActorGroup" class="form-label small fw-bold text-muted mb-1">
                    <i class="fa-solid fa-users me-1 text-primary"></i> Filtrar por Rol / Actor
                </label>
                <select class="form-select form-select-lg form-bingo" id="auditActorGroup" onchange="filterAudit(1)">
                    <option value="all" <?= ($actorGroup === 'all' || $actorGroup === '') ? 'selected' : ''; ?>>🌐 Todos los Actores</option>
                    <option value="1" <?= (string)$actorGroup === '1' ? 'selected' : ''; ?>>🛡️ Administrador</option>
                    <option value="2" <?= (string)$actorGroup === '2' ? 'selected' : ''; ?>>🏪 Puntos de Venta</option>
                    <option value="0" <?= (string)$actorGroup === '0' ? 'selected' : ''; ?>>👤 Usuarios / Jugadores</option>
                    <option value="3" <?= (string)$actorGroup === '3' ? 'selected' : ''; ?>>👔 Operadores</option>
                </select>
            </div>

            <!-- Filtro Tipo de Movimiento -->
            <div class="col-12 col-md-3">
                <label for="auditMovementType" class="form-label small fw-bold text-muted mb-1">
                    <i class="fa-solid fa-tags me-1 text-primary"></i> Tipo de Movimiento
                </label>
                <select class="form-select form-select-lg form-bingo" id="auditMovementType" onchange="filterAudit(1)">
                    <option value="all" <?= ($movementType === 'all' || $movementType === '') ? 'selected' : ''; ?>>Todos los Movimientos</option>
                    <option value="deposit" <?= $movementType === 'deposit' ? 'selected' : ''; ?>>📥 Recargas / Depósitos</option>
                    <option value="retire" <?= $movementType === 'retire' ? 'selected' : ''; ?>>📤 Retiros / Pagos</option>
                    <option value="carton_purchase" <?= $movementType === 'carton_purchase' ? 'selected' : ''; ?>>🎟️ Compras de Cartones</option>
                    <option value="award" <?= $movementType === 'award' ? 'selected' : ''; ?>>🏆 Premios Ganados</option>
                    <option value="bonus" <?= $movementType === 'bonus' ? 'selected' : ''; ?>>🎁 Bonos / Ruletas</option>
                    <option value="transfer" <?= $movementType === 'transfer' ? 'selected' : ''; ?>>🔁 Transferencias</option>
                </select>
            </div>

            <!-- Filtro Fecha Desde - Hasta -->
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">
                    <i class="fa-solid fa-calendar-days me-1 text-primary"></i> Rango de Fechas (Desde - Hasta)
                </label>
                <div class="input-group">
                    <input type="date" class="form-control form-control-lg form-bingo" id="auditStartDate" value="<?= esc($startDate); ?>" onchange="filterAudit(1)">
                    <input type="date" class="form-control form-control-lg form-bingo" id="auditEndDate" value="<?= esc($endDate); ?>" onchange="filterAudit(1)">
                </div>
            </div>

            <!-- Búsqueda -->
            <div class="col-12 col-md-3">
                <label for="auditSearch" class="form-label small fw-bold text-muted mb-1">
                    <i class="fa-solid fa-magnifying-glass me-1 text-primary"></i> Buscar Usuario / Ref
                </label>
                <div class="input-group">
                    <input type="text" class="form-control form-control-lg form-bingo" id="auditSearch" 
                           placeholder="Nombre, usuario, código, ref..." value="<?= esc($search); ?>" 
                           onkeyup="if(event.key === 'Enter') filterAudit(1);">
                    <button class="btn btn-primary btn-bingo px-3" type="button" onclick="filterAudit(1)">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Botones rápidos de fechas -->
        <div class="d-flex flex-wrap gap-1 mt-2 pt-2 border-top">
            <span class="small text-muted align-self-center me-1">Atajos de fecha:</span>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="setAuditDateRange('today')">Hoy</button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="setAuditDateRange('yesterday')">Ayer</button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="setAuditDateRange('this_week')">Esta Semana</button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="setAuditDateRange('this_month')">Este Mes</button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="setAuditDateRange('last_30')">Últimos 30 días</button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="setAuditDateRange('all_time')">Todo el año</button>
        </div>
    </div>
</div>

<!-- Tabla Detallada de Auditoría -->
<div class="card shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="fa-solid fa-table-list me-1 text-primary"></i> Registro Cronológico de Movimientos Financieros
        </h6>
        <span class="badge bg-secondary">
            Mostrando <?= min($totalRecords, count($items)); ?> de <?= number_format($totalRecords); ?> registros
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                <thead class="table-dark text-nowrap">
                    <tr>
                        <th style="width: 140px;">Fecha / Hora</th>
                        <th>Actor / Usuario</th>
                        <th>Rol</th>
                        <th>Concepto / Tipo</th>
                        <th>Billetera Afectada</th>
                        <th class="text-end" style="width: 120px;">Monto</th>
                        <th>Estado</th>
                        <th>Detalles / Referencia</th>
                        <th class="text-center" style="width: 60px;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $it): ?>
                            <?php 
                                $groupInfo = $groupNames[$it['user_group'] ?? 0] ?? ['label' => 'Usuario', 'badge' => 'bg-secondary'];
                                $isPositive = ($it['direction'] ?? '+') === '+';
                                $amtClass = $isPositive ? 'text-success fw-bold' : 'text-danger fw-bold';
                                $sign = $isPositive ? '+' : '-';
                            ?>
                            <tr>
                                <td class="text-nowrap text-muted font-monospace" style="font-size: 0.82rem;">
                                    <?= date('d/m/Y H:i:s', strtotime($it['datetime'])); ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <strong class="d-block text-dark"><?= esc($it['user_name']); ?></strong>
                                            <span class="text-muted small">@<?= esc($it['username']); ?> · <code><?= esc($it['user_code']); ?></code></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $groupInfo['badge']; ?> small">
                                        <?= $groupInfo['label']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $it['badge_class'] ?? 'bg-primary'; ?> px-2 py-1">
                                        <i class="<?= $it['icon'] ?? 'fa-solid fa-money-bill'; ?> me-1"></i>
                                        <?= esc($it['type_label']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted small fw-semibold"><?= esc($it['wallet'] ?? 'Saldo Real'); ?></span>
                                </td>
                                <td class="text-end <?= $amtClass; ?> font-monospace fs-6">
                                    <?= $sign; ?> <?= $currency; ?> <?= number_format((float) $it['amount'], 2); ?>
                                </td>
                                <td>
                                    <span class="badge <?= $it['status_badge'] ?? 'bg-success'; ?> small">
                                        <?= esc($it['status_label'] ?? 'Completado'); ?>
                                    </span>
                                </td>
                                <td class="small text-muted" style="max-width: 250px;">
                                    <div class="text-truncate" title="<?= esc($it['detail'] ?? ''); ?>">
                                        <?= esc($it['detail'] ?? '-'); ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($it['user_id'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" 
                                                onclick="viewUser(<?= (int) $it['user_id']; ?>)" title="Ver perfil de usuario">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fa-duotone fa-solid fa-magnifying-glass fs-1 d-block mb-2 opacity-50"></i>
                                No se encontraron registros financieros con los filtros seleccionados.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="small text-muted">
                Página <?= $currentPage; ?> de <?= $totalPages; ?> (<?= number_format($totalRecords); ?> movimientos en total)
            </span>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : ''; ?>">
                    <button class="page-link" type="button" onclick="filterAudit(1)"><i class="fa-solid fa-angles-left"></i></button>
                </li>
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : ''; ?>">
                    <button class="page-link" type="button" onclick="filterAudit(<?= max(1, $currentPage - 1); ?>)"><i class="fa-solid fa-angle-left"></i></button>
                </li>
                
                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                for ($p = $startPage; $p <= $endPage; $p++):
                ?>
                    <li class="page-item <?= $p == $currentPage ? 'active' : ''; ?>">
                        <button class="page-link" type="button" onclick="filterAudit(<?= $p; ?>)"><?= $p; ?></button>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                    <button class="page-link" type="button" onclick="filterAudit(<?= min($totalPages, $currentPage + 1); ?>)"><i class="fa-solid fa-angle-right"></i></button>
                </li>
                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                    <button class="page-link" type="button" onclick="filterAudit(<?= $totalPages; ?>)"><i class="fa-solid fa-angles-right"></i></button>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
    function filterAudit(page = 1) {
        var actorGroup = $('#auditActorGroup').val() || 'all';
        var movementType = $('#auditMovementType').val() || 'all';
        var startDate = $('#auditStartDate').val() || '';
        var endDate = $('#auditEndDate').val() || '';
        var search = $('#auditSearch').val() || '';

        // Sincronizar con los filtros superiores si existen
        if (startDate) $('#startdate').val(startDate);
        if (endDate) $('#enddate').val(endDate);

        if (typeof statisticsGet === 'function') {
            statisticsGet('audit', {
                actor_group: actorGroup,
                movement_type: movementType,
                startdate: startDate,
                enddate: endDate,
                search: search,
                page: page,
                per_page: <?= (int) $perPage; ?>
            });
        }
    }

    function setAuditDateRange(preset) {
        var today = new Date();
        var yyyy = today.getFullYear();
        var mm = String(today.getMonth() + 1).padStart(2, '0');
        var dd = String(today.getDate()).padStart(2, '0');
        var todayStr = yyyy + '-' + mm + '-' + dd;
        
        var startStr = todayStr;
        var endStr = todayStr;

        if (preset === 'today') {
            startStr = todayStr;
            endStr = todayStr;
        } else if (preset === 'yesterday') {
            var yest = new Date(today);
            yest.setDate(today.getDate() - 1);
            var ymm = String(yest.getMonth() + 1).padStart(2, '0');
            var ydd = String(yest.getDate()).padStart(2, '0');
            startStr = yest.getFullYear() + '-' + ymm + '-' + ydd;
            endStr = startStr;
        } else if (preset === 'this_week') {
            var curr = new Date(today);
            var firstDay = new Date(curr.setDate(curr.getDate() - curr.getDay() + (curr.getDay() === 0 ? -6 : 1)));
            startStr = firstDay.getFullYear() + '-' + String(firstDay.getMonth() + 1).padStart(2, '0') + '-' + String(firstDay.getDate()).padStart(2, '0');
            endStr = todayStr;
        } else if (preset === 'this_month') {
            startStr = yyyy + '-' + mm + '-01';
            endStr = todayStr;
        } else if (preset === 'last_30') {
            var prior = new Date(today);
            prior.setDate(today.getDate() - 30);
            startStr = prior.getFullYear() + '-' + String(prior.getMonth() + 1).padStart(2, '0') + '-' + String(prior.getDate()).padStart(2, '0');
            endStr = todayStr;
        } else if (preset === 'all_time') {
            startStr = yyyy + '-01-01';
            endStr = todayStr;
        }

        $('#auditStartDate').val(startStr);
        $('#auditEndDate').val(endStr);
        $('#startdate').val(startStr);
        $('#enddate').val(endStr);

        filterAudit(1);
    }
</script>
