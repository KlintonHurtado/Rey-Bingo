<div class="table-responsive">
    <table class="table table-striped mb-0">
        <thead>
            <tr>
                <th><?= translate('game'); ?></th>
                <th><?= translate('player'); ?></th>
                <th class="text-center"><?= translate('carton'); ?></th>
                <th class="text-center"><?= translate('modality'); ?></th>
                <th class="text-center"><?= translate('award'); ?></th>
                <?php if (session()->get('group') == 1) : ?>
                <th class="text-center"><?= translate('status'); ?></th>
                <th class="text-center"><?= translate('option'); ?></th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($sings)) : ?>
                <?php foreach ($sings as $sing) : ?>
                    <tr>
                        <td><strong><?= esc($sing['room_name']) ?> </strong><br /> <?= esc($sing['game_description']) ?></td>
                        <td>
                            <strong><?= esc($sing['user_code']) ?></strong>
                            <br>
                            <small class="text-muted"><?= esc($sing['user_name']) ?></small>
                        </td>
                        <td class="text-center">C<?= esc($sing['serial']) ?></td>
                        <td class="text-center"><?= esc($sing['modality_name']) ?></td>
                        <td class="text-center"><?= systemGet('currency'); ?> <?= esc($sing['award_amount']) ?></td>
                        <?php if (session()->get('group') == 1) : ?>
                        <td class="text-center" id="award-<?= $sing['id'] ?>"><?= $sing['status'] ?></td>
                        <td class="text-center">
                            <?php if (($sing['status_raw'] ?? 0) == 1) : ?>
                            <a class="btn btn-primary btn-modal text-white" onclick="payawardSubmit('<?= $sing['id'] ?>', '<?= $sing['user_name'] ?>', '<?= $sing['award_amount'] ?>', 'pay');"><i class="fa-duotone fa-hand-holding-dollar"></i></a>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td <?php if (session()->get('group') == 1) : ?> colspan="7" <?php else : ?> colspan="6" <?php endif; ?> class="text-center"><?= translate('inners found'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
