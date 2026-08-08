<?php
$operatorCommissions = $operatorCommissions ?? [];
$currency = esc(systemGet('currency'));
$affiliateRate = (float) ($operatorCommissions['affiliate_rate'] ?? $operatorCommissions['operator_rate'] ?? 0);
$rechargeRate = (float) ($operatorCommissions['recharge_rate'] ?? 0);
$withdrawRate = (float) ($operatorCommissions['withdraw_rate'] ?? 0);
$affiliateEarned = (float) ($operatorCommissions['ggr_commissions'] ?? 0);
?>
<div class="operator-pane-inner operator-pane-inner-commissions">
    <div class="operator-panel-pane-head">
        <div class="operator-panel-pane-icon operator-panel-pane-icon-commissions">
            <i class="fa-duotone fa-solid fa-chart-line"></i>
        </div>
        <div>
            <h5 class="mb-1"><?= translate('operator commissions panel'); ?></h5>
            <p class="small text-muted mb-0"><?= translate('operator commissions three rates description'); ?></p>
        </div>
    </div>

    <div class="operator-commissions-stats operator-commissions-stats-three">
        <div class="operator-affiliate-stat">
            <span><?= translate('operator ggr affiliate rate'); ?></span>
            <strong><?= number_format($affiliateRate * 100, 2); ?>%</strong>
            <small class="d-block text-muted mt-1">
                <?= translate('earned'); ?>:
                <?= $currency ?> <?= number_format($affiliateEarned, 2); ?>
            </small>
        </div>
        <div class="operator-affiliate-stat">
            <span><?= translate('operator recharge rate'); ?></span>
            <strong><?= number_format($rechargeRate * 100, 2); ?>%</strong>
        </div>
        <div class="operator-affiliate-stat">
            <span><?= translate('operator withdraw rate'); ?></span>
            <strong><?= number_format($withdrawRate * 100, 2); ?>%</strong>
        </div>
    </div>
</div>
