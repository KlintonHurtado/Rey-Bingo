<?php
$currency = $currency ?? systemGet('currency') ?? '$';
$stats = $stats ?? [];
$items = $items ?? [];
?>
<div class="table-responsive" id="operator-commissions-table-wrapper"
     data-ggr-base="<?= esc(number_format((float)($stats['ggr']['total_base'] ?? 0), 2, '.', '')); ?>"
     data-ggr-stores="<?= esc(bingo_format_exact_amount((float)($stats['ggr']['stores_earned'] ?? 0))); ?>"
     data-ggr-operator="<?= esc(bingo_format_exact_amount((float)($stats['ggr']['operator_earned'] ?? 0))); ?>"
     data-rec-base="<?= esc(number_format((float)($stats['recharge']['total_base'] ?? 0), 2, '.', '')); ?>"
     data-rec-stores="<?= esc(bingo_format_exact_amount((float)($stats['recharge']['stores_earned'] ?? 0))); ?>"
     data-rec-operator="<?= esc(bingo_format_exact_amount((float)($stats['recharge']['operator_earned'] ?? 0))); ?>"
     data-with-base="<?= esc(number_format((float)($stats['withdraw']['total_base'] ?? 0), 2, '.', '')); ?>"
     data-with-stores="<?= esc(bingo_format_exact_amount((float)($stats['withdraw']['stores_earned'] ?? 0))); ?>"
     data-with-operator="<?= esc(bingo_format_exact_amount((float)($stats['withdraw']['operator_earned'] ?? 0))); ?>"
     data-total-profit="<?= esc(bingo_format_exact_amount((float)($stats['total_operator_profit'] ?? 0))); ?>">

    <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
        <thead class="table-light">
            <tr>
                <th style="min-width: 125px;">Fecha y Hora</th>
                <th>Punto de Venta / Origen</th>
                <th>Tipo de Tasa</th>
                <th class="text-end">Total apostado</th>
                <th class="text-end">Total premios</th>
                <th class="text-end">Monto Base / GGR</th>
                <th class="text-center">Tasa PV (%)</th>
                <th class="text-end">Comisión PV</th>
                <th class="text-center">Tasa Operador (%)</th>
                <th class="text-center text-primary fw-bold">Margen / Dif. (%)</th>
                <th class="text-end text-success fw-bold" style="min-width: 120px;">Ganancia Operador</th>
                <th class="text-center">Estado</th>
                <th style="min-width: 160px;">Detalle</th>
            </tr>
        </thead>
        <tbody>
            <?php if (! empty($items)) : ?>
                <?php foreach ($items as $it) : ?>
                    <?php
                    $baseAmt = (float) ($it['base_amount'] ?? 0);
                    $stCommission = (float) ($it['store_commission'] ?? 0);
                    $opProfit = (float) ($it['operator_profit'] ?? 0);
                    $stRate = ((float) ($it['store_rate'] ?? 0)) * 100;
                    $opRate = ((float) ($it['operator_rate'] ?? 0)) * 100;
                    $opSpread = ((float) ($it['operator_spread'] ?? 0)) * 100;
                    $badgeClass = $it['badge_class'] ?? 'bg-secondary text-white';
                    $icon = $it['icon'] ?? 'fa-duotone fa-solid fa-percent';
                    $isGgr = (string) ($it['rate_type'] ?? '') === 'ggr';
                    $totalStake = $isGgr ? (float) ($it['total_stake'] ?? 0) : null;
                    $totalPayout = $isGgr ? (float) ($it['total_payout'] ?? 0) : null;
                    ?>
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark">
                                <?= ! empty($it['datetime']) ? date('d/m/Y', strtotime($it['datetime'])) : '-'; ?>
                            </div>
                            <small class="text-muted">
                                <?= ! empty($it['datetime']) ? date('H:i:s', strtotime($it['datetime'])) : ''; ?>
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-light text-primary border border-primary-subtle fw-semibold">
                                <i class="fa-duotone fa-solid fa-store me-1"></i> <?= esc($it['store_name'] ?? 'Punto de Venta'); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= esc($badgeClass); ?> py-1 px-2">
                                <i class="<?= esc($icon); ?> me-1"></i> <?= esc($it['rate_type_label'] ?? $it['rate_type']); ?>
                            </span>
                        </td>
                        <td class="text-end text-muted">
                            <?= $totalStake !== null ? esc($currency) . ' ' . number_format($totalStake, 2) : '-'; ?>
                        </td>
                        <td class="text-end text-muted">
                            <?= $totalPayout !== null ? esc($currency) . ' ' . number_format($totalPayout, 2) : '-'; ?>
                        </td>
                        <td class="text-end fw-semibold <?= $baseAmt < 0 ? 'text-danger' : 'text-dark'; ?>">
                            <?= esc($currency); ?> <?= number_format($baseAmt, 2); ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border"><?= number_format($stRate, 2); ?>%</span>
                        </td>
                        <td class="text-end <?= $stCommission < 0 ? 'text-danger' : 'text-muted'; ?>">
                            <?= esc($currency); ?> <?= bingo_format_exact_amount($stCommission); ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border"><?= number_format($opRate, 2); ?>%</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary-subtle text-primary border border-primary fw-bold" style="font-size: 0.85rem;">
                                +<?= number_format($opSpread, 2); ?>%
                            </span>
                        </td>
                        <td class="text-end fw-bold <?= $opProfit < 0 ? 'text-danger' : 'text-success'; ?>" style="font-size: 0.95rem;">
                            <?= ($opProfit < 0 ? '' : '+') . esc($currency); ?> <?= bingo_format_exact_amount($opProfit); ?>
                        </td>
                        <td class="text-center">
                            <?php
                            $st = (int) ($it['status'] ?? 0);
                            $stClass = match($st) {
                                1, 2 => 'bg-success',
                                default => 'bg-warning text-dark'
                            };
                            ?>
                            <span class="badge <?= $stClass; ?>">
                                <?= esc($it['status_label'] ?? ($st === 2 || $st === 1 ? 'Aprobada' : 'Pendiente')); ?>
                            </span>
                        </td>
                        <td>
                            <small class="text-muted d-block" style="max-width: 220px; white-space: normal;">
                                <?= esc($it['detail'] ?? '-'); ?>
                            </small>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="13" class="text-center py-5 text-muted">
                        <i class="fa-duotone fa-solid fa-chart-line-down fs-1 d-block mb-2 text-secondary"></i>
                        No se encontraron registros de comisiones para este criterio de búsqueda.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
