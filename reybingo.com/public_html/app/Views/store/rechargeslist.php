<table class="table table-striped mb-0">
    <thead>
        <tr>
            <th><?= translate('date'); ?></th>
            <th><?= translate('player'); ?></th>
            <th><?= translate('document'); ?></th>
            <th class="text-center"><?= translate('amount'); ?></th>
            <th class="text-center"><?= translate('commission'); ?></th>
            <th><?= translate('reference'); ?></th>
            <th class="text-center"><?= translate('status'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (! empty($recharges)) : ?>
            <?php foreach ($recharges as $recharge) : ?>
                <tr>
                    <td><?= esc(date('d/m/Y H:i', strtotime($recharge['created_at']))) ?></td>
                    <td>
                        <strong><?= esc($recharge['player_name']) ?></strong><br>
                        <small class="text-muted"><?= esc($recharge['player_code']) ?></small>
                    </td>
                    <td><?= esc($recharge['player_document']) ?></td>
                    <td class="text-center"><?= systemGet('currency'); ?> <?= number_format((float) $recharge['amount'], 2) ?></td>
                    <td class="text-center">
                        <?php if (! empty($recharge['commission_amount']) && (float) $recharge['commission_amount'] > 0) : ?>
                            <span class="text-success"><?= systemGet('currency'); ?> <?= number_format((float) $recharge['commission_amount'], 2) ?></span>
                        <?php else : ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= esc($recharge['reference']) ?></td>
                    <td class="text-center"><?= $recharge['status_label'] ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="7" class="text-center py-3"><?= translate('no recharges yet'); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
