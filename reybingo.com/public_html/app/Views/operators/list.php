<table class="table table-striped mb-0">
    <thead>
        <tr>
            <th><?= translate('first name'); ?></th>
            <th><?= translate('last name'); ?></th>
            <th><?= translate('email'); ?></th>
            <th><?= translate('username'); ?></th>
            <th class="text-center"><?= translate('points of sale'); ?></th>
            <th class="text-center">Saldo / Ganancias</th>
            <th class="text-center"><?= translate('status'); ?></th>
            <th class="text-center"><?= translate('options'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (! empty($operators)) : ?>
            <?php foreach ($operators as $operator) : ?>
                <tr>
                    <td>
                        <?= esc($operator['firstname']) ?>
                        <?php if (! empty($operator['document'])) : ?>
                            <br><small class="text-muted"><?= esc($operator['document']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= esc($operator['lastname']) ?></td>
                    <td><?= esc($operator['email']) ?></td>
                    <td><?= esc($operator['username']) ?><br><small class="text-muted"><?= esc($operator['code'] ?? '') ?></small></td>
                    <td class="text-center"><strong><?= (int) ($operator['stores_count'] ?? 0); ?></strong></td>
                    <td class="text-center">
                        <small class="text-muted d-block">Saldo: <strong class="text-success"><?= systemGet('currency'); ?> <?= number_format((float) ($operator['wallet_balance'] ?? 0), 2) ?></strong></small>
                        <small class="text-muted d-block">Ganancias: <strong class="text-primary"><?= systemGet('currency'); ?> <?= number_format((float) ($operator['total_earnings'] ?? 0), 2) ?></strong></small>
                    </td>
                    <td class="text-center">
                        <?php if ((int) $operator['status'] === 1) : ?>
                            <span class="badge bg-success"><?= translate('active'); ?></span>
                        <?php else : ?>
                            <span class="badge bg-secondary"><?= translate('inactive'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="stores-actions d-flex justify-content-center gap-1" role="group">
                            <button type="button" class="btn btn-sm btn-success text-white d-flex align-items-center gap-1" onclick="openCommissionLiquidationModal(<?= (int) $operator['id'] ?>, function(){ operatorRefreshList(); })" title="Pagar / Liquidar Comisiones">
                                <i class="fa-duotone fa-solid fa-money-bill-wave"></i> Pagar
                            </button>
                            <a class="btn btn-sm btn-outline-success d-flex align-items-center gap-1" href="<?= site_url('users/exportUserCommissions/' . (int) $operator['id']); ?>" title="Descargar comisiones de este Operador">
                                <i class="fa-duotone fa-solid fa-file-excel"></i>
                            </a>
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
                <td colspan="8" class="text-center"><?= translate('no operators found'); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
