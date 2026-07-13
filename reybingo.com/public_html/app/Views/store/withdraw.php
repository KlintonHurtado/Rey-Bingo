<?= view('store/partials/open', [
    'imagePath' => $imagePath,
    'walletSummary' => $walletSummary,
    'pendingPrizes' => $pendingPrizes ?? 0,
    'activeNav' => 'withdraw',
]) ?>

<?php
$currency = esc(systemGet('currency'));
$earningsSummary = $earningsSummary ?? [];
$displayTotal = (float) ($earningsSummary['display_total'] ?? $withdrawable ?? 0);
$withdrawable = (float) ($earningsSummary['wallet_withdraw'] ?? $withdrawable ?? 0);
$canWithdraw = ! empty($retireEnabled) && ! empty($earningsSummary['can_withdraw']);
$blockedReason = (string) ($earningsSummary['withdraw_blocked_reason'] ?? '');
?>

<div class="card store-panel-card h-100">
    <div class="card-body store-tab-body store-withdraw-tab-body">
        <div class="store-tab-form store-withdraw-form">
            <h6 class="store-tab-form-title">
                <i class="fa-duotone fa-solid fa-arrow-up-from-bracket"></i> <?= translate('withdraw store earnings'); ?>
            </h6>

            <div class="store-withdraw-balance mb-3">
                <span class="store-balance-label d-block"><?= translate('withdrawable earnings'); ?></span>
                <strong class="store-withdraw-balance-amount"><?= $currency ?> <?= number_format($displayTotal, 2); ?></strong>
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
                <button type="button" class="btn btn-primary btn-bingo w-100 mb-2" onclick="retireGet();">
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

        <div class="store-tab-history store-withdraw-history">
            <h6 class="store-tab-history-title">
                <i class="fa-duotone fa-solid fa-clock-rotate-left"></i> <?= translate('withdrawal history'); ?>
            </h6>
            <div class="store-table-wrap">
                <?php if (! empty($retires)) : ?>
                    <table class="table table-striped table-sm mb-0">
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
</div>

<?= view('store/partials/close') ?>
<?= view('store/partials/scripts_common') ?>
