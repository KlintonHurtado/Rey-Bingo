<div class="row mt-4">
    <div class="col-md-12">
        <h4>Gestión de Operadores</h4>
    </div>
</div>

<div class="card mt-2">
    <div class="row">
        <div class="col-12 col-md">
            <div class="card bingo-bg-primary text-white m-2">
                <div class="card-body">
                    <h5>Total Operadores</h5>
                    <h2><?= number_format($stats['total_operators'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md">
            <div class="card bingo-bg-success text-white m-2">
                <div class="card-body">
                    <h5>Operadores Activos</h5>
                    <h2><?= number_format($stats['active_operators'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md">
            <div class="card bingo-bg-danger text-white m-2">
                <div class="card-body">
                    <h5>Operadores Suspendidos</h5>
                    <h2><?= number_format($stats['banned_operators'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
    </div>
</div>
        
<div class="card mt-3">
    <div class="row">
        <div class="col-12 col-md">
            <div class="card bingo-bg-info text-white m-2">
                <div class="card-body">
                    <h5>Saldo Total Billetera</h5>
                    <h2><?= systemGet('currency'); ?> <?= number_format($stats['total_wallet'] ?? 0, 2); ?></h2>
                </div>
            </div>
        </div>

        <div class="col-12 col-md">
            <div class="card bingo-bg-warning text-white m-2">
                <div class="card-body">
                    <h5>Operadores Nuevos Hoy</h5>
                    <h2><?= number_format($stats['today_operators'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
            
        <div class="col-12 col-md">
            <div class="card bingo-bg-secondary text-white m-2">
                <div class="card-body">
                    <h5>Promedio Billetera</h5>
                    <h2><?= systemGet('currency'); ?> <?= number_format($stats['avg_wallet'] ?? 0, 2); ?></h2>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="collapse show">
                <div class="card-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Buscar operador</label>
                            <input type="text" class="form-control form-control-lg form-bingo" id="searchOperators" placeholder="Buscar por nombre, código, correo, teléfono..." value="<?= esc($search ?? ''); ?>" onkeyup="if(event.key === 'Enter') filterOperators();">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Estado</label>
                            <select class="form-control form-control-lg form-bingo" id="statusFilterOperators" onchange="filterOperators()">
                                <option value="all" <?= ($status ?? 'all') == 'all' ? 'selected' : ''; ?>>Todos los estados</option>
                                <option value="1" <?= ($status ?? '') === '1' ? 'selected' : ''; ?>>Activo</option>
                                <option value="0" <?= ($status ?? '') === '0' ? 'selected' : ''; ?>>Suspendido</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-primary btn-bingo w-100 py-2" onclick="filterOperators();">
                                <i class="fa-duotone fa-solid fa-magnifying-glass me-1"></i> Buscar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header pt-3">
                <h5>Lista de Operadores</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Información del Operador</th>
                                <th>Rol</th>
                                <th>Billetera</th>
                                <th>Puntos de Venta & Actividad</th>
                                <th>Estado</th>
                                <th>Registro</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($operators)): ?>
                                <?php foreach ($operators as $op): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($op['image'])): ?>
                                                <img src="<?= site_url('uploads/users/' . $op['image']); ?>" class="rounded-circle me-3" width="50" height="50" style="object-fit:cover;">
                                            <?php else: ?>
                                                <div class="bingo-bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; text-transform: uppercase;">
                                                    <span class="text-white fw-bold"><?= strtoupper(substr($op['firstname'] ?? 'O', 0, 1)); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <strong><?= esc(($op['firstname'] ?? '') . ' ' . ($op['lastname'] ?? '')); ?></strong><br>
                                                <small class="text-muted">
                                                    @<?= esc($op['username'] ?? ''); ?>
                                                    <?php if (!empty($op['phone'])): ?>
                                                        - <i class="fa-duotone fa-phone text-muted"></i> <?= esc($op['phone']); ?>
                                                    <?php endif; ?>
                                                </small><br>
                                                <div class="mt-1">
                                                    <small class="text-muted">
                                                        <i class="fa-duotone fa-envelope"></i> <?= esc($op['email'] ?? ''); ?>
                                                        <?php if (!empty($op['code'])): ?>
                                                            · <span class="badge bg-secondary"><?= esc($op['code']); ?></span>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning text-dark">
                                            <i class="fa-solid fa-user-tie me-1"></i> Operador
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-success"><?= systemGet('currency'); ?> <?= number_format((float)($op['wallet'] ?? 0), 2); ?></strong>
                                    </td>
                                    <td>
                                        <small>
                                            Puntos de venta: <strong class="text-primary"><?= number_format((int)($op['stores_count'] ?? 0)); ?></strong><br>
                                            Recargas: <strong class="text-success"><?= systemGet('currency'); ?> <?= number_format((float) ($op['total_deposits'] ?? 0), 2); ?></strong><br>
                                            Retiros: <strong class="text-danger"><?= systemGet('currency'); ?> <?= number_format((float) ($op['total_retires'] ?? 0), 2); ?></strong><br>
                                            <?php if (!empty($op['last_activity'])): ?>
                                                <span class="text-muted">Últ. act: <?= date('d/m/Y H:i', strtotime($op['last_activity'])); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">Sin actividad</span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge <?= ($op['status'] ?? 1) == 1 ? 'bg-success' : 'bg-danger'; ?>">
                                            <?= ($op['status'] ?? 1) == 1 ? translate('active') : translate('banned'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?= !empty($op['created_at']) ? date('d/m/Y', strtotime($op['created_at'])) : '-'; ?></small>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-info text-white btn-sm" onclick="viewUser(<?= (int) $op['id']; ?>)" title="Ver detalles del operador">
                                            <i class="fa-duotone fa-eye fs-5 me-1"></i> Ver detalles
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No se encontraron operadores
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php 
                    $showPagination = false;
                    $totalPages = 1;
                    $currentPage = $current_page ?? 1;
                    $totalRecords = count($operators ?? []);

                    if (isset($pager) && $pager) {
                        if (method_exists($pager, 'getLastPage')) {
                            $totalPages = $pager->getLastPage();
                            $showPagination = $totalPages > 1;
                        } elseif (method_exists($pager, 'getPageCount')) {
                            $totalPages = $pager->getPageCount();
                            $showPagination = $totalPages > 1;
                        }
                        if (method_exists($pager, 'getTotal')) {
                            $totalRecords = $pager->getTotal();
                        }
                    }
                ?>

                <?php if ($showPagination): ?>
                    <div class="row mt-4">
                        <div class="col-12 col-md text-center mt-2 mb-sm-3">
                            <span class="text-muted">
                                Mostrando 
                                <?= ($currentPage - 1) * ($per_page ?? 10) + 1; ?> - 
                                <?= min($currentPage * ($per_page ?? 10), $totalRecords); ?> 
                                de <?= number_format($totalRecords); ?> operadores
                            </span>
                        </div>
                        <div class="col-12 col-md text-center">
                            <nav class="d-flex justify-content-center align-items-center">
                                <ul class="pagination mb-0">
                                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : ''; ?>">
                                        <button class="page-link" type="button" onclick="goToOperatorsPage(<?= $currentPage - 1; ?>)">&laquo;</button>
                                    </li>
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $i == $currentPage ? 'active' : ''; ?>">
                                            <button class="page-link" type="button" onclick="goToOperatorsPage(<?= $i; ?>)"><?= $i; ?></button>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                                        <button class="page-link" type="button" onclick="goToOperatorsPage(<?= $currentPage + 1; ?>)">&raquo;</button>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function filterOperators() {
        var search = $('#searchOperators').val() || '';
        var status = $('#statusFilterOperators').val() || 'all';
        statisticsGet('operators', {
            search: search,
            status: status,
            page: 1
        });
    }

    function goToOperatorsPage(page) {
        var search = $('#searchOperators').val() || '';
        var status = $('#statusFilterOperators').val() || 'all';
        statisticsGet('operators', {
            search: search,
            status: status,
            page: page
        });
    }
</script>
