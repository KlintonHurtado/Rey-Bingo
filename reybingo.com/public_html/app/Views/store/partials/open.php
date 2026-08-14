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
                    <div class="store-sidebar-stats">
                        <div class="store-balance-sidebar">
                            <span class="store-balance-label"><?= translate('available store balance'); ?></span>
                            <strong class="store-balance-amount"><?= systemGet('currency'); ?> <?= number_format((float) ($walletSummary['recharge'] ?? 0), 2) ?></strong>
                        </div>
                        <div class="store-earnings-sidebar">
                            <span class="store-balance-label">Comisiones Acumuladas (Fin de Mes)</span>
                            <strong class="store-earnings-amount"><?= systemGet('currency'); ?> <?= number_format((float) ($walletSummary['earnings_display'] ?? $walletSummary['withdraw'] ?? 0), 2) ?></strong>
                        </div>
                        <div class="store-commission-sidebar">
                            <span class="store-balance-label"><?= translate('store ggr commission rate'); ?></span>
                            <strong class="store-commission-rate"><?= number_format(bingo_store_ggr_commission_rate($storeUser) * 100, 2) ?>%</strong>
                        </div>
                        <div class="store-commission-sidebar">
                            <span class="store-balance-label"><?= translate('store recharge commission rate'); ?></span>
                            <strong class="store-commission-rate"><?= number_format(bingo_store_commission_rate($storeUser) * 100, 2) ?>%</strong>
                        </div>
                        <div class="store-commission-sidebar">
                            <span class="store-balance-label"><?= translate('store prize commission rate'); ?></span>
                            <strong class="store-commission-rate"><?= number_format(bingo_store_prize_commission_rate($storeUser) * 100, 2) ?>%</strong>
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
                        <li class="nav-item">
                            <a class="nav-link store-nav-link <?= ($activeNav ?? '') === 'funding' ? 'active' : '' ?>" href="<?= site_url('store/funding'); ?>">
                                <i class="fa-duotone fa-solid fa-hand-holding-dollar"></i>
                                <span><?= translate('request store balance'); ?></span>
                            </a>
                        </li>
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
