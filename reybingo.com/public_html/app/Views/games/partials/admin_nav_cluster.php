<?php
$activeNav = $activeNav ?? '';
$showHome = $showHome ?? false;
$showWallet = $showWallet ?? true;
$showStatistics = $showStatistics ?? false;
$showUsers = $showUsers ?? false;

$canStats = function_exists('bingo_can') ? bingo_can('stats.view') : (session()->get('group') == 1);
$canUsers = function_exists('bingo_can') ? bingo_can_any(['users.view', 'users.manage']) : (session()->get('group') == 1);
$canStores = function_exists('bingo_can') ? bingo_can_any(['stores.view', 'stores.manage']) : (session()->get('group') == 1);
$canOperators = function_exists('bingo_can') ? bingo_can_any(['operators.view', 'operators.manage']) : (session()->get('group') == 1);
$canLowBalance = function_exists('bingo_can') ? bingo_can('low_balance.view') : (session()->get('group') == 1);
$canAudit = function_exists('bingo_can') ? bingo_can('audit.view') : (session()->get('group') == 1);
$canKyc = function_exists('bingo_can') ? bingo_can('kyc.review') : (session()->get('group') == 1);
$canLegal = function_exists('bingo_can') ? bingo_can('legal.manage') : (session()->get('group') == 1);
$canPayments = function_exists('bingo_can') ? bingo_can_any(['payments.view', 'payments.manage']) : true;

$lowBalancePending = function_exists('bingo_low_balance_roulette_pending_count')
    ? (int) bingo_low_balance_roulette_pending_count()
    : 0;

$menuItems = [];

if ($canLowBalance) {
    $menuItems[] = [
        'type' => 'link',
        'href' => site_url('users/lowBalancePlayers'),
        'label' => translate('low balance players'),
        'icon' => 'fa-duotone fa-solid fa-coins',
        'class' => 'admin-menu-tile--lowbalance' . ($activeNav === 'low_balance' ? ' is-active' : ''),
        'badge' => $lowBalancePending,
    ];
}
if ($showStatistics && $canStats) {
    $menuItems[] = [
        'type' => 'button',
        'onclick' => 'statisticsView();',
        'label' => translate('statistics'),
        'icon' => 'fa-duotone fa-chart-column',
        'class' => 'admin-menu-tile--stats',
    ];
}
if ($canStores) {
    $menuItems[] = [
        'type' => 'link',
        'href' => site_url('users/stores'),
        'label' => translate('point of sale management'),
        'icon' => 'fa-duotone fa-solid fa-store',
        'class' => 'admin-menu-tile--stores' . ($activeNav === 'stores' ? ' is-active' : ''),
    ];
}
if ($canOperators) {
    $menuItems[] = [
        'type' => 'link',
        'href' => site_url('users/operators'),
        'label' => translate('operator management'),
        'icon' => 'fa-duotone fa-solid fa-user-tie',
        'class' => 'admin-menu-tile--operators' . ($activeNav === 'operators' ? ' is-active' : ''),
    ];
}
if ($showUsers && $canUsers) {
    $menuItems[] = [
        'type' => 'button',
        'onclick' => 'statisticsViewUsers();',
        'label' => translate('users management'),
        'icon' => 'fa-duotone fa-solid fa-users',
        'class' => 'admin-menu-tile--users',
    ];
}
if ($canAudit) {
    $menuItems[] = [
        'type' => 'button',
        'onclick' => 'statisticsViewAudit();',
        'label' => 'Auditoría',
        'icon' => 'fa-duotone fa-solid fa-file-invoice-dollar',
        'class' => 'admin-menu-tile--audit',
    ];
}
if ($canKyc) {
    $menuItems[] = [
        'type' => 'link',
        'href' => site_url('kycAdmin'),
        'label' => 'KYC',
        'icon' => 'fa-duotone fa-solid fa-user-check',
        'class' => 'admin-menu-tile--kyc' . ($activeNav === 'kyc' ? ' is-active' : ''),
    ];
}
if ($canLegal) {
    $menuItems[] = [
        'type' => 'link',
        'href' => site_url('legal/admin'),
        'label' => translate('legal content'),
        'icon' => 'fa-duotone fa-solid fa-scale-balanced',
        'class' => 'admin-menu-tile--legal' . ($activeNav === 'legal' ? ' is-active' : ''),
    ];
}

