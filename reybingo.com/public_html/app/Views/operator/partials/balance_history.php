<div class="table-responsive">
    <table class="table table-striped align-middle mb-0">
        <thead>
            <tr>
                <th>Ref / ID</th>
                <th>Banco</th>
                <th>Monto</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th class="text-center">Comprobante</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($operatorDeposits)): ?>
                <?php foreach ($operatorDeposits as $dep): ?>
                <tr>
                    <td>
                        <strong class="text-primary">#<?= esc($dep['reference'] ?: $dep['id']); ?></strong>
                    </td>
                    <td>
                        <small><?= esc($dep['bank'] ?: 'N/A'); ?></small>
                    </td>
                    <td>
                        <strong class="text-success"><?= systemGet('currency'); ?> <?= number_format((float)$dep['amount'], 2); ?></strong>
                    </td>
                    <td>
                        <?php 
                            $status = (int) ($dep['status'] ?? 1);
                            if ($status === 2) {
                                echo '<span class="badge bg-success"><i class="fa-solid fa-check me-1"></i> Aprobado</span>';
                            } elseif ($status === 0) {
                                echo '<span class="badge bg-danger"><i class="fa-solid fa-xmark me-1"></i> Rechazado</span>';
                            } else {
                                echo '<span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i> Pendiente</span>';
                            }
                        ?>
                    </td>
                    <td>
                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($dep['created_at'] ?? $dep['date'])); ?></small>
                    </td>
                    <td class="text-center">
                        <?php if (!empty($dep['voucher'])): ?>
                            <a href="<?= site_url('uploads/vouchers/' . $dep['voucher']); ?>" target="_blank" class="btn btn-sm btn-outline-info p-1 px-2" title="Ver comprobante">
                                <i class="fa-duotone fa-image fs-6"></i>
                            </a>
                        <?php else: ?>
                            <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No hay solicitudes de saldo registradas
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
