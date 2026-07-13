<?php
$extraClass = trim($extraClass ?? '');
?>
<a
    class="btn btn-small btn-store-admin <?= esc($extraClass); ?>"
    href="<?= site_url('users/stores'); ?>"
    title="<?= translate('point of sale management'); ?>"
>
    <i class="fa-duotone fa-solid fa-store"></i>
</a>
