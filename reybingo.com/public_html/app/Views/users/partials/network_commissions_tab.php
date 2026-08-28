<?php
$currency = $currency ?? esc(systemGet('currency') ?? '$');
$filters = $filters ?? [];
$opSum = $networkSummary['operators'] ?? [];
$stSum = $networkSummary['stores'] ?? [];
$totSum = $networkSummary['totals'] ?? [];
$opEntities = $networkEntities['operators'] ?? [];
$stEntities = $networkEntities['stores'] ?? [];

$dateFrom = (string) ($filters['date_from'] ?? '');
$dateTo = (string) ($filters['date_to'] ?? '');
$search = (string) ($filters['search'] ?? '');
$rateType = (string) ($filters['rate_type'] ?? 'all');

$periodLabel = ($dateFrom !== '' || $dateTo !== '')
    ? (($dateFrom !== '' ? $dateFrom : '…') . ' → ' . ($dateTo !== '' ? $dateTo : '…'))
    : 'Histórico completo (sin filtro de fechas)';

$fmt = static fn (float $n): string => number_format($n, 2);
?>

<div class="admin-network-commissions">
    <div class="admin-network-filters mb-3 p-3">
        <form method="get" action="<?= site_url('users/commissions'); ?>" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="network">
            <div class="col-lg-2 col-md-4 col-sm-6">
                <label class="form-label small fw-semibold text-dark mb-1">Desde</label>
                <input type="date" class="form-control form-bingo" name="date_from" value="<?= esc($dateFrom); ?>">
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <label class="form-label small fw-semibold text-dark mb-1">Hasta</label>
                <input type="date" class="form-control form-bingo" name="date_to" value="<?= esc($dateTo); ?>">
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <label class="form-label small fw-semibold text-dark mb-1">Tipo comisión</label>
                <select class="form-select form-bingo" name="rate_type">
                    <option value="all" <?= $rateType === 'all' ? 'selected' : ''; ?>>Todas</option>
                    <option value="ggr" <?= $rateType === 'ggr' ? 'selected' : ''; ?>>GGR Afiliados</option>
                    <option value="recharge" <?= $rateType === 'recharge' ? 'selected' : ''; ?>>Recargas</option>
                    <option value="withdraw" <?= $rateType === 'withdraw' ? 'selected' : ''; ?>>Retiros / Premios</option>
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label small fw-semibold text-dark mb-1">Buscar</label>
                <input type="text" class="form-control form-bingo" name="search" value="<?= esc($search); ?>" placeholder="Nombre, código, referencia...">
            </div>
            <div class="col-lg-3 col-md-12 d-flex flex-wrap gap-2 align-items-center">
                <button type="submit" class="btn btn-primary px-3">
                    <i class="fa-duotone fa-solid fa-filter me-1"></i> Filtrar
                </button>
                <a href="<?= site_url('users/commissions?tab=network'); ?>" class="btn btn-outline-secondary">Limpiar</a>
                <button type="button" class="btn btn-success ms-auto" onclick="exportNetworkCommissions();">
                    <i class="fa-duotone fa-solid fa-file-excel me-1"></i> Excel detalle Red
                </button>
            </div>
        </form>
        <div class="small text-muted mt-2">
            <i class="fa-duotone fa-solid fa-calendar-days me-1"></i> Período: <strong class="text-dark"><?= esc($periodLabel); ?></strong>
            · <?= count($opEntities); ?> operadores · <?= count($stEntities); ?> puntos de venta con actividad
        </div>
    </div>

  <!-- Totales red -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-lg-3">
            <div class="admin-network-kpi admin-network-kpi--ggr">
                <div class="admin-network-kpi-head">
                    <span>Total GGR Red</span>
                    <i class="fa-duotone fa-solid fa-chart-pie"></i>
                </div>
                <div class="admin-network-kpi-value"><?= $currency; ?> <?= $fmt((float) ($totSum['ggr'] ?? 0)); ?></div>
                <div class="admin-network-kpi-sub">
                    Apostado <?= $currency; ?> <?= $fmt((float) ($totSum['ggr_stake'] ?? 0)); ?>
                    · Premios <?= $currency; ?> <?= $fmt((float) ($totSum['ggr_payout'] ?? 0)); ?>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="admin-network-kpi admin-network-kpi--rec">
                <div class="admin-network-kpi-head">
                    <span>Total Recargas Red</span>
                    <i class="fa-duotone fa-solid fa-mobile-screen"></i>
                </div>
                <div class="admin-network-kpi-value"><?= $currency; ?> <?= $fmt((float) ($totSum['recharge'] ?? 0)); ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="admin-network-kpi admin-network-kpi--ret">
                <div class="admin-network-kpi-head">
                    <span>Total Retiros Red</span>
                    <i class="fa-duotone fa-solid fa-money-bill-transfer"></i>
                </div>
                <div class="admin-network-kpi-value"><?= $currency; ?> <?= $fmt((float) ($totSum['withdraw'] ?? 0)); ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="admin-network-kpi admin-network-kpi--total">
                <div class="admin-network-kpi-head">
                    <span>Total Comisiones Red</span>
                    <i class="fa-duotone fa-solid fa-coins"></i>
                </div>
                <div class="admin-network-kpi-value admin-network-kpi-value--hero"><?= $currency; ?> <?= $fmt((float) ($totSum['total'] ?? 0)); ?></div>
            </div>
        </div>
    </div>

    <!-- Matriz comparativa -->
    <div class="card border-0 shadow-sm mb-3 admin-network-matrix-card">
        <div class="card-body p-0">
            <table class="table table-sm mb-0 admin-network-matrix">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th class="text-end">Operadores</th>
                        <th class="text-end">Puntos de Venta</th>
                        <th class="text-end fw-bold">Total Red</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="badge bg-warning text-dark">GGR</span></td>
                        <td class="text-end"><?= $currency; ?> <?= $fmt((float) ($opSum['ggr'] ?? 0)); ?></td>
                        <td class="text-end"><?= $currency; ?> <?= $fmt((float) ($stSum['ggr'] ?? 0)); ?></td>
                        <td class="text-end fw-bold"><?= $currency; ?> <?= $fmt((float) ($totSum['ggr'] ?? 0)); ?></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-info text-dark">Recargas</span></td>
                        <td class="text-end"><?= $currency; ?> <?= $fmt((float) ($opSum['recharge'] ?? 0)); ?></td>
                        <td class="text-end"><?= $currency; ?> <?= $fmt((float) ($stSum['recharge'] ?? 0)); ?></td>
                        <td class="text-end fw-bold"><?= $currency; ?> <?= $fmt((float) ($totSum['recharge'] ?? 0)); ?></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-danger">Retiros</span></td>
                        <td class="text-end"><?= $currency; ?> <?= $fmt((float) ($opSum['withdraw'] ?? 0)); ?></td>
                        <td class="text-end"><?= $currency; ?> <?= $fmt((float) ($stSum['withdraw'] ?? 0)); ?></td>
                        <td class="text-end fw-bold"><?= $currency; ?> <?= $fmt((float) ($totSum['withdraw'] ?? 0)); ?></td>
                    </tr>
                    <tr class="table-light">
                        <td class="fw-bold">Total comisiones</td>
                        <td class="text-end fw-bold text-primary"><?= $currency; ?> <?= $fmt((float) ($opSum['total'] ?? 0)); ?></td>
                        <td class="text-end fw-bold text-success"><?= $currency; ?> <?= $fmt((float) ($stSum['total'] ?? 0)); ?></td>
                        <td class="text-end fw-bold text-primary fs-6"><?= $currency; ?> <?= $fmt((float) ($totSum['total'] ?? 0)); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3">
        <!-- Operadores -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-bold">
                        <i class="fa-duotone fa-solid fa-user-tie text-primary me-1"></i> Operadores
                    </h6>
                    <span class="badge bg-primary-subtle text-primary"><?= count($opEntities); ?></span>
                </div>
                <div class="table-responsive scroll-pane admin-network-table-scroll">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.82rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th class="text-end">GGR</th>
                                <th class="text-end">Rec.</th>
                                <th class="text-end">Ret.</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (! empty($opEntities)) : ?>
                                <?php foreach ($opEntities as $row) : ?>
                                    <tr>
                                        <td>
                                            <a href="javascript:void(0)" onclick="viewUser(<?= (int) $row['id']; ?>);" class="fw-semibold text-dark text-decoration-none">
                                                <?= esc($row['name'] ?? '-'); ?>
                                            </a>
                                            <?php if (! empty($row['code'])) : ?>
                                                <small class="text-muted d-block"><?= esc($row['code']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <span class="d-block"><?= $currency; ?> <?= $fmt((float) ($row['ggr'] ?? 0)); ?></span>
                                            <?php if ((float) ($row['ggr_stake'] ?? 0) > 0) : ?>
                                                <small class="text-muted">Ap. <?= $fmt((float) ($row['ggr_stake'] ?? 0)); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end"><?= $currency; ?> <?= $fmt((float) ($row['recharge'] ?? 0)); ?></td>
                                        <td class="text-end"><?= $currency; ?> <?= $fmt((float) ($row['withdraw'] ?? 0)); ?></td>
                                        <td class="text-end fw-bold text-primary"><?= $currency; ?> <?= $fmt((float) ($row['total'] ?? 0)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Sin comisiones de operadores en este criterio.</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if (! empty($opEntities)) : ?>
                            <tfoot class="table-light">
                                <tr>
                                    <td class="fw-bold">Totales</td>
                                    <td class="text-end fw-semibold"><?= $currency; ?> <?= $fmt((float) ($opSum['ggr'] ?? 0)); ?></td>
                                    <td class="text-end fw-semibold"><?= $currency; ?> <?= $fmt((float) ($opSum['recharge'] ?? 0)); ?></td>
                                    <td class="text-end fw-semibold"><?= $currency; ?> <?= $fmt((float) ($opSum['withdraw'] ?? 0)); ?></td>
                                    <td class="text-end fw-bold text-primary"><?= $currency; ?> <?= $fmt((float) ($opSum['total'] ?? 0)); ?></td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- Puntos de venta -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-bold">
                        <i class="fa-duotone fa-solid fa-store text-success me-1"></i> Puntos de Venta / Agencias
                    </h6>
                    <span class="badge bg-success-subtle text-success"><?= count($stEntities); ?></span>
                </div>
                <div class="table-responsive scroll-pane admin-network-table-scroll">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.82rem;">
                        <thead class="table-light">
                            <tr>
                                <th>PV / Agencia</th>
                                <th class="text-end">GGR</th>
                                <th class="text-end">Rec.</th>
                                <th class="text-end">Ret.</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (! empty($stEntities)) : ?>
                                <?php foreach ($stEntities as $row) : ?>
                                    <tr>
                                        <td>
                                            <a href="javascript:void(0)" onclick="viewUser(<?= (int) $row['id']; ?>);" class="fw-semibold text-dark text-decoration-none">
                                                <?= esc($row['name'] ?? '-'); ?>
                                            </a>
                                            <small class="text-muted d-block">
                                                <?= esc($row['code'] ?? ''); ?>
                                                <?php if (! empty($row['operator_name'])) : ?>
                                                    · Op: <?= esc($row['operator_name']); ?>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td class="text-end">
                                            <span class="d-block"><?= $currency; ?> <?= $fmt((float) ($row['ggr'] ?? 0)); ?></span>
                                            <?php if ((float) ($row['ggr_stake'] ?? 0) > 0) : ?>
                                                <small class="text-muted">Ap. <?= $fmt((float) ($row['ggr_stake'] ?? 0)); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end"><?= $currency; ?> <?= $fmt((float) ($row['recharge'] ?? 0)); ?></td>
                                        <td class="text-end"><?= $currency; ?> <?= $fmt((float) ($row['withdraw'] ?? 0)); ?></td>
                                        <td class="text-end fw-bold text-success"><?= $currency; ?> <?= $fmt((float) ($row['total'] ?? 0)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Sin comisiones de PV en este criterio.</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if (! empty($stEntities)) : ?>
                            <tfoot class="table-light">
                                <tr>
                                    <td class="fw-bold">Totales</td>
                                    <td class="text-end fw-semibold"><?= $currency; ?> <?= $fmt((float) ($stSum['ggr'] ?? 0)); ?></td>
                                    <td class="text-end fw-semibold"><?= $currency; ?> <?= $fmt((float) ($stSum['recharge'] ?? 0)); ?></td>
                                    <td class="text-end fw-semibold"><?= $currency; ?> <?= $fmt((float) ($stSum['withdraw'] ?? 0)); ?></td>
                                    <td class="text-end fw-bold text-success"><?= $currency; ?> <?= $fmt((float) ($stSum['total'] ?? 0)); ?></td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <p class="small text-muted mt-3 mb-0">
        <i class="fa-duotone fa-solid fa-circle-info me-1"></i>
        Operadores: ganancia diferencial. PV: comisión directa. El Excel incluye cada línea de comisión con detalle GGR (apostado / premios).
    </p>
</div>
