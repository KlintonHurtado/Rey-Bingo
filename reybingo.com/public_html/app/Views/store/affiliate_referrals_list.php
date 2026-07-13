<table class="table table-striped mb-0">
    <thead>
        <tr>
            <th><?= translate('player'); ?></th>
            <th class="text-center"><?= translate('document'); ?></th>
            <th class="text-center"><?= translate('registered at'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (! empty($referredPlayers)) : ?>
            <?php foreach ($referredPlayers as $player) : ?>
                <tr>
                    <td>
                        <strong><?= esc(trim(($player['firstname'] ?? '') . ' ' . ($player['lastname'] ?? ''))); ?></strong><br>
                        <small class="text-muted"><?= esc($player['code'] ?? ''); ?></small>
                    </td>
                    <td class="text-center"><?= esc($player['document'] ?? ''); ?></td>
                    <td class="text-center">
                        <small><?= esc(date('d/m/Y', strtotime($player['created_at'] ?? 'now'))); ?></small>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="3" class="text-center py-4 text-muted">
                    <?= translate('no linked players yet'); ?>
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
