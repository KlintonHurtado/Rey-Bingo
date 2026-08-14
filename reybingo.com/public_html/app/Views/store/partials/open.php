<?php
helper(['bingo', 'affiliate_ggr']);
$isOperatorActing = $isOperatorActing ?? (bingo_is_operator() && bingo_get_acting_store_id() > 0);
$storeUser = $user ?? null;
if (!$storeUser || empty($storeUser)) {
    $storeId = bingo_get_effective_store_id();
    if ($storeId > 0) {
        $storeUser = (new \App\Models\UsersModel())->find($storeId);
    }
}
$storeUser = $storeUser ?? [];
?>
<a class="btn btn-small btn-profile" href="<?= site_url('profile'); ?>"><img src="<?= $imagePath ?>" alt="img"></a>

<a class="btn btn-small btn-lock" href="<?= site_url('password'); ?>"><i class="fa-duotone fa-solid fa-lock"></i></a>

<a class="btn btn-small btn-logout" href="<?= site_url('logout'); ?>"><i class="fa-duotone fa-solid fa-arrow-right-from-arc"></i></a>

<div class="store-panel-fit">
    <div class="store-panel-shell">
        <aside class="store-panel-sidebar">
            <div class="card store-panel-card store-panel-sidebar-card">
                <div class="card-body">
                    <?php $storeDisplayName = trim(bingo_store_display_name($storeUser)); ?>
                    <h5 class="store-sidebar-title mb-1">
                        <i class="fa-duotone fa-solid fa-store"></i>
                        <?= $storeDisplayName !== '' ? esc($storeDisplayName) : translate('store panel'); ?>
                    </h5>
                    <?php if ($storeDisplayName !== '') : ?>
                        <p class="store-sidebar-subtitle small text-muted mb-3"><?= translate('store panel'); ?></p>
                    <?php else : ?>
                        <div class="mb-3"></div>
                    <?php endif; ?>
<?php
$storeIdVal = (int) ($storeUser['id'] ?? 0);
$currency = systemGet('currency') ?? '$';

$rechargeCommissionEarned = $storeIdVal > 0 ? bingo_sum_store_recharge_commissions($storeIdVal) : 0.0;
$prizeCommissionEarned = $storeIdVal > 0 ? bingo_sum_store_prize_commissions($storeIdVal) : 0.0;
$ggrEarned = 0.0;
if ($storeIdVal > 0 && function_exists('bingo_sum_affiliate_ggr_commissions')) {
    $ggrData = bingo_sum_affiliate_ggr_commissions($storeIdVal, 'store');
    $ggrEarned = (float) ($ggrData['total_commission'] ?? 0) + (float) ($ggrData['pending_commission'] ?? 0);
}
$totalCommissionsEarned = round($rechargeCommissionEarned + $prizeCommissionEarned + $ggrEarned, 2);
?>
                    <div class="store-sidebar-stats">
                        <div class="store-balance-sidebar">
                            <span class="store-balance-label"><?= translate('available store balance'); ?></span>
                            <strong class="store-balance-amount"><?= $currency; ?> <?= number_format((float) ($walletSummary['recharge'] ?? 0), 2) ?></strong>
                        </div>
                        <div class="store-earnings-sidebar">
                            <span class="store-balance-label">Total Comisiones (Fin de Mes)</span>
                            <strong class="store-earnings-amount"><?= $currency; ?> <?= number_format($totalCommissionsEarned, 2) ?></strong>
                            <small class="text-muted" style="font-size: 0.73rem;">Por liquidar al cierre del mes</small>
                        </div>
                        <div class="store-commission-sidebar">
                            <span class="store-balance-label">Comisión GGR (<?= number_format(bingo_store_ggr_commission_rate($storeUser) * 100, 2) ?>%)</span>
                            <strong class="store-commission-rate"><?= $currency; ?> <?= number_format($ggrEarned, 2) ?></strong>
                        </div>
                        <div class="store-commission-sidebar">
                            <span class="store-balance-label">Comisión Recargas (<?= number_format(bingo_store_commission_rate($storeUser) * 100, 2) ?>%)</span>
                            <strong class="store-commission-rate"><?= $currency; ?> <?= number_format($rechargeCommissionEarned, 2) ?></strong>
                        </div>
                        <div class="store-commission-sidebar">
                            <span class="store-balance-label">Comisión Retiros (<?= number_format(bingo_store_prize_commission_rate($storeUser) * 100, 2) ?>%)</span>
                            <strong class="store-commission-rate"><?= $currency; ?> <?= number_format($prizeCommissionEarned, 2) ?></strong>
                        </div>
                    </div>

                    <?php if ($isOperatorActing) : ?>
                        <a
                            href="<?= site_url('operator/leaveStore'); ?>"
                            class="store-panel-back-btn"
                            title="<?= translate('back to operator panel'); ?>"
                        >
                            <i class="fa-duotone fa-solid fa-arrow-left"></i>
                            <span><?= translate('go back'); ?></span>
                        </a>
                    <?php endif; ?>

                    <div class="store-panel-sidebar-divider"></div>

                    <ul class="nav store-panel-tabs store-panel-nav">
                        <!-- Botón Solicitar Saldo comentado temporalmente por solicitud -->
                        <!-- <li class="nav-item">
                            <a class="nav-link store-nav-link <?= ($activeNav ?? '') === 'funding' ? 'active' : '' ?>" href="<?= site_url('store/funding'); ?>">
                                <i class="fa-duotone fa-solid fa-hand-holding-dollar"></i>
                                <span><?= translate('request store balance'); ?></span>
                            </a>
                        </li> -->
                        <li class="nav-item">
                            <a class="nav-link store-nav-link <?= ($activeNav ?? '') === 'recharge' ? 'active' : '' ?>" href="<?= site_url('store/recharge'); ?>">
                                <i class="fa-duotone fa-solid fa-mobile-screen"></i>
                                <span><?= translate('recharge player by document'); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link store-nav-link <?= ($activeNav ?? '') === 'affiliate' ? 'active' : '' ?>" href="<?= site_url('store/affiliate'); ?>">
                                <i class="fa-duotone fa-solid fa-percent"></i>
                                <span><?= translate('store commissions'); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link store-nav-link <?= ($activeNav ?? '') === 'prizes' ? 'active' : '' ?>" href="<?= site_url('store/prizes'); ?>">
                                <i class="fa-duotone fa-solid fa-money-bill-transfer"></i>
                                <span>Pagar Retiros</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link store-nav-link <?= ($activeNav ?? '') === 'movements' ? 'active' : '' ?>" href="<?= site_url('store/movements'); ?>">
                                <i class="fa-duotone fa-solid fa-clock-rotate-left"></i>
                                <span>Movimientos</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>

        <main class="store-panel-page-content">
