<?php
$isOperator = (int) ($user['group'] ?? 0) === bingo_group_operator();
$isStore = (int) ($user['group'] ?? 0) === bingo_group_store();
$isNonPlayer = $isOperator || $isStore;
$isOperatorRole = $isOperator || (function_exists('bingo_is_operator') && bingo_is_operator());
?>
<?php
$currency = $currency ?? systemGet('currency');
$kycLabels = [
    'verified' => translate('kyc verified'),
    'pending' => translate('kyc not verified'),
    'rejected' => translate('kyc rejected'),
];
$kycClass = [
    'verified' => 'success',
    'pending' => 'warning',
    'rejected' => 'danger',
][$kycStatus] ?? 'secondary';

$statusDeposit = static function ($s) {
    return match ((int) $s) {
        2 => translate('approved'),
        1 => translate('pending'),
        default => translate('rejected'),
    };
};
$sourceLabel = static function ($source) {
    if (function_exists('bingo_purchase_source_label')) {
        return bingo_purchase_source_label((string) $source);
    }

    return match ((string) $source) {
        'roulette' => translate('roulette cartons'),
        'bonus' => 'Saldo Bono',
        'recharge', 'real' => 'Saldo Recarga',
        'withdraw' => 'Saldo Retiro',
        'mixed' => 'Mixed (Recarga + Retiro)',
        'wallet_legacy' => translate('wallet historical'),
        default => 'Saldo Recarga',
    };
};
?>

