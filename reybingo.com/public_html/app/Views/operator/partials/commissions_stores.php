<?php
$storesCommissions = $storesCommissions ?? [];
$currency = esc(systemGet('currency'));
$dateFrom = (string) ($storesCommissions['date_from'] ?? date('Y-m-d', strtotime('-30 days')));
$dateTo = (string) ($storesCommissions['date_to'] ?? date('Y-m-d'));

$totRecargasBase = (float) ($storesCommissions['total_recargas_base'] ?? 0);
$totRecargasComision = (float) ($storesCommissions['total_recargas_comision'] ?? 0);
$totRetirosBase = (float) ($storesCommissions['total_retiros_base'] ?? 0);
$totRetirosComision = (float) ($storesCommissions['total_retiros_comision'] ?? 0);
$totApuestasAfiliados = (float) ($storesCommissions['total_apuestas_afiliados'] ?? 0);
$totPremiosAfiliados = (float) ($storesCommissions['total_premios_afiliados'] ?? 0);
$totGgrBase = (float) ($storesCommissions['total_ggr_base'] ?? 0);
$totGgrComision = (float) ($storesCommissions['total_ggr_comision'] ?? 0);
$totGranTotalComision = (float) ($storesCommissions['grand_total_comision'] ?? 0);

if ($totRecargasBase <= 0 && $totRecargasComision <= 0 && $totRetirosBase <= 0 && $totRetirosComision <= 0 && $totGgrBase <= 0 && $totGgrComision <= 0) {
    foreach ($storesCommissions['stores'] ?? [] as $row) {
        $totRecargasBase += (float) ($row['recharge_base'] ?? 0);
        $totRecargasComision += (float) ($row['recharge_store'] ?? 0);
        $totRetirosBase += (float) ($row['withdraw_base'] ?? 0);
        $totRetirosComision += (float) ($row['withdraw_store'] ?? 0);
        $totApuestasAfiliados += (float) ($row['affiliate_stakes'] ?? 0);
        $totPremiosAfiliados += (float) ($row['affiliate_payouts'] ?? 0);
        $totGgrBase += (float) ($row['ggr_base'] ?? $row['total_ggr'] ?? 0);
        $totGgrComision += (float) ($row['ggr_store'] ?? $row['ggr_commissions'] ?? 0);
        $totGranTotalComision += (float) ($row['total_commission'] ?? 0);
    }
}
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
        <div class="store-table-wrap table-responsive">
            <?php if (! empty($storesCommissions['stores'])) : ?>
            <table class="table table-sm store-table mb-0 text-nowrap align-middle">
                <thead>
                    <tr>
                        <th><?= translate('business name'); ?></th>
                        <th class="text-end">Total Recargas</th>
                        <th class="text-end">Comisión Recargas</th>
                        <th class="text-end">Total Retiros</th>
                        <th class="text-end">Comisión Retiros</th>
                        <th class="text-end">Total Ap Afiliados</th>
                        <th class="text-end">Total Premios Afiliados</th>
                        <th class="text-end">GGR</th>
                        <th class="text-end">Comisión GGR</th>
                        <th class="text-end">TOTAL COMISIÓN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($storesCommissions['stores'] as $storeRow) :
                        $recBase = (float) ($storeRow['recharge_base'] ?? 0);
                        $recCom  = (float) ($storeRow['recharge_store'] ?? 0);
                        $retBase = (float) ($storeRow['withdraw_base'] ?? 0);
                        $retCom  = (float) ($storeRow['withdraw_store'] ?? 0);
                        $apAf    = (float) ($storeRow['affiliate_stakes'] ?? 0);
                        $prAf    = (float) ($storeRow['affiliate_payouts'] ?? 0);
                        $ggrBase = (float) ($storeRow['ggr_base'] ?? $storeRow['total_ggr'] ?? 0);
                        $ggrCom  = (float) ($storeRow['ggr_store'] ?? $storeRow['ggr_commissions'] ?? 0);
                        $rowTotal = (float) ($storeRow['total_commission'] ?? ($recCom + $retCom + $ggrCom));
                        ?>
                    <tr>
                        <td>
                            <strong><?= esc($storeRow['name'] ?? '-'); ?></strong>
                            <?php if (! empty($storeRow['code'])) : ?>
                                <br><small class="text-muted"><?= esc($storeRow['code']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?= $currency; ?> <?= number_format($recBase, 2); ?></td>
                        <td class="text-end"><strong><?= $currency; ?> <?= number_format($recCom, 2); ?></strong></td>
                        <td class="text-end"><?= $currency; ?> <?= number_format($retBase, 2); ?></td>
                        <td class="text-end"><strong><?= $currency; ?> <?= number_format($retCom, 2); ?></strong></td>
                        <td class="text-end"><?= $currency; ?> <?= number_format($apAf, 2); ?></td>
                        <td class="text-end"><?= $currency; ?> <?= number_format($prAf, 2); ?></td>
                        <td class="text-end"><?= $currency; ?> <?= number_format($ggrBase, 2); ?></td>
                        <td class="text-end"><strong><?= $currency; ?> <?= number_format($ggrCom, 2); ?></strong></td>
                        <td class="text-end"><strong class="text-success"><?= $currency; ?> <?= number_format($rowTotal, 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <th><strong>Totales</strong></th>
                        <th class="text-end"><strong><?= $currency; ?> <?= number_format($totRecargasBase, 2); ?></strong></th>
                        <th class="text-end"><strong><?= $currency; ?> <?= number_format($totRecargasComision, 2); ?></strong></th>
                        <th class="text-end"><strong><?= $currency; ?> <?= number_format($totRetirosBase, 2); ?></strong></th>
                        <th class="text-end"><strong><?= $currency; ?> <?= number_format($totRetirosComision, 2); ?></strong></th>
                        <th class="text-end"><strong><?= $currency; ?> <?= number_format($totApuestasAfiliados, 2); ?></strong></th>
                        <th class="text-end"><strong><?= $currency; ?> <?= number_format($totPremiosAfiliados, 2); ?></strong></th>
                        <th class="text-end"><strong><?= $currency; ?> <?= number_format($totGgrBase, 2); ?></strong></th>
                        <th class="text-end"><strong><?= $currency; ?> <?= number_format($totGgrComision, 2); ?></strong></th>
                        <th class="text-end"><strong class="text-success"><?= $currency; ?> <?= number_format($totGranTotalComision, 2); ?></strong></th>
                    </tr>
                </tfoot>
            </table>
            <?php else : ?>
            <p class="text-muted small mb-0"><?= translate('no points of sale assigned yet'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
