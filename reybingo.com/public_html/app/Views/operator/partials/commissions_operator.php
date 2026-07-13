<?php
$operatorCommissions = $operatorCommissions ?? [];
$ggrDashboard = $operatorCommissions['ggr_dashboard'] ?? [];
$currency = esc(systemGet('currency'));
?>
<div class="operator-pane-inner operator-pane-inner-commissions">
    <div class="operator-panel-pane-head">
        <div class="operator-panel-pane-icon operator-panel-pane-icon-commissions">
            <i class="fa-duotone fa-solid fa-chart-line"></i>
        </div>
        <div>
            <h5 class="mb-1"><?= translate('operator commissions panel'); ?></h5>
            <p class="small text-muted mb-0"><?= translate('operator commissions panel description'); ?></p>
            <?php if (bingo_ggr_pays_monthly()) : ?>
                <p class="small text-info mb-0 mt-1"><?= translate('ggr monthly settlement note'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="operator-commissions-stats">
        <div class="operator-affiliate-stat">
            <span><?= translate('total commissions earned'); ?></span>
            <strong><?= $currency ?> <?= number_format((float) ($operatorCommissions['total_commission'] ?? 0), 2); ?></strong>
        </div>
        <?php if (bingo_ggr_affiliate_active()) : ?>
        <div class="operator-affiliate-stat">
            <span><?= translate('ggr commissions earned'); ?></span>
            <strong><?= $currency ?> <?= number_format((float) ($operatorCommissions['ggr_commissions'] ?? 0), 2); ?></strong>
        </div>
        <div class="operator-affiliate-stat">
            <span><?= translate('ggr generated'); ?></span>
            <strong><?= $currency ?> <?= number_format((float) ($operatorCommissions['total_ggr'] ?? 0), 2); ?></strong>
        </div>
        <div class="operator-affiliate-stat">
            <span><?= translate('pending commissions'); ?></span>
            <strong><?= $currency ?> <?= number_format((float) ($operatorCommissions['pending_commission'] ?? 0), 2); ?></strong>
        </div>
        <div class="operator-affiliate-stat">
            <span><?= translate('operator ggr total rate'); ?></span>
            <strong><?= number_format((float) ($operatorCommissions['ggr_rate'] ?? 0) * 100, 2); ?>%</strong>
        </div>
        <div class="operator-affiliate-stat">
            <span><?= translate('operator ggr margin note'); ?></span>
            <strong class="small"><?= translate('operator ggr margin hint'); ?></strong>
        </div>
        <?php endif; ?>
        <div class="operator-affiliate-stat">
            <span><?= translate('affiliated stores'); ?></span>
            <strong><?= (int) ($operatorCommissions['affiliated_stores'] ?? $operatorCommissions['referred_operators'] ?? 0); ?></strong>
        </div>
    </div>

    <?php if (bingo_ggr_affiliate_active() && ! empty($ggrDashboard['chart'])) : ?>
    <div class="operator-commissions-chart-wrap">
        <canvas id="operator-ggr-chart"></canvas>
    </div>
    <?php endif; ?>

    <?php if (bingo_ggr_affiliate_active() && ! empty($ggrDashboard['history'])) : ?>
    <div class="operator-commissions-history">
        <h6 class="operator-commissions-history-title"><?= translate('recent ggr commissions'); ?></h6>
        <div class="store-table-wrap">
            <table class="table table-sm store-table mb-0">
                <thead>
                    <tr>
                        <th><?= translate('player'); ?></th>
                        <th><?= translate('ggr generated'); ?></th>
                        <th><?= translate('commission'); ?></th>
                        <th><?= translate('status'); ?></th>
                        <th><?= translate('date'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($ggrDashboard['history'], 0, 20) as $row) : ?>
                    <tr>
                        <td><?= esc($row['player'] ?? '-'); ?></td>
                        <td><?= $currency ?> <?= number_format((float) ($row['ggr'] ?? 0), 2); ?></td>
                        <td><?= $currency ?> <?= number_format((float) ($row['commission'] ?? 0), 2); ?></td>
                        <td>
                            <?php if ((int) ($row['status'] ?? 0) === 2) : ?>
                                <span class="badge bg-success"><?= translate('paid'); ?></span>
                            <?php elseif ((int) ($row['status'] ?? 0) === 3) : ?>
                                <span class="badge bg-secondary"><?= translate('rejected'); ?></span>
                            <?php else : ?>
                                <span class="badge bg-warning text-dark"><?= translate('pending'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= ! empty($row['date']) ? esc(date('d/m/Y H:i', strtotime($row['date']))) : '—'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php elseif (! bingo_ggr_affiliate_active()) : ?>
    <p class="small text-muted mb-0"><?= translate('operator ggr commissions inactive note'); ?></p>
    <?php endif; ?>
</div>
