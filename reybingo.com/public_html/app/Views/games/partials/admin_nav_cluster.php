<?php
$activeNav = $activeNav ?? '';
$showHome = $showHome ?? false;
$showWallet = $showWallet ?? true;
$showStatistics = $showStatistics ?? false;
$showUsers = $showUsers ?? false;
$storeNavClass = 'btn-store-admin-left' . ($activeNav === 'stores' ? ' is-active' : '');
$operatorsNavClass = 'btn-operators-admin-left' . ($activeNav === 'operators' ? ' is-active' : '');
$lowBalanceNavClass = 'btn-low-balance-admin-left' . ($activeNav === 'low_balance' ? ' is-active' : '');
$ggrNavClass = 'btn-ggr-admin-left' . ($activeNav === 'ggr' ? ' is-active' : '');
$showGgr = $showGgr ?? ($showAffiliates ?? true);
?>
<div class="admin-nav-cluster">
    <div class="admin-nav-cluster-top">
        <?php if ($showHome) : ?>
            <a class="btn btn-small btn-home admin-nav-cluster-item" href="<?= site_url('games'); ?>" title="<?= translate('home'); ?>">
                <i class="fa-duotone fa-solid fa-house"></i>
            </a>
        <?php else : ?>
            <a class="btn btn-small btn-profile admin-nav-cluster-item" href="<?= site_url('profile'); ?>" title="<?= translate('profile'); ?>">
                <img src="<?= $imagePath ?>" alt="img">
            </a>
        <?php endif; ?>

        <?php if ($showWallet) : ?>
            <button type="button" class="btn btn-small btn-wallet admin-nav-cluster-item" onclick="paymentsGet();" title="<?= translate('wallet'); ?>">
                <i class="fa-duotone fa-solid fa-wallet"></i>
            </button>
        <?php endif; ?>
    </div>

    <div class="admin-nav-cluster-menu">
        <?= view('users/low_balance_players/nav_button', ['extraClass' => $lowBalanceNavClass]) ?>

        <?php if ($showStatistics) : ?>
            <button type="button" class="btn btn-small btn-statistics admin-nav-cluster-item" onclick="statisticsView();" title="<?= translate('statistics'); ?>">
                <i class="fa-duotone fa-chart-column"></i>
            </button>
        <?php endif; ?>

        <?= view('stores/nav_button', ['extraClass' => $storeNavClass]) ?>
        <?= view('operators/nav_button', ['extraClass' => $operatorsNavClass]) ?>

        <?php if ($showUsers) : ?>
            <button type="button" class="btn btn-small btn-users admin-nav-cluster-item" onclick="statisticsViewUsers();" title="<?= translate('users management'); ?>">
                <i class="fa-duotone fa-solid fa-users"></i>
            </button>
        <?php endif; ?>
    </div>
</div>
