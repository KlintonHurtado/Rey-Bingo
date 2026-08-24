<?php
$storesCommissions = $storesCommissions ?? [];
$currency = esc(systemGet('currency'));
$dateFrom = (string) ($storesCommissions['date_from'] ?? date('Y-m-d', strtotime('-30 days')));
$dateTo = (string) ($storesCommissions['date_to'] ?? date('Y-m-d'));

$stats = $storesCommissions['commission_stats'] ?? [];

$totalRecargas = (float) ($stats['recharge']['stores_earned'] ?? 0);
$totalRetiros = (float) ($stats['withdraw']['stores_earned'] ?? 0);
$totalGgr = (float) ($stats['ggr']['stores_earned'] ?? 0);

if ($totalRecargas <= 0 && $totalRetiros <= 0 && $totalGgr <= 0) {
    foreach ($storesCommissions['stores'] ?? [] as $row) {
        $totalRecargas += (float) ($row['recharge_store'] ?? 0);
        $totalRetiros += (float) ($row['withdraw_store'] ?? 0);
        $totalGgr += (float) ($row['ggr_store'] ?? $row['ggr_commissions'] ?? 0);
    }
}

$totalPv = round($totalRecargas + $totalRetiros + $totalGgr, 2);
?>
<div class="operator-pane-inner operator-pane-inner-commissions" id="operator-stores-commissions-root">
    <div class="operator-panel-pane-head mb-3">
        <div class="operator-panel-pane-icon operator-panel-pane-icon-commissions">
            <i class="fa-duotone fa-solid fa-store"></i>
        </div>
        <div class="flex-grow-1">
            <h5 class="mb-1"><?= translate('stores commissions panel'); ?></h5>
            <p class="small text-muted mb-0"><?= translate('stores commissions panel description'); ?></p>
        </div>
    </div>

    <div class="row g-2 align-items-end mb-3 operator-stores-commissions-filters">
        <div class="col-md-3 col-sm-6">
            <label class="form-label small mb-1" for="operator-stores-date-from"><?= translate('from date'); ?></label>
            <input type="date" class="form-control form-bingo" id="operator-stores-date-from" value="<?= esc($dateFrom); ?>">
        </div>
        <div class="col-md-3 col-sm-6">
            <label class="form-label small mb-1" for="operator-stores-date-to"><?= translate('to date'); ?></label>
            <input type="date" class="form-control form-bingo" id="operator-stores-date-to" value="<?= esc($dateTo); ?>">
        </div>
        <div class="col-md-6 col-sm-12 d-flex flex-wrap gap-2 align-items-center">
            <button type="button" class="btn btn-outline-secondary" id="operator-stores-commissions-clear">
                <i class="fa-duotone fa-solid fa-xmark"></i> <?= translate('clear filters'); ?>
            </button>
            <button
                type="button"
                class="btn btn-success"
                id="btn-export-stores-commissions"
                style="background-color:#198754 !important; border-color:#198754 !important; color:#fff !important; font-weight:600;"
            >
                <i class="fa-duotone fa-solid fa-file-excel me-1"></i> Descargar Excel
            </button>
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
                        <th>Total Recargas</th>
                        <th>Total Retiros</th>
                        <th>Total GGR</th>
                        <th>Total comisiones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($storesCommissions['stores'] as $storeRow) :
                        $rec = (float) ($storeRow['recharge_store'] ?? 0);
                        $ret = (float) ($storeRow['withdraw_store'] ?? 0);
                        $ggr = (float) ($storeRow['ggr_store'] ?? $storeRow['ggr_commissions'] ?? 0);
                        $rowTotal = round($rec + $ret + $ggr, 2);
                        ?>
                    <tr>
                        <td>
                            <strong><?= esc($storeRow['name'] ?? '-'); ?></strong>
                            <?php if (! empty($storeRow['code'])) : ?>
                                <br><small class="text-muted"><?= esc($storeRow['code']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= $currency; ?> <?= number_format($rec, 2); ?></strong></td>
                        <td><strong><?= $currency; ?> <?= number_format($ret, 2); ?></strong></td>
                        <td><strong><?= $currency; ?> <?= number_format($ggr, 2); ?></strong></td>
                        <td><strong><?= $currency; ?> <?= number_format($rowTotal, 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Totales</th>
                        <th><strong><?= $currency; ?> <?= number_format($totalRecargas, 2); ?></strong></th>
                        <th><strong><?= $currency; ?> <?= number_format($totalRetiros, 2); ?></strong></th>
                        <th><strong><?= $currency; ?> <?= number_format($totalGgr, 2); ?></strong></th>
                        <th><strong><?= $currency; ?> <?= number_format($totalPv, 2); ?></strong></th>
                    </tr>
                </tfoot>
            </table>
            <?php else : ?>
            <p class="text-muted small mb-0"><?= translate('no points of sale assigned yet'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
