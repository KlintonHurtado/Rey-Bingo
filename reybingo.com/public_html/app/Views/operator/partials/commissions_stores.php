<?php
$storesCommissions = $storesCommissions ?? [];
$currency = esc(systemGet('currency'));
$dateFrom = (string) ($storesCommissions['date_from'] ?? date('Y-m-d', strtotime('-30 days')));
$dateTo = (string) ($storesCommissions['date_to'] ?? date('Y-m-d'));

$stats = $storesCommissions['commission_stats'] ?? [];
$totalStores = (float) ($stats['total_stores_earned'] ?? 0);
$totalOp = (float) ($stats['total_operator_profit'] ?? 0);

if ($totalStores <= 0 && $totalOp <= 0) {
    $totalStores = 0.0;
    $totalOp = 0.0;
    foreach ($storesCommissions['stores'] ?? [] as $row) {
        $totalStores += (float) ($row['three_total_store'] ?? $row['total_commission'] ?? 0);
        $totalOp += (float) ($row['three_total_operator'] ?? 0);
    }
}
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
        <div class="operator-affiliate-stat">
            <span>Total general</span>
            <strong><?= $currency; ?> <?= number_format($totalStores + $totalOp, 2); ?></strong>
        </div>
    </div>

    <div class="operator-commissions-history">
        <h6 class="operator-commissions-history-title"><?= translate('points of sale'); ?></h6>
        <div class="store-table-wrap">
            <?php if (! empty($storesCommissions['stores'])) : ?>
            <table class="table table-sm store-table mb-0">
                <thead>
                    <tr>
                        <th><?= translate('business name'); ?></th>
                        <th>Total PV</th>
                        <th>Total Operador</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($storesCommissions['stores'] as $storeRow) :
                        $pvTotal = (float) ($storeRow['three_total_store'] ?? $storeRow['total_commission'] ?? 0);
                        $opTotal = (float) ($storeRow['three_total_operator'] ?? 0);
                        ?>
                    <tr>
                        <td>
                            <strong><?= esc($storeRow['name'] ?? '-'); ?></strong>
                            <?php if (! empty($storeRow['code'])) : ?>
                                <br><small class="text-muted"><?= esc($storeRow['code']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= $currency; ?> <?= number_format($pvTotal, 2); ?></strong></td>
                        <td><strong class="text-success"><?= $currency; ?> <?= number_format($opTotal, 2); ?></strong></td>
                        <td><strong><?= $currency; ?> <?= number_format($pvTotal + $opTotal, 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Totales</th>
                        <th><strong><?= $currency; ?> <?= number_format($totalStores, 2); ?></strong></th>
                        <th><strong class="text-success"><?= $currency; ?> <?= number_format($totalOp, 2); ?></strong></th>
                        <th><strong><?= $currency; ?> <?= number_format($totalStores + $totalOp, 2); ?></strong></th>
                    </tr>
                </tfoot>
            </table>
            <?php else : ?>
            <p class="text-muted small mb-0"><?= translate('no points of sale assigned yet'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
