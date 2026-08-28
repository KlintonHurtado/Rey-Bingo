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
$networkEntities = $networkEntities ?? ['operators' => [], 'stores' => []];
$refStats = $playerReferrals['stats'] ?? [];
$refItems = $playerReferrals['items'] ?? [];

$dateFrom = (string) ($filters['date_from'] ?? '');
$dateTo = (string) ($filters['date_to'] ?? '');
$search = (string) ($filters['search'] ?? '');
$rateType = (string) ($filters['rate_type'] ?? 'all');
?>
<div class="admin-stores-scroll">
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
                        <?= view('users/partials/network_commissions_tab', [
                            'currency' => $currency,
                            'filters' => $filters,
                            'networkSummary' => $networkSummary,
                            'networkEntities' => $networkEntities,
                        ]); ?>
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
</div>

<script type="text/javascript">
    function viewUser(userId) {
        $.ajax({
            url: '<?= site_url('users/getUserDetails/'); ?>' + userId,
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.success && response.html) {
                    $('#userDetailsContent').html(response.html);
                    $('#modalUserDetails').modal('show');
                } else {
                    Toastify({
                        text: (response && response.error) ? response.error : 'Usuario no encontrado',
                        duration: 3000,
                        gravity: 'top',
                        position: 'right',
                        style: { background: '#dc3545' }
                    }).showToast();
                }
            }
        });
    }

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
