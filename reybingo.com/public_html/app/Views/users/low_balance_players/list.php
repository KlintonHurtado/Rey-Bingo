<?php $currency = esc(systemGet('currency')); ?>

<table class="table table-striped mb-0">
    <thead>
        <tr>
            <th><?= translate('player'); ?></th>
            <th><?= translate('email'); ?></th>
            <th class="text-end"><?= translate('balance'); ?></th>
            <th class="text-center"><?= translate('roulette'); ?></th>
            <th class="text-center"><?= translate('history'); ?></th>
            <th class="text-center"></th>
        </tr>
    </thead>
    <tbody>
        <?php if (! empty($players)) : ?>
            <?php foreach ($players as $player) : ?>
                <?php
                $canSpin = (int) ($player['roulette'] ?? 1) === 0;
                $playerLabel = trim(($player['firstname'] ?? '') . ' ' . ($player['lastname'] ?? ''));
                $latestGrant = $player['latest_grant'] ?? null;
                ?>
                <tr>
                    <td>
                        <strong><?= esc($playerLabel !== '' ? $playerLabel : $player['username']) ?></strong>
                        <br><small class="text-muted"><?= esc($player['code'] ?? '') ?></small>
                    </td>
                    <td><?= esc($player['email'] ?? '-') ?></td>
                    <td class="text-end">
                        <strong class="text-danger"><?= $currency ?> <?= number_format((float) ($player['wallet_total'] ?? 0), 2) ?></strong>
                    </td>
                    <td class="text-center">
                        <?php if ($canSpin) : ?>
                            <span class="badge bg-success"><?= translate('active'); ?></span>
                        <?php else : ?>
                            <span class="badge bg-secondary"><?= translate('inactive'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($latestGrant) : ?>
                            <span class="badge bg-light text-success border border-success">
                                <i class="fa-duotone fa-solid fa-check"></i>
                                <?= esc(date('d/m H:i', strtotime((string) $latestGrant['created_at']))) ?>
                            </span>
                            <br>
                            <small class="text-muted">
                                <?= ($latestGrant['source'] ?? '') === 'auto' ? translate('automatic') : translate('manual'); ?>
                            </small>
                        <?php else : ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if (! $canSpin) : ?>
                            <button
                                type="button"
                                class="btn btn-sm btn-warning text-dark"
                                onclick="grantPlayerRoulette(<?= (int) $player['id'] ?>, '<?= esc($playerLabel !== '' ? $playerLabel : ($player['username'] ?? ''), 'js') ?>')"
                                title="<?= translate('grant roulette'); ?>"
                            >
                                <i class="fa-duotone fa-solid fa-gift"></i>
                            </button>
                        <?php elseif ($latestGrant) : ?>
                            <i class="fa-duotone fa-solid fa-circle-check text-success" title="<?= translate('roulette granted successfully'); ?>"></i>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="6" class="text-center"><?= translate('no low balance players found'); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
