<?php
$storesCommissions = $storesCommissions ?? [];
$currency = esc(systemGet('currency'));
$dateFrom = (string) ($storesCommissions['date_from'] ?? date('Y-m-d', strtotime('-30 days')));
$dateTo = (string) ($storesCommissions['date_to'] ?? date('Y-m-d'));

$stats = $storesCommissions['commission_stats'] ?? [];
$ggrRate = (float) ($stats['ggr']['rate'] ?? 0) * 100;
$recRate = (float) ($stats['recharge']['rate'] ?? 0) * 100;
$withRate = (float) ($stats['withdraw']['rate'] ?? 0) * 100;

$ggrStores = (float) ($stats['ggr']['stores_earned'] ?? 0);
$ggrOp = (float) ($stats['ggr']['operator_earned'] ?? 0);
$recStores = (float) ($stats['recharge']['stores_earned'] ?? 0);
$recOp = (float) ($stats['recharge']['operator_earned'] ?? 0);
$withStores = (float) ($stats['withdraw']['stores_earned'] ?? 0);
$withOp = (float) ($stats['withdraw']['operator_earned'] ?? 0);

$totalStores = (float) ($stats['total_stores_earned'] ?? ($ggrStores + $recStores + $withStores));
$totalOp = (float) ($stats['total_operator_profit'] ?? ($ggrOp + $recOp + $withOp));
?>
<div class="operator-pane-inner operator-pane-inner-commissions" id="operator-stores-commissions-root">
    <div class="operator-panel-pane-head">
        <div class="operator-panel-pane-icon operator-panel-pane-icon-commissions">
            <i class="fa-duotone fa-solid fa-store"></i>
        </div>
        <div>
            <h5 class="mb-1"><?= translate('stores commissions panel'); ?></h5>
            <p class="small text-muted mb-0"><?= translate('stores commissions panel description'); ?></p>
            <?php if (bingo_ggr_pays_monthly()) : ?>
                <p class="small text-info mb-0 mt-1"><?= translate('ggr monthly settlement note'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-2 align-items-end mb-3 operator-stores-commissions-filters">
        <div class="col-md-4 col-sm-6">
            <label class="form-label small mb-1" for="operator-stores-date-from"><?= translate('from date'); ?></label>
            <input type="date" class="form-control form-bingo" id="operator-stores-date-from" value="<?= esc($dateFrom); ?>">
        </div>
        <div class="col-md-4 col-sm-6">
            <label class="form-label small mb-1" for="operator-stores-date-to"><?= translate('to date'); ?></label>
            <input type="date" class="form-control form-bingo" id="operator-stores-date-to" value="<?= esc($dateTo); ?>">
        </div>
        <div class="col-md-4 col-sm-12 d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-light" id="operator-stores-commissions-clear" style="border-color: rgba(255,255,255,0.25);">
                <i class="fa-duotone fa-solid fa-xmark"></i> <?= translate('clear filters'); ?>
            </button>
        </div>
    </div>

    <!-- Totales de las 3 comisiones: Puntos de venta + Operador -->
    <div class="row g-2 mb-3">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm p-2 h-100" style="border-radius: 10px; background: linear-gradient(135deg, rgba(255,193,7,0.12) 0%, rgba(255,193,7,0.02) 100%); border-left: 3.5px solid #ffc107 !important;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-uppercase fw-bold text-muted" style="font-size: 0.70rem; letter-spacing: 0.3px;">GGR Afiliados</span>
                    <span class="badge bg-warning-subtle text-dark border border-warning py-0 px-1" style="font-size: 0.68rem;"><?= number_format($ggrRate, 2); ?>%</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-1" style="font-size: 0.78rem;">
                    <div>
                        <span class="text-muted d-block" style="font-size: 0.68rem;">Puntos de venta</span>
                        <strong class="text-secondary"><?= $currency; ?> <?= number_format($ggrStores, 2); ?></strong>
                    </div>
                    <div class="text-end">
                        <span class="text-success d-block" style="font-size: 0.68rem;">Operador</span>
                        <strong class="text-success">+<?= $currency; ?> <?= number_format($ggrOp, 2); ?></strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm p-2 h-100" style="border-radius: 10px; background: linear-gradient(135deg, rgba(13,202,240,0.12) 0%, rgba(13,202,240,0.02) 100%); border-left: 3.5px solid #0dcaf0 !important;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-uppercase fw-bold text-muted" style="font-size: 0.70rem; letter-spacing: 0.3px;">Recargas</span>
                    <span class="badge bg-info-subtle text-info-emphasis border border-info py-0 px-1" style="font-size: 0.68rem;"><?= number_format($recRate, 2); ?>%</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-1" style="font-size: 0.78rem;">
                    <div>
                        <span class="text-muted d-block" style="font-size: 0.68rem;">Puntos de venta</span>
                        <strong class="text-secondary"><?= $currency; ?> <?= number_format($recStores, 2); ?></strong>
                    </div>
                    <div class="text-end">
                        <span class="text-info d-block" style="font-size: 0.68rem;">Operador</span>
                        <strong class="text-info">+<?= $currency; ?> <?= number_format($recOp, 2); ?></strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm p-2 h-100" style="border-radius: 10px; background: linear-gradient(135deg, rgba(220,53,69,0.12) 0%, rgba(220,53,69,0.02) 100%); border-left: 3.5px solid #dc3545 !important;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-uppercase fw-bold text-muted" style="font-size: 0.70rem; letter-spacing: 0.3px;">Retiros</span>
                    <span class="badge bg-danger-subtle text-danger border border-danger py-0 px-1" style="font-size: 0.68rem;"><?= number_format($withRate, 2); ?>%</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-1" style="font-size: 0.78rem;">
                    <div>
                        <span class="text-muted d-block" style="font-size: 0.68rem;">Puntos de venta</span>
                        <strong class="text-secondary"><?= $currency; ?> <?= number_format($withStores, 2); ?></strong>
                    </div>
                    <div class="text-end">
                        <span class="text-danger d-block" style="font-size: 0.68rem;">Operador</span>
                        <strong class="text-danger">+<?= $currency; ?> <?= number_format($withOp, 2); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="operator-commissions-stats mb-3">
        <div class="operator-affiliate-stat">
            <span><?= translate('points of sale'); ?></span>
            <strong><?= (int) ($storesCommissions['store_count'] ?? 0); ?></strong>
        </div>
        <div class="operator-affiliate-stat">
            <span>Total Puntos de venta</span>
            <strong><?= $currency; ?> <?= number_format($totalStores, 2); ?></strong>
        </div>
        <div class="operator-affiliate-stat">
            <span>Total Operador</span>
            <strong><?= $currency; ?> <?= number_format($totalOp, 2); ?></strong>
        </div>
        <?php if (bingo_ggr_affiliate_active()) : ?>
        <div class="operator-affiliate-stat">
            <span><?= translate('ggr generated'); ?></span>
            <strong><?= $currency; ?> <?= number_format((float) ($storesCommissions['total_ggr'] ?? 0), 2); ?></strong>
        </div>
        <div class="operator-affiliate-stat">
            <span><?= translate('pending commissions'); ?></span>
            <strong><?= $currency; ?> <?= number_format((float) ($storesCommissions['pending_commission'] ?? 0), 2); ?></strong>
        </div>
        <?php endif; ?>
    </div>

    <?php if (bingo_ggr_affiliate_active() && ! empty($storesCommissions['chart'])) : ?>
    <div class="operator-commissions-chart-wrap">
        <canvas id="operator-stores-ggr-chart"></canvas>
    </div>
    <?php endif; ?>

    <div class="operator-commissions-history">
        <h6 class="operator-commissions-history-title"><?= translate('stores commission breakdown'); ?></h6>
        <div class="store-table-wrap">
            <?php if (! empty($storesCommissions['stores'])) : ?>
            <table class="table table-sm store-table mb-0">
                <thead>
                    <tr>
                        <th><?= translate('business name'); ?></th>
                        <th>Recargas<br><small class="fw-normal text-muted">PV / Op</small></th>
                        <th>Retiros<br><small class="fw-normal text-muted">PV / Op</small></th>
                        <th>GGR<br><small class="fw-normal text-muted">PV / Op</small></th>
                        <th>Total PV</th>
                        <th>Total Operador</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($storesCommissions['stores'] as $storeRow) : ?>
                    <tr>
                        <td>
                            <strong><?= esc($storeRow['name'] ?? '-'); ?></strong>
                            <?php if (! empty($storeRow['code'])) : ?>
                                <br><small class="text-muted"><?= esc($storeRow['code']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $currency; ?> <?= number_format((float) ($storeRow['recharge_store'] ?? 0), 2); ?>
                            <br><small class="text-info">+<?= $currency; ?> <?= number_format((float) ($storeRow['recharge_operator'] ?? 0), 2); ?></small>
                        </td>
                        <td>
                            <?= $currency; ?> <?= number_format((float) ($storeRow['withdraw_store'] ?? 0), 2); ?>
                            <br><small class="text-danger">+<?= $currency; ?> <?= number_format((float) ($storeRow['withdraw_operator'] ?? 0), 2); ?></small>
                        </td>
                        <td>
                            <?= $currency; ?> <?= number_format((float) ($storeRow['ggr_store'] ?? $storeRow['ggr_commissions'] ?? 0), 2); ?>
                            <br><small class="text-success">+<?= $currency; ?> <?= number_format((float) ($storeRow['ggr_operator'] ?? 0), 2); ?></small>
                        </td>
                        <td><strong><?= $currency; ?> <?= number_format((float) ($storeRow['three_total_store'] ?? 0), 2); ?></strong></td>
                        <td><strong class="text-success"><?= $currency; ?> <?= number_format((float) ($storeRow['three_total_operator'] ?? 0), 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Totales</th>
                        <th>
                            <?= $currency; ?> <?= number_format($recStores, 2); ?>
                            <br><small class="text-info">+<?= $currency; ?> <?= number_format($recOp, 2); ?></small>
                        </th>
                        <th>
                            <?= $currency; ?> <?= number_format($withStores, 2); ?>
                            <br><small class="text-danger">+<?= $currency; ?> <?= number_format($withOp, 2); ?></small>
                        </th>
                        <th>
                            <?= $currency; ?> <?= number_format($ggrStores, 2); ?>
                            <br><small class="text-success">+<?= $currency; ?> <?= number_format($ggrOp, 2); ?></small>
                        </th>
                        <th><strong><?= $currency; ?> <?= number_format($totalStores, 2); ?></strong></th>
                        <th><strong class="text-success"><?= $currency; ?> <?= number_format($totalOp, 2); ?></strong></th>
                    </tr>
                </tfoot>
            </table>
            <?php else : ?>
            <p class="text-muted small mb-0"><?= translate('no points of sale assigned yet'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
