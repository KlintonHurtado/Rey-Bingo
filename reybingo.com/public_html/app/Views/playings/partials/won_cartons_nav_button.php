<?php
helper('bingo');

$extraClass = $extraClass ?? '';
$pendingCount = bingo_count_pending_roulette_cartons((int) session()->get('id'));
?>

<a
    class="btn btn-small btn-won-cartons <?= esc($extraClass); ?>"
    href="<?= site_url('playings/wonCartons'); ?>"
    title="Mis cartones ganados"
>
    <i class="fa-duotone fa-solid fa-ticket"></i>
    <span class="won-cartons-pending-badge<?= $pendingCount > 0 ? '' : ' d-none'; ?>" id="won-cartons-pending-badge"><?= (int) $pendingCount; ?></span>
</a>
