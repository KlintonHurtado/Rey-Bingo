<?php
$currency = $currency ?? systemGet('currency') ?? '$';
?>
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
        <thead class="table-light">
            <tr>
                <th style="min-width: 125px;">Fecha y Hora</th>
                <th>Tipo de Movimiento</th>
                <th class="text-end" style="min-width: 95px;">Monto</th>
                <th>Punto de Venta</th>
                <th>Beneficiario / Jugador</th>
                <th>Cédula / Doc.</th>
                <th>Código / Ref.</th>
                <th class="text-center">Estado</th>
                <th style="min-width: 180px;">Detalle</th>
            </tr>
        </thead>
        <tbody>
            <?php if (! empty($movements)) : ?>
                <?php foreach ($movements as $m) : ?>
                    <?php
                    $isPositive = ($m['direction'] ?? '') === '+';
                    $amountColor = $isPositive ? 'text-success fw-bold' : 'text-danger fw-bold';
                    $sign = $isPositive ? '+' : '-';
                    $badgeClass = $m['badge_class'] ?? 'bg-secondary text-white';
                    $icon = $m['icon'] ?? 'fa-duotone fa-solid fa-circle-dot';
                    ?>
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark">
                                <?= ! empty($m['datetime']) ? date('d/m/Y', strtotime($m['datetime'])) : '-'; ?>
                            </div>
                            <small class="text-muted">
                                <?= ! empty($m['datetime']) ? date('H:i:s', strtotime($m['datetime'])) : ''; ?>
                            </small>
                        </td>
                        <td>
                            <span class="badge <?= esc($badgeClass); ?> py-1 px-2">
                                <i class="<?= esc($icon); ?> me-1"></i> <?= esc($m['type_label'] ?? $m['type']); ?>
                            </span>
                        </td>
                        <td class="text-end <?= $amountColor; ?>" style="font-size: 0.95rem;">
                            <?= $sign; ?> <?= esc($currency); ?> <?= number_format((float) ($m['amount'] ?? 0), 2); ?>
                        </td>
                        <td>
                            <span class="badge bg-light text-primary border border-primary-subtle fw-semibold">
                                <i class="fa-duotone fa-solid fa-store me-1"></i> <?= esc($m['store_name'] ?? 'Operador'); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (! empty($m['beneficiary_name']) && ! in_array($m['beneficiary_name'], ['Mi Punto de Venta', 'Mi Cuenta de Operador', 'Operador'], true)) : ?>
                                <div class="fw-bold text-dark"><?= esc($m['beneficiary_name']); ?></div>
                                <?php if (! empty($m['beneficiary_username'])) : ?>
                                    <small class="text-muted">@<?= esc($m['beneficiary_username']); ?></small>
                                <?php endif; ?>
                            <?php else : ?>
                                <span class="text-muted fst-italic"><?= esc($m['beneficiary_name'] ?? 'Operador'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (! empty($m['beneficiary_document'])) : ?>
                                <span class="badge bg-light text-dark border"><?= esc($m['beneficiary_document']); ?></span>
                            <?php else : ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (! empty($m['ref_code'])) : ?>
                                <span class="font-monospace fw-bold text-primary"><?= esc($m['ref_code']); ?></span>
                            <?php else : ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php
                            $st = (int) ($m['status'] ?? 0);
                            $stClass = match($st) {
                                2 => 'bg-success',
                                1 => 'bg-warning text-dark',
                                default => 'bg-danger'
                            };
                            ?>
                            <span class="badge <?= $stClass; ?>">
                                <?= esc($m['status_label'] ?? ($st === 2 ? 'Aprobado' : ($st === 1 ? 'Pendiente' : 'Rechazado'))); ?>
                            </span>
                        </td>
                        <td>
                            <small class="text-muted d-block" style="max-width: 260px; white-space: normal;">
                                <?= esc($m['detail'] ?? '-'); ?>
                            </small>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="fa-duotone fa-solid fa-receipt fs-1 d-block mb-2 text-secondary"></i>
                        No se encontraron movimientos registrados para este criterio de búsqueda.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
