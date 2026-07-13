<?= view('store/partials/open', [
    'imagePath' => $imagePath,
    'walletSummary' => $walletSummary,
    'pendingPrizes' => $pendingPrizes ?? ($pendingCount ?? 0),
    'activeNav' => 'affiliate',
]) ?>

<?php
$ggrDashboard = $ggrDashboard ?? [];
$currency = esc(systemGet('currency'));
$commissionRate = (float) ($commissionRate ?? 0);
$prizeCommissionRate = (float) ($prizeCommissionRate ?? 0);
?>

<div class="card store-panel-card h-100">
    <div class="card-body store-tab-body store-affiliate-tab-body">
        <div class="store-tab-form store-affiliate-share">
            <h6 class="store-tab-form-title">
                <i class="fa-duotone fa-solid fa-percent"></i> <?= translate('store payment commission'); ?>
            </h6>

            <div class="store-tab-form-fields store-affiliate-fields">
                <label class="form-label" for="store-recharge-commission-rate"><?= translate('store recharge commission rate'); ?></label>
                <div class="input-group mb-2">
                    <input
                        type="text"
                        class="form-control form-bingo"
                        id="store-recharge-commission-rate"
                        value="<?= number_format($commissionRate * 100, 2); ?>"
                        readonly
                    >
                    <span class="input-group-text">%</span>
                </div>

                <label class="form-label" for="store-prize-commission-rate"><?= translate('store prize commission rate'); ?></label>
                <div class="input-group mb-2">
                    <input
                        type="text"
                        class="form-control form-bingo"
                        id="store-prize-commission-rate"
                        value="<?= number_format($prizeCommissionRate * 100, 2); ?>"
                        readonly
                    >
                    <span class="input-group-text">%</span>
                </div>

                <div class="store-affiliate-stats mb-2 mt-3">
                    <div class="store-affiliate-stat">
                        <span class="store-balance-label"><?= translate('recharge commissions earned'); ?></span>
                        <strong><?= $currency ?> <?= number_format((float) ($rechargeCommissionTotal ?? 0), 2); ?></strong>
                    </div>
                    <div class="store-affiliate-stat">
                        <span class="store-balance-label"><?= translate('prize payment commissions earned'); ?></span>
                        <strong><?= $currency ?> <?= number_format((float) ($prizeCommissionTotal ?? 0), 2); ?></strong>
                    </div>
                    <?php if (bingo_ggr_affiliate_active()) : ?>
                    <div class="store-affiliate-stat">
                        <span class="store-balance-label"><?= translate('ggr generated'); ?></span>
                        <strong><?= $currency ?> <?= number_format((float) ($ggrDashboard['total_ggr'] ?? 0), 2); ?></strong>
                    </div>
                    <div class="store-affiliate-stat">
                        <span class="store-balance-label"><?= translate('ggr commissions earned'); ?></span>
                        <strong><?= $currency ?> <?= number_format((float) ($ggrCommissionTotal ?? 0), 2); ?></strong>
                        <small class="text-muted d-block"><?= translate('ggr commission rate'); ?>: <?= number_format(($ggrRate ?? 0) * 100, 2) ?>%</small>
                    </div>
                    <?php endif; ?>
                    <div class="store-affiliate-stat">
                        <span class="store-balance-label"><?= translate('total commissions earned'); ?></span>
                        <strong><?= $currency ?> <?= number_format((float) ($totalCommission ?? 0), 2); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="store-tab-history store-affiliate-history">
            <h6 class="store-tab-history-title">
                <i class="fa-duotone fa-solid fa-users"></i> <?= translate('linked players'); ?>
                <span class="badge bg-secondary ms-1"><?= (int) ($referredCount ?? 0); ?></span>
            </h6>
            <div class="store-table-wrap">
                <?= view('store/affiliate_referrals_list', [
                    'referredPlayers' => $referredPlayers ?? [],
                ]); ?>
            </div>

            <?php if (bingo_ggr_affiliate_active() && ! empty($ggrDashboard['history'])) : ?>
            <h6 class="store-tab-history-title mt-4">
                <i class="fa-duotone fa-solid fa-chart-line"></i> <?= translate('ggr commission history'); ?>
            </h6>
            <div class="store-table-wrap">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th><?= translate('player'); ?></th>
                            <th><?= translate('date'); ?></th>
                            <th>GGR</th>
                            <th><?= translate('commission'); ?></th>
                            <th><?= translate('status'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($ggrDashboard['history'], 0, 20) as $row) : ?>
                        <tr>
                            <td><?= esc($row['player']); ?></td>
                            <td>
                                <?php if (! empty($row['date'])) : ?>
                                    <small><?= esc(date('d/m/Y H:i', strtotime($row['date']))); ?></small>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?= $currency ?> <?= number_format($row['ggr'], 2); ?></td>
                            <td><?= $currency ?> <?= number_format($row['commission'], 2); ?></td>
                            <td>
                                <?php if ((int) $row['status'] === 2) : ?>
                                    <span class="badge bg-success"><?= translate('paid'); ?></span>
                                <?php elseif ((int) $row['status'] === 3) : ?>
                                    <span class="badge bg-danger"><?= translate('rejected'); ?></span>
                                <?php else : ?>
                                    <span class="badge bg-warning text-dark"><?= translate('pending'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= view('store/partials/close') ?>

<?= view('store/partials/scripts_common') ?>
