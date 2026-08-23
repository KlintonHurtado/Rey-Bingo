<table class="table table-striped mb-0">
    <thead>
        <tr>
            <th><?= translate('business name'); ?></th>
            <th><?= translate('first name'); ?></th>
            <th><?= translate('last name'); ?></th>
            <th><?= translate('email'); ?></th>
            <th><?= translate('username'); ?></th>
            <th><?= translate('operator'); ?></th>
            <th class="text-center"><?= translate('store commission rate'); ?></th>
            <th class="text-center"><?= translate('status'); ?></th>
            <th class="text-center"><?= translate('options'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (! empty($stores)) : ?>
            <?php foreach ($stores as $store) : ?>
                <tr>
                    <td><strong><?= esc($store['business_name'] ?? '-') ?></strong><br><small class="text-muted"><?= esc($store['code']) ?></small></td>
                    <td>
                        <?= esc($store['firstname']) ?>
                        <?php if (! empty($store['document'])) : ?>
                            <br><small class="text-muted"><?= esc($store['document']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= esc($store['lastname']) ?></td>
                    <td><?= esc($store['email']) ?></td>
                    <td><?= esc($store['username']) ?></td>
                    <td>
                        <?php if (! empty($store['operator_name'])) : ?>
                            <?= esc($store['operator_name']); ?>
                        <?php else : ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php
                        $rate = bingo_store_commission_rate($store);
                        $isCustom = isset($store['store_commission_rate']) && $store['store_commission_rate'] !== null && $store['store_commission_rate'] !== '';
                        ?>
                        <strong><?= number_format($rate * 100, 2) ?>%</strong>
                        <?php if (! $isCustom) : ?>
                            <br><small class="text-muted"><?= translate('global'); ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ((int) $store['status'] === 1) : ?>
                            <span class="badge bg-success"><?= translate('active'); ?></span>
                        <?php else : ?>
                            <span class="badge bg-warning text-dark"><?= translate('inactive'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="stores-actions d-flex justify-content-center gap-1" role="group">
                            <button type="button" class="btn btn-sm btn-success text-white d-flex align-items-center gap-1" onclick="openCommissionLiquidationModal(<?= (int) $store['id'] ?>, function(){ storeRefreshList(); })" title="Pagar / Liquidar Comisiones">
                                <i class="fa-duotone fa-solid fa-money-bill-wave"></i> Pagar
                            </button>
                            <a class="btn btn-sm btn-outline-success d-flex align-items-center gap-1" href="<?= site_url('users/exportUserCommissions/' . (int) $store['id']); ?>" title="Descargar comisiones de este Punto de Venta">
                                <i class="fa-duotone fa-solid fa-file-excel"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-primary" onclick="storeEdit(<?= (int) $store['id'] ?>)" title="<?= translate('edit'); ?>">
                                <i class="fa-duotone fa-solid fa-pen"></i>
                            </button>
                            <?php if ((int) $store['status'] === 1) : ?>
                                <button type="button" class="btn btn-sm btn-warning" onclick="storeDeactivate(<?= (int) $store['id'] ?>, false)" title="<?= translate('deactivate'); ?>">
                                    <i class="fa-duotone fa-solid fa-ban"></i>
                                </button>
                            <?php else : ?>
                                <button type="button" class="btn btn-sm btn-success" onclick="storeDeactivate(<?= (int) $store['id'] ?>, true)" title="<?= translate('activate'); ?>">
                                    <i class="fa-duotone fa-solid fa-check"></i>
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-danger" onclick="storeDelete(<?= (int) $store['id'] ?>)" title="<?= translate('delete'); ?>">
                                <i class="fa-duotone fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="9" class="text-center"><?= translate('no stores found'); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
