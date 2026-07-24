<?php
helper('bingo');

$mode = $mode ?? 'avatar';
$imagePath = $imagePath ?? '';
$wonCartonsExtraClass = trim($wonCartonsExtraClass ?? '');
$showWonCartons = $showWonCartons ?? ((int) session()->get('group') === 0);
$clusterClass = 'player-nav-cluster' . ($mode === 'avatar' ? ' player-nav-cluster--avatar' : ' player-nav-cluster--home');
?>

<div class="<?= esc($clusterClass); ?>">
    <?php if ($mode === 'avatar') : ?>
        <a class="btn btn-small btn-profile player-nav-slot-main" href="<?= site_url('profile'); ?>">
            <img src="<?= $imagePath ?>" alt="img">
        </a>
    <?php else : ?>
        <a class="btn btn-small btn-home player-nav-slot-main" href="<?= site_url('play'); ?>">
            <i class="fa-duotone fa-solid fa-house"></i>
        </a>
    <?php endif; ?>

    <button type="button" class="btn btn-small btn-wallet player-nav-slot-wallet" onclick="paymentsGet();">
        <i class="fa-duotone fa-solid fa-wallet"></i>
    </button>

    <button type="button" class="btn btn-small btn-gamepad player-nav-slot-gamepad" onclick="gamesGet();">
        <i class="fa-duotone fa-solid fa-gamepad"></i>
    </button>

    <?php if ($showWonCartons) : ?>
        <?= view('playings/partials/won_cartons_nav_button', [
            'extraClass' => trim('player-nav-slot-cartons ' . $wonCartonsExtraClass),
        ]); ?>
    <?php endif; ?>

    <?php if ((int) session()->get('group') === 0) : ?>
        <a class="btn btn-small btn-legal-player player-nav-slot-legal<?= ! empty($legalActive) ? ' is-active' : ''; ?>" href="<?= site_url('terminos'); ?>" title="<?= translate('terms and promotions'); ?>">
            <i class="fa-duotone fa-solid fa-scale-balanced"></i>
        </a>
    <?php endif; ?>
</div>
