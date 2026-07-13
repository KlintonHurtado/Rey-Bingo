<?php $currency = esc(systemGet('currency')); ?>

<table class="table table-striped table-sm mb-0">
    <thead>
        <tr>
            <th><?= translate('player'); ?></th>
            <th class="text-end"><?= translate('balance'); ?></th>
            <th class="text-center"><?= translate('grant type'); ?></th>
            <th class="text-center"><?= translate('granted at'); ?></th>
            <th class="text-center"><?= translate('roulette'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (! empty($history)) : ?>
            <?php foreach ($history as $row) : ?>
                <tr>
                    <td>
                        <strong><?= esc($row['player_name'] ?? '-') ?></strong>
                        <?php if (! empty($row['player_code'])) : ?>
                            <br><small class="text-muted"><?= esc($row['player_code']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-muted">
                        <?= $currency ?> <?= number_format((float) ($row['balance'] ?? 0), 2) ?>
                    </td>
                    <td class="text-center">
                        <?php if (($row['source'] ?? '') === 'auto') : ?>
                            <span class="badge bg-info text-dark"><?= translate('automatic'); ?></span>
                        <?php else : ?>
                            <span class="badge bg-primary"><?= translate('manual'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <small><?= esc(date('d/m/Y H:i', strtotime((string) ($row['created_at'] ?? 'now')))) ?></small>
                    </td>
                    <td class="text-center">
                        <?php if ((int) ($row['roulette_status'] ?? 1) === 0) : ?>
                            <span class="badge bg-success"><?= translate('roulette granted pending spin'); ?></span>
                        <?php else : ?>
                            <span class="badge bg-secondary"><?= translate('roulette used'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="5" class="text-center text-muted"><?= translate('no roulette grants yet'); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
