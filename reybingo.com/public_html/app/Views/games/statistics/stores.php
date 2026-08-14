<div class="row mt-4">
    <div class="col-md-12">
        <h4>Gestión de Puntos de Venta</h4>
    </div>
</div>

<div class="card mt-2">
    <div class="row">
        <div class="col-12 col-md">
            <div class="card bingo-bg-primary text-white m-2">
                <div class="card-body">
                    <h5>Total Puntos de Venta</h5>
                    <h2><?= number_format($stats['total_stores'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md">
            <div class="card bingo-bg-success text-white m-2">
                <div class="card-body">
                    <h5>Puntos de Venta Activos</h5>
                    <h2><?= number_format($stats['active_stores'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md">
            <div class="card bingo-bg-danger text-white m-2">
                <div class="card-body">
                    <h5>Puntos de Venta Suspendidos</h5>
                    <h2><?= number_format($stats['banned_stores'] ?? 0); ?></h2>
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
                    <h5>Puntos Nuevos Hoy</h5>
                    <h2><?= number_format($stats['today_stores'] ?? 0); ?></h2>
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
                        <div class="col-md-5">
                            <label class="form-label small">Buscar punto de venta</label>
                            <input type="text" class="form-control form-control-lg form-bingo" id="searchStores" placeholder="Buscar por nombre, negocio, código, correo, teléfono..." value="<?= esc($search ?? ''); ?>" onkeyup="if(event.key === 'Enter') filterStores();">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Estado</label>
                            <select class="form-control form-control-lg form-bingo" id="statusFilterStores" onchange="filterStores()">
                                <option value="all" <?= ($status ?? 'all') == 'all' ? 'selected' : ''; ?>>Todos los estados</option>
                                <option value="1" <?= ($status ?? '') === '1' ? 'selected' : ''; ?>>Activo</option>
                                <option value="0" <?= ($status ?? '') === '0' ? 'selected' : ''; ?>>Suspendido</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Operador</label>
                            <select class="form-control form-control-lg form-bingo" id="operatorFilterStores" onchange="filterStores()">
                                <option value="all" <?= ($operator_id ?? 'all') == 'all' ? 'selected' : ''; ?>>Todos los operadores</option>
                                <?php foreach ($operators_list ?? [] as $opItem): ?>
                                    <option value="<?= (int) $opItem['id']; ?>" <?= ((string)($operator_id ?? '')) === ((string)$opItem['id']) ? 'selected' : ''; ?>>
                                        <?= esc(trim(($opItem['firstname'] ?? '') . ' ' . ($opItem['lastname'] ?? ''))); ?> (<?= esc($opItem['code'] ?? ''); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-primary btn-bingo w-100 py-2" onclick="filterStores();">
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
                <h5>Lista de Puntos de Venta</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Información del Punto de Venta</th>
                                <th>Operador Asignado</th>
                                <th>Billetera</th>
                                <th>Jugadores & Actividad</th>
                                <th>Estado</th>
                                <th>Registro</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($stores)): ?>
                                <?php foreach ($stores as $st): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($st['image'])): ?>
                                                <img src="<?= site_url('uploads/users/' . $st['image']); ?>" class="rounded-circle me-3" width="50" height="50" style="object-fit:cover;">
                                            <?php else: ?>
                                                <div class="bingo-bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; text-transform: uppercase;">
                                                    <span class="text-white fw-bold"><?= strtoupper(substr($st['business_name'] ?? ($st['firstname'] ?? 'P'), 0, 1)); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <strong><?= esc($st['business_name'] ?? (trim(($st['firstname'] ?? '') . ' ' . ($st['lastname'] ?? '')))); ?></strong>
                                                <?php if (!empty($st['business_name']) && trim(($st['firstname'] ?? '') . ' ' . ($st['lastname'] ?? '')) !== ''): ?>
                                                    <small class="text-muted d-block"><?= esc(trim(($st['firstname'] ?? '') . ' ' . ($st['lastname'] ?? ''))); ?></small>
                                                <?php endif; ?>
                                                <small class="text-muted">
                                                    @<?= esc($st['username'] ?? ''); ?>
                                                    <?php if (!empty($st['phone'])): ?>
                                                        - <i class="fa-duotone fa-phone text-muted"></i> <?= esc($st['phone']); ?>
                                                    <?php endif; ?>
                                                </small><br>
                                                <div class="mt-1">
                                                    <small class="text-muted">
                                                        <i class="fa-duotone fa-envelope"></i> <?= esc($st['email'] ?? ''); ?>
                                                        <?php if (!empty($st['code'])): ?>
                                                            · <span class="badge bg-secondary"><?= esc($st['code']); ?></span>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($st['operator_name']) && $st['operator_name'] !== '-'): ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fa-solid fa-user-tie me-1"></i> <?= esc($st['operator_name']); ?>
                                            </span>
                                            <?php if (!empty($st['operator_code'])): ?>
                                                <small class="d-block text-muted mt-1"><?= esc($st['operator_code']); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Sin operador</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-success"><?= systemGet('currency'); ?> <?= number_format((float)($st['wallet'] ?? 0), 2); ?></strong>
                                    </td>
                                    <td>
                                        <small>
                                            Jugadores afiliados: <strong class="text-primary"><?= number_format((int)($st['players_count'] ?? 0)); ?></strong><br>
                                            Recargas: <strong class="text-success"><?= systemGet('currency'); ?> <?= number_format((float) ($st['total_deposits'] ?? 0), 2); ?></strong><br>
                                            Retiros: <strong class="text-danger"><?= systemGet('currency'); ?> <?= number_format((float) ($st['total_retires'] ?? 0), 2); ?></strong><br>
                                            <?php if (!empty($st['last_activity'])): ?>
                                                <span class="text-muted">Últ. act: <?= date('d/m/Y H:i', strtotime($st['last_activity'])); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">Sin actividad</span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge <?= ($st['status'] ?? 1) == 1 ? 'bg-success' : 'bg-danger'; ?>">
                                            <?= ($st['status'] ?? 1) == 1 ? translate('active') : translate('banned'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?= !empty($st['created_at']) ? date('d/m/Y', strtotime($st['created_at'])) : '-'; ?></small>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-info text-white btn-sm" onclick="viewUser(<?= (int) $st['id']; ?>)" title="Ver detalles del punto de venta">
                                            <i class="fa-duotone fa-eye fs-5 me-1"></i> Ver detalles
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No se encontraron puntos de venta
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
                    $totalRecords = count($stores ?? []);

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
                                de <?= number_format($totalRecords); ?> puntos de venta
                            </span>
                        </div>
                        <div class="col-12 col-md text-center">
                            <nav class="d-flex justify-content-center align-items-center">
                                <ul class="pagination mb-0">
                                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : ''; ?>">
                                        <button class="page-link" type="button" onclick="goToStoresPage(<?= $currentPage - 1; ?>)">&laquo;</button>
                                    </li>
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $i == $currentPage ? 'active' : ''; ?>">
                                            <button class="page-link" type="button" onclick="goToStoresPage(<?= $i; ?>)"><?= $i; ?></button>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                                        <button class="page-link" type="button" onclick="goToStoresPage(<?= $currentPage + 1; ?>)">&raquo;</button>
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
    function filterStores() {
        var search = $('#searchStores').val() || '';
        var status = $('#statusFilterStores').val() || 'all';
        var operatorId = $('#operatorFilterStores').val() || 'all';
        statisticsGet('stores', {
            search: search,
            status: status,
            operator_id: operatorId,
            page: 1
        });
    }

    function goToStoresPage(page) {
        var search = $('#searchStores').val() || '';
        var status = $('#statusFilterStores').val() || 'all';
        var operatorId = $('#operatorFilterStores').val() || 'all';
        statisticsGet('stores', {
            search: search,
            status: status,
            operator_id: operatorId,
            page: page
        });
    }
</script>
