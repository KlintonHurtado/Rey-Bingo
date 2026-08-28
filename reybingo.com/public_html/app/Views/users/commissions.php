<?php
$currency = esc(systemGet('currency') ?? '$');
$filters = $filters ?? [];
$activeTab = $activeTab ?? 'network';
$networkSummary = $networkSummary ?? [];
$playerReferrals = $playerReferrals ?? ['stats' => [], 'items' => []];
$referralRatePct = (float) ($referralRatePct ?? 0);

$opSum = $networkSummary['operators'] ?? [];
$stSum = $networkSummary['stores'] ?? [];
$totSum = $networkSummary['totals'] ?? [];
$refStats = $playerReferrals['stats'] ?? [];
$refItems = $playerReferrals['items'] ?? [];

$dateFrom = (string) ($filters['date_from'] ?? '');
$dateTo = (string) ($filters['date_to'] ?? '');
$search = (string) ($filters['search'] ?? '');
$rateType = (string) ($filters['rate_type'] ?? 'all');
?>
<?= view('games/partials/admin_nav_cluster', [
    'activeNav' => 'commissions',
    'showHome' => true,
    'showStatistics' => false,
    'showUsers' => false,
]) ?>

<a class="btn btn-small btn-logout" href="<?= site_url('logout'); ?>"><i class="fa-duotone fa-solid fa-arrow-right-from-arc"></i></a>

