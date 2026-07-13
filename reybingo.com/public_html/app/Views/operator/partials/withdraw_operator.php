<?php
$currency = esc(systemGet('currency'));
$earningsSummary = $earningsSummary ?? [];
$displayTotal = (float) ($earningsSummary['display_total'] ?? $withdrawable ?? 0);
$withdrawable = (float) ($earningsSummary['wallet_withdraw'] ?? $withdrawable ?? 0);
$canWithdraw = ! empty($retireEnabled) && ! empty($earningsSummary['can_withdraw']);
$blockedReason = (string) ($earningsSummary['withdraw_blocked_reason'] ?? '');
?>

<div class="operator-pane-inner operator-pane-inner-withdraw">
    <div class="operator-panel-pane-head">
        <div class="operator-panel-pane-icon operator-panel-pane-icon-withdraw">
            <i class="fa-duotone fa-solid fa-arrow-up-from-bracket"></i>
        </div>
        <div>
            <h5 class="mb-1"><?= translate('withdraw operator earnings'); ?></h5>
            <p class="small text-muted mb-0"><?= translate('withdraw operator earnings description'); ?></p>
        </div>
    </div>

    <div class="store-tab-form store-withdraw-form mt-4" style="max-width: 480px;">
        <div class="store-withdraw-balance mb-3">
            <span class="store-balance-label d-block"><?= translate('withdrawable earnings'); ?></span>
            <strong class="store-withdraw-balance-amount" style="font-size: 2.25rem; font-weight: 800; color: #198754;"><?= $currency ?> <?= number_format($displayTotal, 2); ?></strong>
        </div>

        <?php if (! empty($earningsSummary['monthly_mode']) && ($earningsSummary['pending_ggr'] ?? 0) > 0) : ?>
            <p class="text-muted small mb-2">
                <?= translate('store earnings available to withdraw now'); ?>:
                <strong><?= $currency ?> <?= number_format($withdrawable, 2); ?></strong>
            </p>
        <?php endif; ?>

        <?php if (empty($retireEnabled)) : ?>
            <p class="text-warning small mb-0"><?= translate('retire disabled by admin'); ?></p>
        <?php elseif ($displayTotal <= 0) : ?>
            <p class="text-muted small mb-3"><?= translate('no withdrawable earnings yet'); ?></p>
        <?php elseif (! $canWithdraw) : ?>
            <p class="text-muted small mb-3"><?= translate($blockedReason !== '' ? $blockedReason : 'store earnings withdraw monthly notice'); ?></p>
        <?php else : ?>
            <button type="button" class="btn btn-primary btn-bingo w-100 mb-2 py-3" style="font-size: 1.1rem; font-weight: 600;" onclick="retireGet();">
                <i class="fa-duotone fa-solid fa-arrow-up-from-bracket"></i> <?= translate('request retire'); ?>
            </button>
            <?php if (($minimumRetire ?? 0) > 0 || ($maximumRetire ?? 0) > 0) : ?>
                <p class="text-muted small mb-0">
                    <?php if (($minimumRetire ?? 0) > 0) : ?>
                        <?= translate('minimum retire amount'); ?>: <?= $currency ?> <?= number_format((float) $minimumRetire, 2); ?>
                    <?php endif; ?>
                    <?php if (($maximumRetire ?? 0) > 0) : ?>
                        <?= ($minimumRetire ?? 0) > 0 ? ' · ' : '' ?>
                        <?= translate('maximum retire amount'); ?>: <?= $currency ?> <?= number_format((float) $maximumRetire, 2); ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="store-tab-history store-withdraw-history mt-5">
        <h6 class="store-tab-history-title mb-3" style="font-size: 1rem; font-weight: 700; border-bottom: 2px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
            <i class="fa-duotone fa-solid fa-clock-rotate-left"></i> <?= translate('withdrawal history'); ?>
        </h6>
        <div class="store-table-wrap">
            <?php if (! empty($retires)) : ?>
                <table class="table table-striped table-sm mb-0 text-center align-middle">
                    <thead>
                        <tr>
                            <th><?= translate('date'); ?></th>
                            <th><?= translate('amount'); ?></th>
                            <th><?= translate('bank'); ?></th>
                            <th><?= translate('status'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($retires as $retire) : ?>
                            <tr>
                                <td><small><?= esc(date('d/m/Y H:i', strtotime($retire['created_at'] ?? 'now'))); ?></small></td>
                                <td><strong><?= $currency ?> <?= number_format((float) ($retire['amount'] ?? 0), 2); ?></strong></td>
                                <td><small><?= esc($retire['bank'] ?? '-'); ?></small></td>
                                <td><?= $retire['status_label'] ?? ''; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="text-muted small mb-0"><?= translate('no records found'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
