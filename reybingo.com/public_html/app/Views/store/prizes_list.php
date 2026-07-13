<table class="table table-striped mb-0">
    <thead>
        <tr>
            <th><?= translate('game'); ?></th>
            <th class="text-center"><?= translate('carton'); ?></th>
            <th class="text-center"><?= translate('modality'); ?></th>
            <th class="text-center"><?= translate('award'); ?></th>
            <th class="text-center"><?= translate('status'); ?></th>
            <th class="text-center"><?= translate('option'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (! empty($sings)) : ?>
            <?php foreach ($sings as $sing) : ?>
                <tr>
                    <td>
                        <strong><?= esc($sing['room_name']) ?></strong><br>
                        <small class="text-muted"><?= esc($sing['game_description']) ?></small>
                    </td>
                    <td class="text-center">C<?= esc($sing['serial']) ?></td>
                    <td class="text-center"><?= esc($sing['modality_name']) ?></td>
                    <td class="text-center">
                        <strong><?= systemGet('currency'); ?> <?= esc($sing['award_amount']) ?></strong>
                    </td>
                    <td class="text-center"><?= $sing['status'] ?></td>
                    <td class="text-center">
                        <?php if (($sing['status_raw'] ?? 0) === 1) : ?>
                            <button type="button" class="btn btn-primary btn-bingo btn-sm text-white" onclick="storePayAwardSubmit('<?= (int) $sing['id']; ?>', '<?= esc($sing['user_name'], 'attr'); ?>', '<?= esc($sing['award_amount'], 'attr'); ?>');">
                                <i class="fa-duotone fa-hand-holding-dollar"></i>
                            </button>
                        <?php else : ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="6" class="text-center py-3">
                    <?php if (! empty($requiresPlayer)) : ?>
                        <?= translate('search and select a player first'); ?>
                    <?php elseif (! empty($playerNotFound)) : ?>
                        <?= translate('player not found'); ?>
                    <?php else : ?>
                        <?= translate('no winners found'); ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php if (! empty($showPagination) && $showPagination) : ?>
    <div class="store-prizes-pagination px-2 py-3 border-top">
        <div class="text-center text-muted small mb-2">
            <?= translate('showing'); ?>
            <?= ($currentPage - 1) * $per_page + 1; ?> -
            <?= min($currentPage * $per_page, $totalRecords); ?>
            <?= translate('of'); ?> <?= number_format($totalRecords); ?> <?= translate('winners'); ?>
        </div>
        <nav class="d-flex justify-content-center align-items-center">
            <ul class="pagination mb-0 pagination-sm">
                <?php if ($currentPage > 1) : ?>
                    <li class="page-item">
                        <a class="page-link" href="javascript:void(0)" onclick="storePrizesGetPage(<?= $currentPage - 1; ?>)">
                            <i class="fa-duotone fa-solid fa-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++) : ?>
                    <li class="page-item <?= $i === $currentPage ? 'active' : ''; ?>">
                        <a class="page-link" href="javascript:void(0)" onclick="storePrizesGetPage(<?= $i; ?>)"><?= $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages) : ?>
                    <li class="page-item">
                        <a class="page-link" href="javascript:void(0)" onclick="storePrizesGetPage(<?= $currentPage + 1; ?>)">
                            <i class="fa-duotone fa-solid fa-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
<?php endif; ?>