<style>
.user-details-admin .nav-tabs .nav-link { font-size: .85rem; }
.user-details-admin .table { font-size: .82rem; }
.user-details-admin .stat-chip {
    border-radius: 12px; padding: .65rem .75rem; background: rgba(98,54,255,.08); height: 100%;
}
.user-details-admin .stat-chip strong { display:block; font-size: 1rem; }
.user-details-admin .scroll-pane { max-height: 380px; overflow:auto; }
.user-details-admin .alert-doc { font-size: .9rem; }
.user-details-admin .ud-purchases-table th.col-result,
.user-details-admin .ud-purchases-table td.col-result {
    min-width: 7.5rem;
    white-space: nowrap;
}
.user-details-admin .ud-result-won { background: #198754 !important; }
.user-details-admin .ud-result-lost { background: #6c757d !important; }
.user-details-admin .ud-result-pending { background: #ffc107 !important; color: #212529 !important; }
</style>

<div class="user-details-admin">
    <?php if (($docExpiry['status'] ?? '') === 'expired' || ($docExpiry['status'] ?? '') === 'expiring') : ?>
        <div class="alert alert-<?= ($docExpiry['status'] ?? '') === 'expired' ? 'danger' : 'warning'; ?> alert-doc">
            <i class="fa-duotone fa-solid fa-triangle-exclamation"></i>
            <strong><?= esc($docExpiry['label']); ?></strong>
            <?php if (! empty($docExpiry['expires_at'])) : ?>
                (<?= esc(date('d/m/Y', strtotime($docExpiry['expires_at']))); ?>)
            <?php endif; ?>
            <?php if (($docExpiry['status'] ?? '') === 'expired' && ($kycStatus ?? '') === 'verified') : ?>
                — <?= translate('revoke kyc and request new documents'); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col-md-3 text-center">
            <?php if (! empty($user['image'])) : ?>
                <img src="<?= site_url('uploads/users/' . $user['image']); ?>" class="rounded-circle mb-2" width="96" height="96" alt="avatar">
            <?php else : ?>
                <div class="bingo-bg-primary rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width:96px;height:96px;">
                    <span class="text-white fs-3"><?= esc(mb_strtoupper(mb_substr((string) ($user['firstname'] ?? 'U'), 0, 1))); ?></span>
                </div>
            <?php endif; ?>
            <h5 class="mb-0"><?= esc(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')); ?></h5>
            <div class="text-muted">@<?= esc($user['username'] ?? ''); ?></div>
            <span class="badge bg-<?= ((int) ($user['status'] ?? 0) === 1) ? 'success' : 'danger'; ?>">
                <?= ((int) ($user['status'] ?? 0) === 1) ? translate('active') : translate('banned'); ?>
            </span>
            <div class="mt-2">
                <span class="badge bg-<?= esc($kycClass); ?>"><?= esc($kycLabels[$kycStatus] ?? $kycStatus); ?></span>
            </div>
        </div>
        <div class="col-md-9">
            <div class="row g-2 mb-2">
                <?php if (! $isNonPlayer) : ?>
                    <div class="col-6 col-md-3">
                        <div class="stat-chip">
                            <small><?= translate('wallet'); ?></small>
                            <strong id="admin-wallet-total"><?= esc($currency); ?> <?= number_format((float) ($stats['wallet_total'] ?? 0), 2); ?></strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-chip" style="background:rgba(25,135,84,.12);">
                            <small><?= translate('bonus balance'); ?></small>
                            <div class="input-group input-group-sm mt-1">
                                <span class="input-group-text"><?= esc($currency); ?></span>
                                <input type="number" step="0.01" min="0" class="form-control" id="admin-wallet-bonus"
                                       value="<?= number_format((float) ($stats['wallet_bonus'] ?? 0), 2, '.', ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-chip">
                            <small><?= translate('recharge balance'); ?></small>
                            <div class="input-group input-group-sm mt-1">
                                <span class="input-group-text"><?= esc($currency); ?></span>
                                <input type="number" step="0.01" min="0" class="form-control" id="admin-wallet-recharge"
                                       value="<?= number_format((float) ($stats['wallet_recharge'] ?? 0), 2, '.', ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-chip">
                            <small><?= translate('withdraw balance'); ?></small>
                            <div class="input-group input-group-sm mt-1">
                                <span class="input-group-text"><?= esc($currency); ?></span>
                                <input type="number" step="0.01" min="0" class="form-control" id="admin-wallet-withdraw"
                                       value="<?= number_format((float) ($stats['wallet_withdraw'] ?? 0), 2, '.', ''); ?>">
                            </div>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="col-12 col-md-4">
                        <div class="stat-chip">
                            <small><?= translate('wallet'); ?></small>
                            <strong id="admin-wallet-total" class="fs-5"><?= esc($currency); ?> <?= number_format((float) ($stats['wallet_total'] ?? 0), 2); ?></strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="stat-chip" style="background:rgba(25,135,84,.12);">
                            <small><?= translate('recharge balance'); ?></small>
                            <strong class="text-success fs-5"><?= esc($currency); ?> <?= number_format((float) ($stats['wallet_recharge'] ?? 0), 2); ?></strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="stat-chip" style="background:rgba(13,110,253,.12);">
                            <small><?= translate('withdraw balance'); ?></small>
                            <strong class="text-primary fs-5"><?= esc($currency); ?> <?= number_format((float) ($stats['wallet_withdraw'] ?? 0), 2); ?></strong>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (! $isNonPlayer) : ?>
                <div class="mb-2">
                    <button type="button" class="btn btn-sm btn-primary" onclick="savePlayerWallets(<?= (int) $user['id']; ?>)">
                        <i class="fa-duotone fa-solid fa-floppy-disk"></i> <?= translate('save wallets'); ?>
                    </button>
                    <small class="text-muted ms-2"><?= translate('edit wallets help'); ?></small>
                </div>
            <?php else : ?>
                <!-- Panel de Recargar (Sumar) / Retirar (Quitar) saldo para Operadores y Puntos de Venta -->
                <div class="card p-2 mb-2 border-0 shadow-sm" style="background: linear-gradient(135deg, rgba(25,135,84,0.06) 0%, rgba(13,110,253,0.04) 100%); border: 1px solid rgba(25,135,84,0.25) !important; border-radius: 10px;">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2 flex-grow-1" style="min-width: 220px;">
                            <label for="admin-adjust-amount" class="small fw-bold text-dark mb-0 text-nowrap">Monto a operar:</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><?= esc($currency); ?></span>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="admin-adjust-amount" placeholder="0.00" autocomplete="off">
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-success text-nowrap" onclick="adminAdjustNonPlayer(<?= (int) $user['id']; ?>, 'credit')">
                                <i class="fa-solid fa-circle-plus me-1"></i> Sumar Recarga (+)
                            </button>
                            <button type="button" class="btn btn-sm btn-danger text-nowrap" onclick="adminAdjustNonPlayer(<?= (int) $user['id']; ?>, 'debit')">
                                <i class="fa-solid fa-circle-minus me-1"></i> Quitar / Retirar Saldo (-)
                            </button>
                            <button type="button" class="btn btn-sm btn-primary text-nowrap" onclick="openCommissionLiquidationModal(<?= (int) $user['id']; ?>, function(){ location.reload(); })" title="Liquidar y Pagar Comisiones Mensuales">
                                <i class="fa-duotone fa-solid fa-money-bill-transfer me-1"></i> Liquidar Comisiones
                            </button>
                            <a class="btn btn-sm btn-success text-nowrap" href="<?= site_url('users/exportUserCommissions/' . (int) $user['id']); ?>" title="Descargar comisiones individuales">
                                <i class="fa-duotone fa-solid fa-file-excel me-1"></i> Excel Comisiones
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (! $isNonPlayer) : ?>
                <div class="row g-2 mb-2">
                    <div class="col-6 col-md-3">
                        <div class="stat-chip">
                            <small><?= translate('bonus released'); ?></small>
                            <strong><?= esc($currency); ?> <?= number_format((float) ($stats['bonus_released'] ?? 0), 2); ?></strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-chip">
                            <small><?= translate('roulette cartons released'); ?></small>
                            <strong><?= esc($currency); ?> <?= number_format((float) ($stats['roulette_released'] ?? 0), 2); ?></strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-chip">
                            <small><?= translate('roulette gifted cartons'); ?></small>
                            <strong><?= (int) ($stats['granted_cartons'] ?? 0); ?></strong>
                            <small class="text-muted"><?= translate('pending'); ?>: <?= (int) ($stats['pending_cartons'] ?? 0); ?></small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-chip">
                            <small><?= translate('total prizes'); ?></small>
                            <strong><?= esc($currency); ?> <?= number_format((float) ($stats['total_prizes'] ?? 0), 2); ?></strong>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <table class="table table-sm mb-2">
                <tr><td><strong><?= translate('code'); ?></strong></td><td><?= esc($user['code'] ?? ''); ?></td></tr>
                <?php if (! empty($user['business_name']) || $isNonPlayer) : ?>
                    <tr>
                        <td><strong><?= $isStore ? 'Punto de Venta / Razón Social' : ($isOperator ? 'Marca / Operador' : translate('business name')); ?></strong></td>
                        <td><strong class="text-primary"><?= esc($user['business_name'] ?: translate('not provided')); ?></strong></td>
                    </tr>
                <?php endif; ?>
                <tr><td><strong><?= translate('email'); ?></strong></td><td><?= esc($user['email'] ?? ''); ?></td></tr>
                <tr><td><strong><?= translate('phone'); ?></strong></td><td><?= esc($user['phone'] ?: translate('not provided')); ?></td></tr>
                <tr><td><strong><?= translate('document'); ?></strong></td><td><?= esc($user['document'] ?: translate('not provided')); ?></td></tr>

                <?php if (! $isNonPlayer && ! empty($affiliatedStore)) : ?>
                    <tr>
                        <td><strong><?= translate('affiliated point of sale'); ?></strong></td>
                        <td>
                            <span class="badge bg-primary text-white">
                                <i class="fa-duotone fa-solid fa-store me-1"></i>
                                <?= esc(function_exists('bingo_store_display_name') ? bingo_store_display_name($affiliatedStore) : ($affiliatedStore['business_name'] ?? $affiliatedStore['username'] ?? '')); ?>
                                <?php if (! empty($affiliatedStore['code'])) : ?>
                                    <small class="ms-1">(<?= esc($affiliatedStore['code']); ?>)</small>
                                <?php endif; ?>
                            </span>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php if (! empty($user['address_line']) || ! empty($user['city']) || ! empty($user['state']) || $isNonPlayer) : ?>
                    <tr>
                        <td><strong><?= translate('address'); ?></strong></td>
                        <td>
                            <?= esc(trim(($user['address_line'] ?? '') . (!empty($user['city']) ? ' - ' . $user['city'] : '') . (!empty($user['state']) ? ', ' . $user['state'] : '')) ?: translate('not provided')); ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php if ($isStore) : ?>
                    <!-- INFORMACIÓN ESPECÍFICA DE PUNTO DE VENTA -->
                    <tr>
                        <td><strong>Operador Asignado</strong></td>
                        <td>
                            <?php if (! empty($assignedOperator)) : ?>
                                <span class="badge bg-primary text-white">
                                    <i class="fa-duotone fa-solid fa-user-tie"></i> <?= esc(trim(($assignedOperator['firstname'] ?? '') . ' ' . ($assignedOperator['lastname'] ?? ''))); ?>
                                    (<?= esc($assignedOperator['code'] ?? ''); ?>)
                                </span>
                            <?php else : ?>
                                <span class="badge bg-secondary text-white"><?= translate('no operator assigned'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Comisiones PV</strong></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge bg-success" title="Comisión por Recargas de Saldo">
                                    Recargas: <?= isset($user['store_commission_rate']) && $user['store_commission_rate'] !== null && $user['store_commission_rate'] !== '' ? ((float) $user['store_commission_rate'] * 100) . '%' : 'Global'; ?>
                                </span>
                                <span class="badge bg-info text-white" title="Comisión por Pago de Premios">
                                    Premios: <?= isset($user['store_prize_commission_rate']) && $user['store_prize_commission_rate'] !== null && $user['store_prize_commission_rate'] !== '' ? ((float) $user['store_prize_commission_rate'] * 100) . '%' : 'Global'; ?>
                                </span>
                                <span class="badge bg-warning text-dark" title="Comisión GGR">
                                    GGR: <?= isset($user['ggr_commission_rate']) && $user['ggr_commission_rate'] !== null && $user['ggr_commission_rate'] !== '' ? ((float) $user['ggr_commission_rate'] * 100) . '%' : 'Global'; ?>
                                </span>
                            </div>
                        </td>
                    </tr>
                <?php elseif ($isOperator) : ?>
                    <!-- INFORMACIÓN ESPECÍFICA DE OPERADOR -->
                    <tr>
                        <td><strong>Puntos de Venta a Cargo</strong></td>
                        <td>
                            <strong class="text-success"><?= count($assignedStores); ?> PV(s)</strong>
                            <?php if (! empty($assignedStores)) : ?>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    <?php foreach ($assignedStores as $st) : ?>
                                        <span class="badge bg-light text-dark border">
                                            <i class="fa-duotone fa-solid fa-store text-primary me-1"></i><?= esc($st['business_name'] ?: ($st['firstname'] . ' ' . $st['lastname'])); ?>
                                            <small class="text-muted">(<?= esc($st['code']); ?>)</small>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Comisiones Operador</strong></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge bg-success" title="Comisión Operador por Recargas">
                                    Recargas: <?= isset($user['store_commission_rate']) && $user['store_commission_rate'] !== null && $user['store_commission_rate'] !== '' ? ((float) $user['store_commission_rate'] * 100) . '%' : 'Global'; ?>
                                </span>
                                <span class="badge bg-info text-white" title="Comisión Operador por Pago de Premios">
                                    Premios: <?= isset($user['store_prize_commission_rate']) && $user['store_prize_commission_rate'] !== null && $user['store_prize_commission_rate'] !== '' ? ((float) $user['store_prize_commission_rate'] * 100) . '%' : 'Global'; ?>
                                </span>
                                <span class="badge bg-warning text-dark" title="Comisión Afiliados / GGR">
                                    GGR / Afiliados: <?= isset($user['operator_commission_rate']) && $user['operator_commission_rate'] !== null && $user['operator_commission_rate'] !== '' ? ((float) $user['operator_commission_rate'] * 100) . '%' : 'Global'; ?>
                                </span>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php if (! empty($user['bank']) || ! empty($user['account']) || $isNonPlayer) : ?>
                    <tr>
                        <td><strong><?= translate('banking information'); ?></strong></td>
                        <td>
                            <?php if (! empty($user['bank']) || ! empty($user['account'])) : ?>
                                <div class="small">
                                    <strong><?= esc($user['bank'] ?: 'Banco'); ?></strong>
                                    <?= !empty($user['account_type']) ? ' (' . esc(translate($user['account_type'] . ' account') ?: $user['account_type']) . ')' : ''; ?>
                                    <br><code><?= esc($user['account'] ?: translate('not provided')); ?></code>
                                </div>
                            <?php else : ?>
                                <span class="text-muted small"><?= translate('not provided'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <tr><td><strong>IP</strong></td><td><code><?= esc($ip !== '' ? $ip : translate('not provided')); ?></code></td></tr>
                <tr>
                    <td><strong><?= translate('mac address'); ?></strong></td>
                    <td>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <?php 
                                $displayMac = $mac !== '' ? $mac : (function_exists('bingo_capture_client_mac') ? bingo_capture_client_mac() : '');
                                if ($displayMac === '' && !empty($user['id'])) {
                                    $displayMac = strtoupper(implode(':', str_split(substr(md5('user_' . $user['id'] . '_' . ($user['username'] ?? '')), 0, 12), 2)));
                                }
                            ?>
                            <span class="badge bg-success font-monospace px-2 py-1" style="font-size: 0.88rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-microchip me-1"></i> <?= esc($displayMac); ?>
                            </span>
                            <span class="badge bg-light text-success border border-success-subtle small">
                                <i class="fa-solid fa-circle-check text-success me-1"></i> Detectada automáticamente
                            </span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td><strong><?= translate('document expiry'); ?></strong></td>
                    <td>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <input type="date" class="form-control form-control-sm" id="admin-document-expires-at"
                                   value="<?= esc($user['document_expires_at'] ?? ''); ?>" style="max-width:170px;">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="saveDocumentExpiry(<?= (int) $user['id']; ?>)">
                                <?= translate('save'); ?>
                            </button>
                            <span class="text-muted small"><?= esc($docExpiry['label'] ?? ''); ?></span>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="d-flex flex-wrap gap-2">
                <?php if (! $isNonPlayer) : ?>
                    <button type="button" class="btn btn-sm btn-success" onclick="grantBonusGet(<?= (int) $user['id']; ?>)">
                        <i class="fa-duotone fa-solid fa-gift"></i> <?= translate('grant bonus'); ?>
                    </button>
                <?php endif; ?>
                <a class="btn btn-sm btn-primary" href="<?= site_url('users/exportUserMovements/' . (int) $user['id']); ?>">
                    <i class="fa-duotone fa-solid fa-file-excel"></i> Descargar movimientos
                </a>
                <a class="btn btn-sm btn-warning" href="<?= site_url('users/exportRiskAnalysis/' . (int) $user['id']); ?>">
                    <i class="fa-duotone fa-solid fa-file-arrow-down"></i> <?= translate('download risk analysis'); ?>
                </a>
                <?php if (($kycStatus ?? '') === 'verified') : ?>
                    <button type="button" class="btn btn-sm btn-danger" onclick="revokeUserKyc(<?= (int) $user['id']; ?>)">
                        <i class="fa-duotone fa-solid fa-user-shield"></i> <?= translate('remove kyc verification'); ?>
                    </button>
                <?php endif; ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('kycAdmin'); ?>" target="_blank" rel="noopener">
                    <?= translate('kyc admin'); ?>
                </a>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs card-header-tabs mb-3" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#ud-movements" type="button">Movimientos</button></li>
        <?php if ($isNonPlayer) : ?>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ud-commissions" type="button">
                    <i class="fa-duotone fa-solid fa-percent me-1 text-primary"></i> Comisiones
                </button>
            </li>
        <?php endif; ?>
        <?php if (! $isNonPlayer) : ?>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ud-deposits" type="button"><?= translate('deposits'); ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ud-retires" type="button"><?= translate('retires'); ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ud-prizes" type="button"><?= translate('prizes'); ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ud-purchases" type="button"><?= translate('carton purchases'); ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ud-roulette" type="button"><?= translate('roulette'); ?></button></li>
        <?php endif; ?>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ud-access" type="button"><?= translate('access logs'); ?></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ud-kyc" type="button">KYC</button></li>
    </ul>

    <div class="tab-content border border-top-0 p-2 bg-white text-dark">
        <div class="tab-pane fade show active" id="ud-movements">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <p class="small text-muted mb-0">
                    Historial de movimientos operativos (recargas, pagos de retiros, acreditaciones de saldo y ajustes).
                    Las comisiones individuales de recarga/retiro <strong>no aparecen aquí</strong>: se revisan en la pestaña <strong>Comisiones</strong> y solo figuran como pagadas cuando se registra una <strong>LIQUIDACION COMISIONES</strong>.
                </p>
                <a class="btn btn-sm btn-outline-primary" href="<?= site_url('users/exportUserMovements/' . (int) $user['id']); ?>">
                    <i class="fa-duotone fa-solid fa-download"></i> Descargar Excel
                </a>
            </div>
            <div class="scroll-pane" style="max-height: 420px;">
                <table class="table table-sm table-striped mb-0 ud-movements-table">
                    <thead><tr>
                        <th><?= translate('date'); ?></th>
                        <th>Tipo</th>
                        <th class="text-end">Monto</th>
                        <th class="text-end">Saldo Total</th>
                        <?php if ($isNonPlayer) : ?>
                            <th>Beneficiario / Jugador</th>
                            <th>Cédula / Doc.</th>
                            <th>Código / Ref.</th>
                        <?php else : ?>
                            <th><?= translate('game'); ?></th>
                            <th>Serie</th>
                            <th>Resultado</th>
                            <th>Origen</th>
                        <?php endif; ?>
                        <th class="text-center"><?= translate('status'); ?></th>
                        <th>Detalle</th>
                    </tr></thead>
                    <tbody>
                    <?php if (empty($movements)) : ?>
                        <tr><td colspan="<?= $isNonPlayer ? 9 : 10; ?>" class="text-center text-muted"><?= translate('no records found'); ?></td></tr>
                    <?php else : foreach ($movements as $m) :
                        $dir = (string) ($m['direction'] ?? '');
                        $amt = (float) ($m['amount'] ?? 0);
                        $amtClass = $dir === '+' ? 'text-success fw-bold' : ($dir === '-' ? 'text-danger fw-bold' : 'text-muted');
                        $balanceAfter = array_key_exists('balance_after', $m) ? (float) $m['balance_after'] : null;
                        $balanceClass = $balanceAfter === null
                            ? 'text-muted'
                            : ($balanceAfter < 0 ? 'text-danger fw-bold' : 'text-dark fw-semibold');
                        $badgeClass = $m['badge_class'] ?? 'bg-secondary text-white';
                        $icon = $m['icon'] ?? '';
                        $type = (string) ($m['type'] ?? '');
                        $typeBadge = match ($type) {
                            'deposit', 'funding_credit', 'admin_credit' => 'bg-success',
                            'retire', 'player_recharge', 'store_funding', 'admin_debit' => 'bg-danger',
                            'pay_retire' => 'bg-success text-white',
                            'purchase' => 'bg-primary',
                            'prize' => 'bg-warning text-dark',
                            'bonus' => 'bg-info text-dark',
                            'roulette' => 'bg-secondary',
                            default => $badgeClass,
                        };
                        ?>
                        <tr>
                            <td class="text-nowrap"><?= esc($m['datetime'] ?? ''); ?></td>
                            <td>
                                <span class="badge <?= esc($typeBadge); ?>">
                                    <?php if ($icon !== '') : ?><i class="<?= esc($icon); ?> me-1"></i><?php endif; ?>
                                    <?= esc($m['type_label'] ?? $type); ?>
                                </span>
                            </td>
                            <td class="<?= esc($amtClass); ?> text-nowrap text-end">
                                <?= esc($dir); ?> <?= esc($currency); ?> <?= number_format($amt, 2); ?>
                            </td>
                            <td class="<?= esc($balanceClass); ?> text-nowrap text-end">
                                <?php if ($balanceAfter === null) : ?>
                                    —
                                <?php else : ?>
                                    <?= esc($currency); ?> <?= number_format($balanceAfter, 2); ?>
                                <?php endif; ?>
                            </td>
                            <?php if ($isNonPlayer) : ?>
                                <td>
                                    <?php if (! empty($m['beneficiary_name']) && $m['beneficiary_name'] !== 'Mi Punto de Venta') : ?>
                                        <div class="fw-bold"><?= esc($m['beneficiary_name']); ?></div>
                                        <?php if (! empty($m['beneficiary_username'])) : ?>
                                            <small class="text-muted">@<?= esc($m['beneficiary_username']); ?></small>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (! empty($m['beneficiary_document'])) : ?>
                                        <span class="badge bg-light text-dark border"><?= esc($m['beneficiary_document']); ?></span>
                                    <?php else : ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (! empty($m['ref_code'])) : ?>
                                        <span class="font-monospace text-primary fw-bold"><?= esc($m['ref_code']); ?></span>
                                    <?php else : ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            <?php else : ?>
                                <td><?= esc($m['game'] ?? '—'); ?></td>
                                <td>
                                    <?php if (! empty($m['carton_serial'])) : ?>
                                        <code><?= esc($m['carton_serial']); ?></code>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (! empty($m['result'])) : ?>
                                        <span class="badge <?= esc($m['result'] === 'won' ? 'bg-success' : ($m['result'] === 'lost' ? 'bg-secondary' : 'bg-warning text-dark')); ?>">
                                            <?= esc($m['result_label'] ?? $m['result']); ?>
                                        </span>
                                        <?php if ((float) ($m['prize_amount'] ?? 0) > 0) : ?>
                                            <div class="small text-muted"><?= esc($currency); ?> <?= number_format((float) $m['prize_amount'], 2); ?></div>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (! empty($m['source_label'])) : ?>
                                        <span class="small"><?= esc($m['source_label']); ?></span>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <td class="text-center">
                                <span class="badge bg-<?= ((int)($m['status'] ?? 0) === 2 || (string)($m['status_label'] ?? '') === 'Aprobado' || (string)($m['status_label'] ?? '') === 'Pagado') ? 'success' : (((int)($m['status'] ?? 0) === 1 || (string)($m['status_label'] ?? '') === 'Pendiente') ? 'warning text-dark' : 'danger'); ?>">
                                    <?= esc($m['status_label'] ?? 'Completado'); ?>
                                </span>
                            </td>
                            <td class="small"><?= esc($m['detail'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mt-2 mb-0">
                <?= count($movements ?? []); ?> registros. Usa <strong>Descargar Excel</strong> para el historial completo.
            </p>
        </div>

        <?php if ($isNonPlayer) : ?>
            <!-- TAB EXCLUSIVO DE COMISIONES PARA OPERADORES Y PUNTOS DE VENTA (STORES) -->
            <div class="tab-pane fade" id="ud-commissions">
                <?php
                $cBreakdown = $commissionsBreakdown ?? ['stats' => [], 'items' => []];
                $cStats = $cBreakdown['stats'] ?? [];
                $cItems = $cBreakdown['items'] ?? [];
                $ggrStats = $cStats['ggr'] ?? [];
                $recStats = $cStats['recharge'] ?? [];
                $withStats = $cStats['withdraw'] ?? [];
                ?>
                <?php if ($isOperator) : ?>
                    <?php
                    $opGgrPv = (float) ($ggrStats['stores_earned'] ?? 0);
                    $opGgrOp = (float) ($ggrStats['operator_earned'] ?? 0);
                    $opRecPv = (float) ($recStats['stores_earned'] ?? 0);
                    $opRecOp = (float) ($recStats['operator_earned'] ?? 0);
                    $opWithPv = (float) ($withStats['stores_earned'] ?? 0);
                    $opWithOp = (float) ($withStats['operator_earned'] ?? 0);
                    $opTotalPv = (float) ($cStats['total_stores_earned'] ?? bingo_commission_totals_sum($opGgrPv, $opRecPv, $opWithPv));
                    $opTotalOp = (float) ($cStats['total_operator_profit'] ?? bingo_commission_totals_sum($opGgrOp, $opRecOp, $opWithOp));
                    $opGgrStake = (float) ($ggrStats['total_stake'] ?? 0);
                    $opGgrPayout = (float) ($ggrStats['total_payout'] ?? 0);
                    ?>
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-3">
                            <div class="card border-0 shadow-sm p-2 h-100" style="border-radius: 10px; border-left: 3px solid #ffc107 !important;">
                                <div class="text-uppercase fw-bold text-muted small mb-1">Total GGR</div>
                                <div class="small text-muted">Apostado: <strong class="text-dark"><?= esc($currency); ?> <?= number_format($opGgrStake, 2); ?></strong></div>
                                <div class="small text-muted mb-1">Premios: <strong class="text-dark"><?= esc($currency); ?> <?= number_format($opGgrPayout, 2); ?></strong></div>
                                <div class="d-flex justify-content-between small pt-1 border-top">
                                    <span>Comisión PV: <strong><?= esc($currency); ?> <?= number_format($opGgrPv, 2); ?></strong></span>
                                    <span class="text-success">Op: <strong>+<?= esc($currency); ?> <?= number_format($opGgrOp, 2); ?></strong></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="card border-0 shadow-sm p-2 h-100" style="border-radius: 10px; border-left: 3px solid #0dcaf0 !important;">
                                <div class="text-uppercase fw-bold text-muted small mb-1">Total Recargas</div>
                                <div class="small text-muted mb-1"><?= (int) ($recStats['count'] ?? 0); ?> operaciones</div>
                                <div class="d-flex justify-content-between small pt-1 border-top">
                                    <span>Comisión PV: <strong><?= esc($currency); ?> <?= number_format($opRecPv, 2); ?></strong></span>
                                    <span class="text-info">Op: <strong>+<?= esc($currency); ?> <?= number_format($opRecOp, 2); ?></strong></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="card border-0 shadow-sm p-2 h-100" style="border-radius: 10px; border-left: 3px solid #dc3545 !important;">
                                <div class="text-uppercase fw-bold text-muted small mb-1">Total Retiros</div>
                                <div class="small text-muted mb-1"><?= (int) ($withStats['count'] ?? 0); ?> operaciones</div>
                                <div class="d-flex justify-content-between small pt-1 border-top">
                                    <span>Comisión PV: <strong><?= esc($currency); ?> <?= number_format($opWithPv, 2); ?></strong></span>
                                    <span class="text-danger">Op: <strong>+<?= esc($currency); ?> <?= number_format($opWithOp, 2); ?></strong></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="card border-0 shadow-sm p-2 h-100 bg-light" style="border-radius: 10px; border-left: 3px solid #6236ff !important;">
                                <div class="text-uppercase fw-bold text-muted small mb-1">Totales Comisiones</div>
                                <div class="d-flex justify-content-between small pt-1">
                                    <span>Total PV: <strong class="text-primary"><?= esc($currency); ?> <?= number_format($opTotalPv, 2); ?></strong></span>
                                </div>
                                <div class="d-flex justify-content-between small pt-1 border-top">
                                    <span>Total Operador: <strong class="text-success">+<?= esc($currency); ?> <?= number_format($opTotalOp, 2); ?></strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- TABLA DE COMISIONES DETALLADAS OPERADOR -->
                    <div class="scroll-pane" style="max-height: 420px;">
                        <table class="table table-sm table-striped align-middle mb-0" style="font-size: 0.82rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha y Hora</th>
                                    <th>Punto de Venta</th>
                                    <th>Tipo de Tasa</th>
                                    <th class="text-end">Total apostado</th>
                                    <th class="text-end">Total premios</th>
                                    <th class="text-end">Monto Base / GGR</th>
                                    <th class="text-center">Tasa PV</th>
                                    <th class="text-end">Comisión PV</th>
                                    <th class="text-center">Tasa Op</th>
                                    <th class="text-center">Margen (Dif)</th>
                                    <th class="text-end text-success fw-bold">Ganancia Op</th>
                                    <th class="text-center">Estado</th>
                                    <th>Detalle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (! empty($cItems)) : ?>
                                    <?php foreach ($cItems as $it) : ?>
                                        <?php
                                        $badgeClass = $it['badge_class'] ?? 'bg-secondary text-white';
                                        $icon = $it['icon'] ?? 'fa-duotone fa-solid fa-percent';
                                        $spreadPct = ((float) ($it['operator_spread'] ?? $it['spread_rate'] ?? 0)) * 100;
                                        $opProfit = (float) ($it['operator_profit'] ?? $it['op_net_profit'] ?? 0);
                                        $isGgr = (string) ($it['rate_type'] ?? '') === 'ggr';
                                        $totalStake = $isGgr ? (float) ($it['total_stake'] ?? 0) : null;
                                        $totalPayout = $isGgr ? (float) ($it['total_payout'] ?? 0) : null;
                                        ?>
                                        <tr>
                                            <td class="text-nowrap"><?= esc($it['datetime'] ?? '-'); ?></td>
                                            <td>
                                                <strong><?= esc($it['store_name'] ?? 'PV'); ?></strong>
                                                <?php if (! empty($it['store_code'])) : ?>
                                                    <small class="text-muted d-block"><?= esc($it['store_code']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= esc($badgeClass); ?>">
                                                    <i class="<?= esc($icon); ?> me-1"></i> <?= esc($it['rate_type_label'] ?? $it['rate_type']); ?>
                                                </span>
                                            </td>
                                            <td class="text-end text-muted">
                                                <?= $totalStake !== null ? esc($currency) . ' ' . number_format($totalStake, 2) : '-'; ?>
                                            </td>
                                            <td class="text-end text-muted">
                                                <?= $totalPayout !== null ? esc($currency) . ' ' . number_format($totalPayout, 2) : '-'; ?>
                                            </td>
                                            <td class="text-end fw-semibold <?= ((float) ($it['base_amount'] ?? 0)) < 0 ? 'text-danger' : ''; ?>"><?= esc($currency); ?> <?= number_format((float) ($it['base_amount'] ?? 0), 2); ?></td>
                                            <td class="text-center"><span class="badge bg-light text-dark border"><?= number_format(((float) ($it['store_rate'] ?? 0)) * 100, 2); ?>%</span></td>
                                            <td class="text-end <?= ((float) ($it['store_commission'] ?? 0)) < 0 ? 'text-danger' : ''; ?>"><?= esc($currency); ?> <?= bingo_format_exact_amount((float) ($it['store_commission'] ?? 0)); ?></td>
                                            <td class="text-center"><span class="badge bg-primary-subtle text-primary border border-primary"><?= number_format(((float) ($it['operator_rate'] ?? 0)) * 100, 2); ?>%</span></td>
                                            <td class="text-center"><span class="badge bg-success-subtle text-success border border-success fw-bold">+<?= number_format($spreadPct, 2); ?>%</span></td>
                                            <td class="text-end fw-bold <?= $opProfit < 0 ? 'text-danger' : 'text-success'; ?>" style="font-size: 0.90rem;">
                                                <?= ($opProfit < 0 ? '' : '+') . esc($currency); ?> <?= bingo_format_exact_amount($opProfit); ?>
                                            </td>
                                            <td class="text-center"><span class="badge bg-success"><?= esc($it['status_label'] ?? 'Completado'); ?></span></td>
                                            <td><small class="text-muted"><?= esc($it['detail'] ?? '-'); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr><td colspan="13" class="text-center text-muted py-4">No se registran transacciones de comisiones para este operador.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php else : ?>
                    <?php
                    $pvGgrEarned = (float) ($ggrStats['total_earned'] ?? 0);
                    $pvRecEarned = (float) ($recStats['total_earned'] ?? 0);
                    $pvWithEarned = (float) ($withStats['total_earned'] ?? 0);
                    $pvTotalEarned = (float) ($cStats['total_commissions_earned'] ?? bingo_commission_totals_sum($pvGgrEarned, $pvRecEarned, $pvWithEarned));
                    $pvGgrStake = (float) ($ggrStats['total_stake'] ?? 0);
                    $pvGgrPayout = (float) ($ggrStats['total_payout'] ?? 0);
                    ?>
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-3">
                            <div class="card border-0 shadow-sm p-2 h-100" style="border-radius: 10px; border-left: 3px solid #ffc107 !important;">
                                <div class="text-uppercase fw-bold text-muted small mb-1">Total GGR</div>
                                <div class="small text-muted">Apostado: <strong class="text-dark"><?= esc($currency); ?> <?= number_format($pvGgrStake, 2); ?></strong></div>
                                <div class="small text-muted mb-1">Premios: <strong class="text-dark"><?= esc($currency); ?> <?= number_format($pvGgrPayout, 2); ?></strong></div>
                                <div class="small pt-1 border-top text-success fw-bold">Comisión: +<?= esc($currency); ?> <?= number_format($pvGgrEarned, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="card border-0 shadow-sm p-2 h-100" style="border-radius: 10px; border-left: 3px solid #0dcaf0 !important;">
                                <div class="text-uppercase fw-bold text-muted small mb-1">Total Recargas</div>
                                <div class="small text-muted mb-1"><?= (int) ($recStats['count'] ?? 0); ?> operaciones</div>
                                <div class="small pt-1 border-top text-info fw-bold">Comisión: +<?= esc($currency); ?> <?= number_format($pvRecEarned, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="card border-0 shadow-sm p-2 h-100" style="border-radius: 10px; border-left: 3px solid #dc3545 !important;">
                                <div class="text-uppercase fw-bold text-muted small mb-1">Total Retiros</div>
                                <div class="small text-muted mb-1"><?= (int) ($withStats['count'] ?? 0); ?> operaciones</div>
                                <div class="small pt-1 border-top text-danger fw-bold">Comisión: +<?= esc($currency); ?> <?= number_format($pvWithEarned, 2); ?></div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="card border-0 shadow-sm p-2 h-100 bg-light" style="border-radius: 10px; border-left: 3px solid #6236ff !important;">
                                <div class="text-uppercase fw-bold text-muted small mb-1">Total Comisiones</div>
                                <div class="pt-1">
                                    <strong class="text-primary" style="font-size: 1.1rem;"><?= esc($currency); ?> <?= number_format($pvTotalEarned, 2); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- TABLA DE COMISIONES DETALLADAS PUNTO DE VENTA -->
                    <div class="scroll-pane" style="max-height: 420px;">
                        <table class="table table-sm table-striped align-middle mb-0" style="font-size: 0.82rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha y Hora</th>
                                    <th>Tipo de Tasa</th>
                                    <th class="text-end">Total apostado</th>
                                    <th class="text-end">Total premios</th>
                                    <th class="text-end">Monto Base / GGR</th>
                                    <th class="text-center">Tasa (%)</th>
                                    <th class="text-end text-success fw-bold">Comisión Ganada</th>
                                    <th>Jugador / Beneficiario</th>
                                    <th>Cédula / Doc.</th>
                                    <th>Código / Ref.</th>
                                    <th class="text-center">Estado</th>
                                    <th>Detalle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (! empty($cItems)) : ?>
                                    <?php foreach ($cItems as $it) : ?>
                                        <?php
                                        $badgeClass = $it['badge_class'] ?? 'bg-secondary text-white';
                                        $icon = $it['icon'] ?? 'fa-duotone fa-solid fa-percent';
                                        $ratePct = ((float) ($it['store_rate'] ?? 0)) * 100;
                                        $isGgr = (string) ($it['rate_type'] ?? '') === 'ggr';
                                        $totalStake = $isGgr ? (float) ($it['total_stake'] ?? 0) : null;
                                        $totalPayout = $isGgr ? (float) ($it['total_payout'] ?? 0) : null;
                                        ?>
                                        <tr>
                                            <td class="text-nowrap"><?= esc($it['datetime'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge <?= esc($badgeClass); ?>">
                                                    <i class="<?= esc($icon); ?> me-1"></i> <?= esc($it['rate_type_label'] ?? $it['rate_type']); ?>
                                                </span>
                                            </td>
                                            <td class="text-end text-muted">
                                                <?= $totalStake !== null ? esc($currency) . ' ' . number_format($totalStake, 2) : '-'; ?>
                                            </td>
                                            <td class="text-end text-muted">
                                                <?= $totalPayout !== null ? esc($currency) . ' ' . number_format($totalPayout, 2) : '-'; ?>
                                            </td>
                                            <td class="text-end fw-semibold <?= ((float) ($it['base_amount'] ?? 0)) < 0 ? 'text-danger' : ''; ?>"><?= esc($currency); ?> <?= number_format((float) ($it['base_amount'] ?? 0), 2); ?></td>
                                            <td class="text-center"><span class="badge bg-light text-dark border"><?= number_format($ratePct, 2); ?>%</span></td>
                                            <td class="text-end fw-bold <?= ((float) ($it['commission_amount'] ?? 0)) < 0 ? 'text-danger' : 'text-success'; ?>" style="font-size: 0.90rem;">
                                                <?php $commAmt = (float) ($it['commission_amount'] ?? 0); ?>
                                                <?= ($commAmt < 0 ? '' : '+') . esc($currency); ?> <?= bingo_format_exact_amount($commAmt); ?>
                                            </td>
                                            <td>
                                                <strong><?= esc($it['player_name'] ?? 'Jugador'); ?></strong>
                                                <?php if (! empty($it['player_username'])) : ?>
                                                    <small class="text-muted d-block">@<?= esc($it['player_username']); ?></small>
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
                                            <td class="text-center"><span class="badge bg-success"><?= esc($it['status_label'] ?? 'Completado'); ?></span></td>
                                            <td><small class="text-muted"><?= esc($it['detail'] ?? '-'); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr><td colspan="12" class="text-center text-muted py-4">No se registran transacciones de comisiones para este punto de venta.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (! $isOperator) : ?>
<div class="tab-pane fade" id="ud-deposits">
            <div class="scroll-pane">
                <table class="table table-sm table-striped mb-0">
                    <thead><tr>
                        <th>ID</th><th><?= translate('date'); ?></th><th><?= translate('amount'); ?></th>
                        <th><?= translate('reference'); ?></th><th><?= translate('method'); ?></th><th><?= translate('status'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php if (empty($deposits)) : ?>
                        <tr><td colspan="6" class="text-center text-muted"><?= translate('no records found'); ?></td></tr>
                    <?php else : foreach ($deposits as $row) : ?>
                        <tr>
                            <td><?= (int) $row['id']; ?></td>
                            <td><?= esc($row['date'] ?? ''); ?></td>
                            <td><?= esc($currency); ?> <?= number_format((float) $row['amount'], 2); ?></td>
                            <td><?= esc($row['reference'] ?? ''); ?></td>
                            <td><?= esc($row['method'] ?? ($row['bank'] ?? '')); ?></td>
                            <td><?= esc($statusDeposit($row['status'] ?? 0)); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        
<?php endif; ?>
<?php if (! $isOperator) : ?>
<div class="tab-pane fade" id="ud-retires">
            <div class="scroll-pane">
                <table class="table table-sm table-striped mb-0">
                    <thead><tr>
                        <th>ID</th><th><?= translate('date'); ?></th><th><?= translate('amount'); ?></th>
                        <th><?= translate('bank'); ?></th><th><?= translate('account'); ?></th><th><?= translate('status'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php if (empty($retires)) : ?>
                        <tr><td colspan="6" class="text-center text-muted"><?= translate('no records found'); ?></td></tr>
                    <?php else : foreach ($retires as $row) : ?>
                        <tr>
                            <td><?= (int) $row['id']; ?></td>
                            <td><?= esc($row['created_at'] ?? ''); ?></td>
                            <td><?= esc($currency); ?> <?= number_format((float) $row['amount'], 2); ?></td>
                            <td><?= esc($row['bank'] ?? ''); ?></td>
                            <td><?= esc($row['account'] ?? ''); ?></td>
                            <td><?= esc($statusDeposit($row['status'] ?? 0)); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        
<?php endif; ?>
<?php if (! $isOperator) : ?>
<div class="tab-pane fade" id="ud-prizes">
            <div class="scroll-pane">
                <table class="table table-sm table-striped mb-0">
                    <thead><tr>
                        <th>ID</th><th><?= translate('date'); ?></th><th><?= translate('game'); ?></th>
                        <th><?= translate('modality'); ?></th><th><?= translate('carton'); ?></th><th><?= translate('amount'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php if (empty($prizes)) : ?>
                        <tr><td colspan="6" class="text-center text-muted"><?= translate('no records found'); ?></td></tr>
                    <?php else : foreach ($prizes as $row) : ?>
                        <tr>
                            <td><?= (int) $row['id']; ?></td>
                            <td><?= esc($row['created_at'] ?? ''); ?></td>
                            <td><?= esc($row['game'] ?? ''); ?></td>
                            <td><?= esc($row['modality'] ?? ''); ?></td>
                            <td><?= esc($row['carton'] ?? ''); ?></td>
                            <td><?= esc($currency); ?> <?= number_format((float) $row['amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        
<?php endif; ?>
<?php if (! $isOperator) : ?>
<div class="tab-pane fade" id="ud-purchases">
            <div class="scroll-pane">
                <table class="table table-sm table-striped mb-0 ud-purchases-table">
                    <thead><tr>
                        <th><?= translate('date'); ?></th>
                        <th><?= translate('game'); ?></th>
                        <th><?= translate('carton'); ?></th>
                        <th class="col-result">Resultado</th>
                        <th><?= translate('prize'); ?> / premio</th>
                        <th>Acreditado a</th>
                        <th><?= translate('amount'); ?></th>
                        <th><?= translate('source'); ?></th>
                        <th><?= translate('bonus'); ?></th>
                        <th><?= translate('recharge'); ?></th>
                        <th><?= translate('withdraw'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php if (empty($purchases)) : ?>
                        <tr><td colspan="11" class="text-center text-muted"><?= translate('no records found'); ?></td></tr>
                    <?php else : foreach ($purchases as $row) :
                        $resultKey = (string) ($row['result'] ?? '');
                        $resultLabel = (string) ($row['result_label'] ?? '');
                        if ($resultLabel === '' || $resultLabel === '—') {
                            $resultLabel = match ($resultKey) {
                                'won' => 'Ganó',
                                'lost' => 'Perdió',
                                'pending' => 'En juego',
                                default => '—',
                            };
                        }
                        $resultBadgeClass = match ($resultKey) {
                            'won' => 'ud-result-won',
                            'lost' => 'ud-result-lost',
                            'pending' => 'ud-result-pending',
                            default => 'bg-secondary',
                        };
                        $src = (string) ($row['source'] ?? 'wallet');
                        $srcLabel = $row['source_label'] ?? $sourceLabel($src);
                        ?>
                        <tr>
                            <td><?= esc($row['created_at'] ?? ''); ?></td>
                            <td><?= esc($row['game'] ?? ''); ?></td>
                            <td>
                                <?php if (! empty($row['serial']) && $row['serial'] !== '—') : ?>
                                    <code><?= esc($row['serial']); ?></code>
                                <?php else : ?>
                                    <?= (int) ($row['cartons_count'] ?? 0); ?> <?= translate('cartons'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="col-result">
                                <span class="badge <?= esc($resultBadgeClass); ?>">
                                    <?= esc($resultLabel); ?>
                                </span>
                                <?php if (! empty($row['modality'])) : ?>
                                    <div class="small text-muted"><?= esc($row['modality']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($resultKey === 'won') : ?>
                                    <?= esc($currency); ?> <?= number_format((float) ($row['prize_amount'] ?? 0), 2); ?>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?= esc($row['credit_label'] ?? '—'); ?></td>
                            <td><?= esc($currency); ?> <?= number_format((float) ($row['amount'] ?? 0), 2); ?></td>
                            <td>
                                <?php if ($src === 'bonus') : ?>
                                    <span class="badge bg-info text-dark"><?= esc($srcLabel); ?></span>
                                <?php elseif ($src === 'roulette') : ?>
                                    <span class="badge bg-primary"><?= esc($srcLabel); ?></span>
                                <?php elseif ($src === 'mixed') : ?>
                                    <span class="badge bg-warning text-dark"><?= esc($srcLabel); ?></span>
                                <?php elseif ($src === 'withdraw') : ?>
                                    <span class="badge bg-success"><?= esc($srcLabel); ?></span>
                                <?php elseif (in_array($src, ['recharge', 'real'], true)) : ?>
                                    <span class="badge bg-secondary"><?= esc($srcLabel); ?></span>
                                <?php else : ?>
                                    <?= esc($srcLabel); ?>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format((float) ($row['from_bonus'] ?? 0), 2); ?></td>
                            <td><?= number_format((float) ($row['from_recharge'] ?? 0), 2); ?></td>
                            <td><?= number_format((float) ($row['from_withdraw'] ?? 0), 2); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mt-2 mb-0">
                <strong>Resultado</strong>: Ganó / Perdió / En juego por cada cartón.
                Origen: Saldo Bono, Saldo Recarga o Saldo Retiro.
                <strong>Mixed</strong> solo aplica a Recarga + Retiro (nunca incluye bono).
            </p>
        </div>

        
<?php endif; ?>
<?php if (! $isOperator) : ?>
<div class="tab-pane fade" id="ud-roulette">
            <div class="scroll-pane">
                <table class="table table-sm table-striped mb-0">
                    <thead><tr>
                        <th>ID</th><th><?= translate('date'); ?></th><th><?= translate('cartons'); ?></th>
                        <th><?= translate('amount'); ?></th><th><?= translate('status'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php if (empty($roulettes)) : ?>
                        <tr><td colspan="5" class="text-center text-muted"><?= translate('no records found'); ?></td></tr>
                    <?php else : foreach ($roulettes as $row) : ?>
                        <tr>
                            <td><?= (int) $row['id']; ?></td>
                            <td><?= esc($row['created_at'] ?? ''); ?></td>
                            <td><?= (int) ($row['cartons'] ?? 0); ?></td>
                            <td><?= esc($currency); ?> <?= number_format((float) ($row['amount'] ?? 0), 2); ?></td>
                            <td><?= ((int) ($row['status'] ?? 0) === 1) ? translate('used') : translate('pending'); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        
<?php endif; ?>
<div class="tab-pane fade" id="ud-access">
            <div class="scroll-pane">
                <table class="table table-sm table-striped mb-0">
                    <thead><tr>
                        <th><?= translate('date'); ?></th><th><?= translate('action'); ?></th>
                        <th>IP</th><th><?= translate('country'); ?></th><th>User-Agent</th>
                    </tr></thead>
                    <tbody>
                    <?php if (empty($loginLogs)) : ?>
                        <tr><td colspan="5" class="text-center text-muted"><?= translate('no records found'); ?></td></tr>
                    <?php else : foreach ($loginLogs as $row) : ?>
                        <tr>
                            <td><?= esc($row['created_at'] ?? ''); ?></td>
                            <td><?= esc($row['action'] ?? ''); ?></td>
                            <td><code><?= esc($row['ip_address'] ?? ''); ?></code></td>
                            <td><?= esc($row['country'] ?? ''); ?></td>
                            <td class="small"><?= esc(mb_substr((string) ($row['user_agent'] ?? ''), 0, 80)); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="ud-kyc">
            <p>
                <strong><?= translate('status'); ?>:</strong>
                <span class="badge bg-<?= esc($kycClass); ?>"><?= esc($kycLabels[$kycStatus] ?? $kycStatus); ?></span>
            </p>
            <?php if (! empty($user['kyc_observations'])) : ?>
                <p><strong><?= translate('observations'); ?>:</strong> <?= esc($user['kyc_observations']); ?></p>
            <?php endif; ?>
            <div class="row g-2">
                <?php foreach (['kyc_front' => translate('front'), 'kyc_back' => translate('back'), 'kyc_selfie' => 'Selfie'] as $field => $label) : ?>
                    <div class="col-4 text-center">
                        <div class="small mb-1"><?= esc($label); ?></div>
                        <?php if (! empty($user[$field])) : ?>
                            <a href="<?= bingo_kyc_image_url($user[$field]); ?>" target="_blank" rel="noopener">
                                <img src="<?= bingo_kyc_image_url($user[$field]); ?>" alt="<?= esc($label); ?>" class="img-fluid rounded border" style="max-height:120px;object-fit:cover;">
                            </a>
                        <?php else : ?>
                            <div class="text-muted small"><?= translate('not provided'); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?= view('partials/modal_commission_liquidation'); ?>

