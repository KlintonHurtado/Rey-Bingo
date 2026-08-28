<?php
$currency = $currency ?? systemGet('currency') ?? '$';
$stats = $stats ?? [];
$items = $items ?? [];
?>
<div class="table-responsive" id="store-commissions-table-wrapper"
     data-ggr-base="<?= esc(number_format((float)($stats['ggr']['total_base'] ?? 0), 2, '.', '')); ?>"
     data-ggr-earned="<?= esc(number_format((float)($stats['ggr']['total_earned'] ?? 0), 2, '.', '')); ?>"
     data-rec-base="<?= esc(number_format((float)($stats['recharge']['total_base'] ?? 0), 2, '.', '')); ?>"
     data-rec-earned="<?= esc(number_format((float)($stats['recharge']['total_earned'] ?? 0), 2, '.', '')); ?>"
     data-with-base="<?= esc(number_format((float)($stats['withdraw']['total_base'] ?? 0), 2, '.', '')); ?>"
     data-with-earned="<?= esc(number_format((float)($stats['withdraw']['total_earned'] ?? 0), 2, '.', '')); ?>"
     data-total-commissions="<?= esc(number_format((float)($stats['total_commissions_earned'] ?? 0), 2, '.', '')); ?>">

    <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
        <thead class="table-light">
            <tr>
                <th style="min-width: 125px;">Fecha y Hora</th>
                <th>Tipo de Tasa</th>
                <th class="text-end">Total apostado</th>
                <th class="text-end">Total premios</th>
                <th class="text-end">Monto Base / GGR</th>
                <th class="text-center">Tasa (%)</th>
                <th class="text-end text-success fw-bold" style="min-width: 120px;">Comisión Ganada</th>
                <th>Jugador / Beneficiario</th>
                <th>Cédula / Doc.</th>
                <th>Código / Ref.</th>
                <th class="text-center">Estado</th>
                <th style="min-width: 160px;">Detalle</th>
            </tr>
        </thead>
        <tbody>
            <?php if (! empty($items)) : ?>
                <?php foreach ($items as $it) : ?>
                    <?php
                    $baseAmt = (float) ($it['base_amount'] ?? 0);
                    $commission = (float) ($it['commission_amount'] ?? 0);
                    $ratePct = ((float) ($it['store_rate'] ?? 0)) * 100;
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
                        <td class="text-end fw-semibold text-dark">
                            <?= esc($currency); ?> <?= number_format($baseAmt, 2); ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border fw-semibold"><?= number_format($ratePct, 2); ?>%</span>
                        </td>
                        <td class="text-end text-success fw-bold" style="font-size: 0.95rem;">
                            +<?= esc($currency); ?> <?= number_format($commission, 2); ?>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= esc($it['player_name'] ?? 'Jugador'); ?></div>
                            <?php if (! empty($it['player_username'])) : ?>
                                <small class="text-muted">@<?= esc($it['player_username']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (! empty($it['player_doc'])) : ?>
                                <span class="badge bg-light text-dark border"><?= esc($it['player_doc']); ?></span>
                            <?php else : ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (! empty($it['ref_code'])) : ?>
                                <span class="font-monospace fw-bold text-primary"><?= esc($it['ref_code']); ?></span>
                            <?php else : ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
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
                    <td colspan="12" class="text-center py-5 text-muted">
                        <i class="fa-duotone fa-solid fa-chart-line-down fs-1 d-block mb-2 text-secondary"></i>
                        No se encontraron registros de comisiones para este criterio de búsqueda.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