<div class="container admin-stores-page">
    <div class="row d-flex justify-content-center">
        <div class="col-md-12">
            <div class="card mb-3 admin-stores-card">
                <div class="card-body p-3">
                    <div class="admin-stores-header d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div class="flex-grow-1">
                            <h5 class="mb-1"><i class="fa-duotone fa-solid fa-file-invoice-dollar"></i> Comisiones</h5>
                            <p class="text-muted small mb-0">Red tercerizada (operadores y puntos de venta) y comisiones por referidos entre jugadores.</p>
                        </div>
                    </div>

                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab === 'network' ? 'active' : ''; ?>" href="<?= site_url('users/commissions?tab=network'); ?>">
                                <i class="fa-duotone fa-solid fa-network-wired me-1"></i> Red Tercerizada
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab === 'player_referrals' ? 'active' : ''; ?>" href="<?= site_url('users/commissions?tab=player_referrals'); ?>">
                                <i class="fa-duotone fa-solid fa-user-group me-1"></i> Referidos Jugadores
                            </a>
                        </li>
                    </ul>

                    <?php if ($activeTab === 'network') : ?>
                        <form method="get" action="<?= site_url('users/commissions'); ?>" class="row g-2 align-items-end mb-3">
                            <input type="hidden" name="tab" value="network">
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label small mb-1">Desde</label>
                                <input type="date" class="form-control form-bingo" name="date_from" value="<?= esc($dateFrom); ?>">
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label small mb-1">Hasta</label>
                                <input type="date" class="form-control form-bingo" name="date_to" value="<?= esc($dateTo); ?>">
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label small mb-1">Tipo</label>
                                <select class="form-select form-bingo" name="rate_type">
                                    <option value="all" <?= $rateType === 'all' ? 'selected' : ''; ?>>Todos</option>
                                    <option value="ggr" <?= $rateType === 'ggr' ? 'selected' : ''; ?>>GGR</option>
                                    <option value="recharge" <?= $rateType === 'recharge' ? 'selected' : ''; ?>>Recargas</option>
                                    <option value="withdraw" <?= $rateType === 'withdraw' ? 'selected' : ''; ?>>Retiros</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label small mb-1">Buscar</label>
                                <input type="text" class="form-control form-bingo" name="search" value="<?= esc($search); ?>" placeholder="Nombre, código, ref...">
                            </div>
                            <div class="col-md-5 col-sm-12 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary"><i class="fa-duotone fa-solid fa-filter me-1"></i> Filtrar</button>
                                <a href="<?= site_url('users/commissions?tab=network'); ?>" class="btn btn-outline-secondary">Limpiar</a>
                                <button type="button" class="btn btn-success" onclick="exportNetworkCommissions();">
                                    <i class="fa-duotone fa-solid fa-file-excel me-1"></i> Descargar Excel Red
                                </button>
                            </div>
                        </form>

                        <div class="row g-2 mb-3">
                            <div class="col-12">
                                <h6 class="text-muted small fw-bold mb-2">OPERADORES — ganancia diferencial</h6>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card border-0 shadow-sm p-2" style="border-left: 3px solid #ffc107;">
                                    <small class="text-muted">Total GGR</small>
                                    <strong><?= $currency; ?> <?= number_format((float) ($opSum['ggr'] ?? 0), 2); ?></strong>
                                    <small class="text-muted d-block">Apostado: <?= $currency; ?> <?= number_format((float) ($opSum['ggr_stake'] ?? 0), 2); ?></small>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card border-0 shadow-sm p-2" style="border-left: 3px solid #0dcaf0;">
                                    <small class="text-muted">Total Recargas</small>
                                    <strong><?= $currency; ?> <?= number_format((float) ($opSum['recharge'] ?? 0), 2); ?></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card border-0 shadow-sm p-2" style="border-left: 3px solid #dc3545;">
                                    <small class="text-muted">Total Retiros</small>
                                    <strong><?= $currency; ?> <?= number_format((float) ($opSum['withdraw'] ?? 0), 2); ?></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card border-0 shadow-sm p-2 bg-light" style="border-left: 3px solid #6236ff;">
                                    <small class="text-muted">Total Operadores</small>
                                    <strong class="text-primary"><?= $currency; ?> <?= number_format((float) ($opSum['total'] ?? 0), 2); ?></strong>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-12">
                                <h6 class="text-muted small fw-bold mb-2">PUNTOS DE VENTA / AGENCIAS</h6>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card border-0 shadow-sm p-2" style="border-left: 3px solid #ffc107;">
                                    <small class="text-muted">Total GGR</small>
                                    <strong><?= $currency; ?> <?= number_format((float) ($stSum['ggr'] ?? 0), 2); ?></strong>
                                    <small class="text-muted d-block">Apostado: <?= $currency; ?> <?= number_format((float) ($stSum['ggr_stake'] ?? 0), 2); ?></small>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card border-0 shadow-sm p-2" style="border-left: 3px solid #0dcaf0;">
                                    <small class="text-muted">Total Recargas</small>
                                    <strong><?= $currency; ?> <?= number_format((float) ($stSum['recharge'] ?? 0), 2); ?></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card border-0 shadow-sm p-2" style="border-left: 3px solid #dc3545;">
                                    <small class="text-muted">Total Retiros</small>
                                    <strong><?= $currency; ?> <?= number_format((float) ($stSum['withdraw'] ?? 0), 2); ?></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card border-0 shadow-sm p-2 bg-light" style="border-left: 3px solid #198754;">
                                    <small class="text-muted">Total PV</small>
                                    <strong class="text-success"><?= $currency; ?> <?= number_format((float) ($stSum['total'] ?? 0), 2); ?></strong>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm p-3 mb-0" style="background: rgba(98,54,255,0.06);">
                            <div class="row text-center g-2">
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Total GGR Red</small>
                                    <strong><?= $currency; ?> <?= number_format((float) ($totSum['ggr'] ?? 0), 2); ?></strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Total Recargas Red</small>
                                    <strong><?= $currency; ?> <?= number_format((float) ($totSum['recharge'] ?? 0), 2); ?></strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Total Retiros Red</small>
                                    <strong><?= $currency; ?> <?= number_format((float) ($totSum['withdraw'] ?? 0), 2); ?></strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Total Comisiones Red</small>
                                    <strong class="text-primary fs-5"><?= $currency; ?> <?= number_format((float) ($totSum['total'] ?? 0), 2); ?></strong>
                                </div>
                            </div>
                            <p class="small text-muted mt-2 mb-0">
                                El Excel incluye el detalle línea por línea de todos los operadores y puntos de venta, más totales por GGR, recargas y retiros.
                            </p>
                        </div>
                    <?php else : ?>
                        <form method="get" action="<?= site_url('users/commissions'); ?>" class="row g-2 align-items-end mb-3">
                            <input type="hidden" name="tab" value="player_referrals">
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label small mb-1">Desde</label>
                                <input type="date" class="form-control form-bingo" name="date_from" value="<?= esc($dateFrom); ?>">
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label small mb-1">Hasta</label>
                                <input type="date" class="form-control form-bingo" name="date_to" value="<?= esc($dateTo); ?>">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label small mb-1">Buscar jugador</label>
                                <input type="text" class="form-control form-bingo" name="search" value="<?= esc($search); ?>" placeholder="Nombre, username, documento...">
                            </div>
                            <div class="col-md-4 col-sm-12 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary"><i class="fa-duotone fa-solid fa-filter me-1"></i> Filtrar</button>
                                <a href="<?= site_url('users/commissions?tab=player_referrals'); ?>" class="btn btn-outline-secondary">Limpiar</a>
                                <button type="button" class="btn btn-success" onclick="exportPlayerReferralCommissions();">
                                    <i class="fa-duotone fa-solid fa-file-excel me-1"></i> Descargar Excel
                                </button>
                            </div>
                        </form>

                        <div class="alert alert-info py-2 small mb-3">
                            Comisión por referido de jugador: <strong><?= number_format($referralRatePct, 2); ?>%</strong> del primer depósito del jugador invitado (cuando se aprueba el depósito).
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6 col-md-3">
                                <div class="card border-0 shadow-sm p-2 bg-light">
                                    <small class="text-muted">Total pagado</small>
                                    <strong class="text-success"><?= $currency; ?> <?= number_format((float) ($refStats['total_paid'] ?? 0), 2); ?></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card border-0 shadow-sm p-2">
                                    <small class="text-muted">Referidos pagados</small>
                                    <strong><?= (int) ($refStats['count_paid'] ?? 0); ?></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card border-0 shadow-sm p-2">
                                    <small class="text-muted">Pendientes</small>
                                    <strong class="text-warning"><?= (int) ($refStats['count_pending'] ?? 0); ?></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card border-0 shadow-sm p-2">
                                    <small class="text-muted">Total registros</small>
                                    <strong><?= (int) ($refStats['count_total'] ?? 0); ?></strong>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive scroll-pane" style="max-height: 480px;">
                            <table class="table table-sm table-striped align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Jugador referidor</th>
                                        <th>Documento</th>
                                        <th>Jugador referido</th>
                                        <th>Documento</th>
                                        <th class="text-end">Comisión</th>
                                        <th class="text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (! empty($refItems)) : ?>
                                        <?php foreach ($refItems as $it) : ?>
                                            <tr>
                                                <td class="text-nowrap"><?= esc($it['datetime'] ?? '-'); ?></td>
                                                <td>
                                                    <strong><?= esc($it['referrer_name'] ?? '-'); ?></strong>
                                                    <?php if (! empty($it['referrer_username'])) : ?>
                                                        <small class="text-muted d-block">@<?= esc($it['referrer_username']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= esc($it['referrer_document'] ?? '-'); ?></td>
                                                <td>
                                                    <strong><?= esc($it['referred_name'] ?? '-'); ?></strong>
                                                    <?php if (! empty($it['referred_username'])) : ?>
                                                        <small class="text-muted d-block">@<?= esc($it['referred_username']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= esc($it['referred_document'] ?? '-'); ?></td>
                                                <td class="text-end fw-semibold text-success">
                                                    <?= $it['amount'] > 0 ? '+' . $currency . ' ' . number_format((float) $it['amount'], 2) : '-'; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php
                                                    $stLabel = (string) ($it['status_label'] ?? '');
                                                    $stClass = $stLabel === 'Pagada' ? 'bg-success' : ($stLabel === 'Rechazada' ? 'bg-secondary' : 'bg-warning text-dark');
                                                    ?>
                                                    <span class="badge <?= $stClass; ?>"><?= esc($stLabel); ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">No hay comisiones de referidos entre jugadores en este período.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function exportNetworkCommissions() {
        const params = new URLSearchParams(window.location.search);
        params.delete('tab');
        const qs = params.toString();
        window.location.href = '<?= site_url('users/exportNetworkCommissions'); ?>' + (qs ? '?' + qs : '');
    }

    function exportPlayerReferralCommissions() {
        const params = new URLSearchParams(window.location.search);
        params.set('tab', 'player_referrals');
        const qs = params.toString();
        window.location.href = '<?= site_url('users/exportPlayerReferralCommissions'); ?>' + (qs ? '?' + qs : '');
    }
</script>
