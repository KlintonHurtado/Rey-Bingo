<?php
$extraClass = $extraClass ?? '';
$pending = bingo_low_balance_roulette_pending_count();
?>
<a class="btn btn-small btn-low-balance-admin <?= esc($extraClass) ?>" href="<?= site_url('users/lowBalancePlayers'); ?>" title="<?= translate('low balance players'); ?>">
    <i class="fa-duotone fa-solid fa-coins"></i>
    <span class="low-balance-pending-badge<?= $pending > 0 ? '' : ' d-none' ?>" id="low-balance-pending-badge"><?= (int) $pending ?></span>
</a>