$menuBadgeTotal = 0;
foreach ($menuItems as $it) {
    $menuBadgeTotal += (int) ($it['badge'] ?? 0);
}
?>
<div class="admin-nav-cluster" id="admin-nav-cluster">
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

        <?php if ($showWallet && $canPayments) : ?>
            <button type="button" class="btn btn-small btn-wallet admin-nav-cluster-item" onclick="paymentsGet();" title="<?= translate('wallet'); ?>">
                <i class="fa-duotone fa-solid fa-wallet"></i>
            </button>
        <?php endif; ?>

        <?php if (! empty($menuItems)) : ?>
            <button
                type="button"
                class="btn btn-small admin-nav-menu-toggle"
                id="admin-nav-menu-toggle"
                aria-expanded="false"
                aria-controls="admin-nav-flyout"
                title="Menú administración"
            >
                <i class="fa-duotone fa-solid fa-bars"></i>
                <?php if ($menuBadgeTotal > 0) : ?>
                    <span class="admin-nav-menu-toggle-badge"><?= $menuBadgeTotal > 99 ? '99+' : $menuBadgeTotal; ?></span>
                <?php endif; ?>
            </button>
        <?php endif; ?>
    </div>

    <?php if (! empty($menuItems)) : ?>
        <div class="admin-nav-flyout" id="admin-nav-flyout" hidden>
            <div class="admin-nav-flyout-head">
                <strong>Administración</strong>
                <button type="button" class="admin-nav-flyout-close" id="admin-nav-flyout-close" aria-label="Cerrar">
                    <i class="fa-duotone fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="admin-nav-flyout-grid">
                <?php foreach ($menuItems as $item) :
                    $tileClass = 'admin-menu-tile ' . ($item['class'] ?? '');
                    $badge = (int) ($item['badge'] ?? 0);
                    $inner = '<i class="' . esc($item['icon']) . '"></i><span>' . esc($item['label']) . '</span>';
                    if ($badge > 0) {
                        $inner .= '<em class="admin-menu-tile-badge">' . ($badge > 99 ? '99+' : $badge) . '</em>';
                    }
                    ?>
                    <?php if (($item['type'] ?? '') === 'link') : ?>
                        <a class="<?= esc($tileClass); ?>" href="<?= esc($item['href']); ?>">
                            <?= $inner; ?>
                        </a>
                    <?php else : ?>
                        <button type="button" class="<?= esc($tileClass); ?>" onclick="<?= esc($item['onclick'] ?? ''); ?>">
                            <?= $inner; ?>
                        </button>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="admin-nav-flyout-backdrop" id="admin-nav-flyout-backdrop" hidden></div>
    <?php endif; ?>
</div>

<script>
(function () {
    var root = document.getElementById('admin-nav-cluster');
    if (!root || root.dataset.bound === '1') return;
    root.dataset.bound = '1';

    var toggle = document.getElementById('admin-nav-menu-toggle');
    var flyout = document.getElementById('admin-nav-flyout');
    var backdrop = document.getElementById('admin-nav-flyout-backdrop');
    var closeBtn = document.getElementById('admin-nav-flyout-close');
    if (!toggle || !flyout) return;

    function setOpen(open) {
        root.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        flyout.hidden = !open;
        if (backdrop) backdrop.hidden = !open;
    }

    toggle.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        setOpen(!root.classList.contains('is-open'));
    });
    if (closeBtn) closeBtn.addEventListener('click', function () { setOpen(false); });
    if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });
    flyout.addEventListener('click', function (e) {
        var tile = e.target.closest('.admin-menu-tile');
        if (tile) setOpen(false);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') setOpen(false);
    });
})();
</script>
