<table class="table table-striped mb-0">
    <thead>
        <tr>
            <th><?= translate('first name'); ?></th>
            <th><?= translate('last name'); ?></th>
            <th><?= translate('email'); ?></th>
            <th><?= translate('username'); ?></th>
            <th class="text-center"><?= translate('points of sale'); ?></th>
            <th class="text-center"><?= translate('status'); ?></th>
            <th class="text-center"><?= translate('options'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (! empty($operators)) : ?>
            <?php foreach ($operators as $operator) : ?>
                <tr>
                    <td><?= esc($operator['firstname']) ?></td>
                    <td><?= esc($operator['lastname']) ?></td>
                    <td><?= esc($operator['email']) ?></td>
                    <td><?= esc($operator['username']) ?><br><small class="text-muted"><?= esc($operator['code'] ?? '') ?></small></td>
                    <td class="text-center"><strong><?= (int) ($operator['stores_count'] ?? 0); ?></strong></td>
                    <td class="text-center">
                        <?php if ((int) $operator['status'] === 1) : ?>
                            <span class="badge bg-success"><?= translate('active'); ?></span>
                        <?php else : ?>
                            <span class="badge bg-secondary"><?= translate('inactive'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="stores-actions" role="group">
                            <button type="button" class="btn btn-sm btn-primary" onclick="operatorEdit(<?= (int) $operator['id'] ?>)" title="<?= translate('edit'); ?>">
                                <i class="fa-duotone fa-solid fa-pen"></i>
                            </button>
                            <?php if ((int) $operator['status'] === 1) : ?>
                                <button type="button" class="btn btn-sm btn-warning" onclick="operatorDeactivate(<?= (int) $operator['id'] ?>, false)" title="<?= translate('deactivate'); ?>">
                                    <i class="fa-duotone fa-solid fa-ban"></i>
                                </button>
                            <?php else : ?>
                                <button type="button" class="btn btn-sm btn-success" onclick="operatorDeactivate(<?= (int) $operator['id'] ?>, true)" title="<?= translate('activate'); ?>">
                                    <i class="fa-duotone fa-solid fa-check"></i>
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-danger" onclick="operatorDelete(<?= (int) $operator['id'] ?>)" title="<?= translate('delete'); ?>">
                                <i class="fa-duotone fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="7" class="text-center"><?= translate('no operators found'); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
