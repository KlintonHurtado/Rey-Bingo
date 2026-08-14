<table class="table table-striped mb-0">
    <thead>
        <tr>
            <th>Ref / ID</th>
            <th>Fecha</th>
            <th>Jugador</th>
            <th class="text-center">Cédula</th>
            <th class="text-center">Código</th>
            <th class="text-end">Monto Pagado</th>
            <th class="text-center">Estado</th>
        </tr>
    </thead>
    <tbody>
        <?php if (! empty($retires)) : ?>
            <?php foreach ($retires as $retire) : ?>
                <tr>
                    <td>
                        <strong class="text-primary">#<?= esc(str_pad($retire['id'], 4, '0', STR_PAD_LEFT)); ?></strong>
                    </td>
                    <td>
                        <small class="text-muted"><?= esc(date('d/m/Y H:i', strtotime($retire['updated_at'] ?? $retire['created_at']))); ?></small>
                    </td>
                    <td>
                        <strong><?= esc($retire['player_name']); ?></strong><br>
                        <small class="text-muted"><?= esc($retire['player_code']); ?></small>
                    </td>
                    <td class="text-center font-monospace">
                        <?= esc($retire['document'] ?: '-'); ?>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-primary font-monospace"><?= esc($retire['code']); ?></span>
                    </td>
                    <td class="text-end">
                        <strong class="text-success"><?= systemGet('currency'); ?> <?= number_format((float) $retire['amount'], 2); ?></strong>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i> Pagado</span>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    <i class="fa-duotone fa-solid fa-receipt fs-2 mb-2 d-block text-secondary"></i>
                    No hay notas de retiro pagadas registradas en este Punto de Venta.
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php if (! empty($showPagination) && $showPagination) : ?>
    <div class="store-prizes-pagination px-2 py-3 border-top">
        <div class="text-center text-muted small mb-2">
            Mostrando <?= ($currentPage - 1) * $per_page + 1; ?> - <?= min($currentPage * $per_page, $totalRecords); ?> de <?= number_format($totalRecords); ?> retiros
        </div>
        <nav aria-label="Navegación de retiros">
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="javascript:void(0);" onclick="storePrizesGetPage(<?= $currentPage - 1; ?>)" aria-label="Anterior">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                for ($p = $startPage; $p <= $endPage; $p++) :
                ?>
                    <li class="page-item <?= ($p == $currentPage) ? 'active' : ''; ?>">
                        <a class="page-link" href="javascript:void(0);" onclick="storePrizesGetPage(<?= $p; ?>)"><?= $p; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="javascript:void(0);" onclick="storePrizesGetPage(<?= $currentPage + 1; ?>)" aria-label="Siguiente">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
<?php endif; ?>
