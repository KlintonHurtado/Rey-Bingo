<?php
$storesCommissions = $storesCommissions ?? [];
$currency = esc(systemGet('currency'));
$dateFrom = (string) ($storesCommissions['date_from'] ?? date('Y-m-d', strtotime('-30 days')));
$dateTo = (string) ($storesCommissions['date_to'] ?? date('Y-m-d'));
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

    <div class="operator-commissions-stats">
        <div class="operator-affiliate-stat">
            <span><?= translate('points of sale'); ?></span>
            <strong><?= (int) ($storesCommissions['store_count'] ?? 0); ?></strong>
        </div>
        <div class="operator-affiliate-stat">
            <span><?= translate('total commissions earned'); ?></span>
            <strong><?= $currency ?> <?= number_format((float) ($storesCommissions['total_commission'] ?? 0), 2); ?></strong>
        </div>
        <div class="operator-affiliate-stat">
            <span><?= translate('affiliate commissions earned'); ?></span>
            <strong><?= $currency ?> <?= number_format((float) ($storesCommissions['affiliate_commissions'] ?? 0), 2); ?></strong>
        </div>
        <?php if (bingo_ggr_affiliate_active()) : ?>
        <div class="operator-affiliate-stat">
            <span><?= translate('ggr commissions earned'); ?></span>
            <strong><?= $currency ?> <?= number_format((float) ($storesCommissions['ggr_commissions'] ?? 0), 2); ?></strong>
        </div>
        <div class="operator-affiliate-stat">
            <span><?= translate('ggr generated'); ?></span>
            <strong><?= $currency ?> <?= number_format((float) ($storesCommissions['total_ggr'] ?? 0), 2); ?></strong>
        </div>
        <div class="operator-affiliate-stat">
            <span><?= translate('pending commissions'); ?></span>
            <strong><?= $currency ?> <?= number_format((float) ($storesCommissions['pending_commission'] ?? 0), 2); ?></strong>
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
                        <?php if (bingo_ggr_affiliate_active()) : ?>
                        <th><?= translate('ggr rate short'); ?></th>
                        <th><?= translate('operator margin short'); ?></th>
                        <?php endif; ?>
                        <th><?= translate('affiliate commissions short'); ?></th>
                        <?php if (bingo_ggr_affiliate_active()) : ?>
                        <th><?= translate('ggr commissions short'); ?></th>
                        <th><?= translate('ggr generated short'); ?></th>
                        <?php endif; ?>
                        <th><?= translate('total commissions short'); ?></th>
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
                        <?php if (bingo_ggr_affiliate_active()) : ?>
                        <td><?= number_format((float) ($storeRow['ggr_rate'] ?? 0) * 100, 2); ?>%</td>
                        <td><?= number_format((float) ($storeRow['operator_margin_rate'] ?? 0) * 100, 2); ?>%</td>
                        <?php endif; ?>
                        <td><?= $currency ?> <?= number_format((float) ($storeRow['affiliate_commissions'] ?? 0), 2); ?></td>
                        <?php if (bingo_ggr_affiliate_active()) : ?>
                        <td><?= $currency ?> <?= number_format((float) ($storeRow['ggr_commissions'] ?? 0), 2); ?></td>
                        <td><?= $currency ?> <?= number_format((float) ($storeRow['total_ggr'] ?? 0), 2); ?></td>
                        <?php endif; ?>
                        <td><strong><?= $currency ?> <?= number_format((float) ($storeRow['total_commission'] ?? 0), 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else : ?>
            <p class="text-muted small mb-0"><?= translate('no points of sale assigned yet'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
