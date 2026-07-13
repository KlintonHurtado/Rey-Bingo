<?php
$stores = $stores ?? [];
$user = $user ?? [];
$operatorGgrTotal = bingo_operator_commission_rate($user);
$operatorGgrTotalPct = number_format($operatorGgrTotal * 100, 2);
?>
<div class="operator-pane-inner operator-pane-inner-ggr-rates">
    <div class="operator-panel-pane-head mb-3">
        <div class="operator-panel-pane-icon operator-panel-pane-icon-commissions">
            <i class="fa-duotone fa-solid fa-percent"></i>
        </div>
        <div>
            <h5 class="mb-0"><?= translate('operator ggr rates configuration'); ?></h5>
            <small class="text-muted"><?= translate('operator ggr total rate'); ?>: <?= $operatorGgrTotalPct; ?>%</small>
        </div>
    </div>

    <?php if ($operatorGgrTotal <= 0) : ?>
        <p class="text-warning small mb-0"><?= translate('operator ggr total rate not configured'); ?></p>
    <?php elseif (empty($stores)) : ?>
        <p class="text-muted small mb-0"><?= translate('no points of sale assigned yet'); ?></p>
    <?php else : ?>
        <div class="store-table-wrap">
            <table class="table table-sm store-table mb-0 operator-ggr-rates-table">
                <thead>
                    <tr>
                        <th><?= translate('point of sale'); ?></th>
                        <th><?= translate('ggr rate for store'); ?> %</th>
                        <th><?= translate('your ggr margin'); ?> %</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stores as $store) : ?>
                        <?php
                        $storeId = (int) ($store['id'] ?? 0);
                        $isCustom = isset($store['ggr_commission_rate']) && $store['ggr_commission_rate'] !== null && $store['ggr_commission_rate'] !== '';
                        $customValue = $isCustom ? (float) $store['ggr_commission_rate'] * 100 : '';
                        $margin = bingo_operator_ggr_margin_for_store($store, $user);
                        ?>
                        <tr data-store-id="<?= $storeId; ?>">
                            <td><strong><?= esc(bingo_store_display_name($store)); ?></strong></td>
                            <td style="min-width: 120px;">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="<?= $operatorGgrTotalPct; ?>"
                                    class="form-control form-control-sm form-bingo text-center operator-store-ggr-input"
                                    id="operator-store-ggr-<?= $storeId; ?>"
                                    value="<?= $customValue !== '' ? number_format($customValue, 2, '.', '') : ''; ?>"
                                    placeholder="<?= number_format(min((float) (systemGet('rateStoreGgrCommission') ?? 0) * 100, (float) $operatorGgrTotalPct), 2); ?>"
                                >
                            </td>
                            <td class="operator-store-margin-cell">
                                <strong class="operator-store-margin-value"><?= number_format($margin * 100, 2); ?></strong>%
                            </td>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-primary"
                                    onclick="saveOperatorStoreGgrRate(<?= $storeId; ?>);"
                                    title="<?= translate('save'); ?>"
                                >
                                    <i class="fa-duotone fa-solid fa-floppy-disk"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
