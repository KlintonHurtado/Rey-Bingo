<table class="table table-striped table-sm mb-0">
    <thead>
        <tr>
            <th><?= translate('business name'); ?></th>
            <th><?= translate('email'); ?></th>
            <th><?= translate('registered'); ?></th>
            <th class="text-center"><?= translate('commission'); ?></th>
            <th class="text-center"><?= translate('status'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($affiliatedStores)) : ?>
        <tr>
            <td colspan="5" class="text-center text-muted py-4">
                <?= strtoupper(translate('no affiliated stores yet share your link')); ?>
            </td>
        </tr>
        <?php else : ?>
            <?php foreach ($affiliatedStores as $affiliatedStore) : ?>
            <tr>
                <td><?= esc(bingo_store_display_name($affiliatedStore)); ?></td>
                <td><?= esc($affiliatedStore['email'] ?? ''); ?></td>
                <td><?= ! empty($affiliatedStore['created_at']) ? esc(date('d/m/Y', strtotime($affiliatedStore['created_at']))) : '—'; ?></td>
                <td class="text-center">
                    <?php if (! empty($affiliatedStore['affiliate_commission'])) : ?>
                        <strong><?= systemGet('currency'); ?> <?= esc($affiliatedStore['affiliate_commission']); ?></strong>
                    <?php else : ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if (! empty($affiliatedStore['affiliate_commission'])) : ?>
                        <span class="badge bg-success"><?= translate('paid'); ?></span>
                    <?php else : ?>
                        <span class="badge bg-warning text-dark"><?= translate('pending'); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
