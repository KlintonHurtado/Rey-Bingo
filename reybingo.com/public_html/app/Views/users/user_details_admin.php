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
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-success text-nowrap" onclick="adminAdjustNonPlayer(<?= (int) $user['id']; ?>, 'credit')">
                                <i class="fa-solid fa-circle-plus me-1"></i> Sumar Recarga (+)
                            </button>
                            <button type="button" class="btn btn-sm btn-danger text-nowrap" onclick="adminAdjustNonPlayer(<?= (int) $user['id']; ?>, 'debit')">
                                <i class="fa-solid fa-circle-minus me-1"></i> Quitar / Retirar Saldo (-)
                            </button>
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
                <tr><td><strong><?= translate('email'); ?></strong></td><td><?= esc($user['email'] ?? ''); ?></td></tr>
                <tr><td><strong><?= translate('phone'); ?></strong></td><td><?= esc($user['phone'] ?: translate('not provided')); ?></td></tr>
                <tr><td><strong><?= translate('document'); ?></strong></td><td><?= esc($user['document'] ?: translate('not provided')); ?></td></tr>
                <tr><td><strong>IP</strong></td><td><code><?= esc($ip !== '' ? $ip : translate('not provided')); ?></code></td></tr>
                <tr>
                    <td><strong><?= translate('mac address'); ?></strong></td>
                    <td><code><?= esc($mac !== '' ? $mac : translate('mac not available web')); ?></code></td>
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
                    Historial unificado: recargas, retiros, compras, resultados, premios, bonos y ruleta.
                    La columna <strong>Saldo Total</strong> muestra cómo queda el saldo después de cada movimiento.
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
                        <th>Monto</th>
                        <th>Saldo Total</th>
                        <th><?= translate('status'); ?></th>
                        <th><?= translate('game'); ?></th>
                        <th>Serie</th>
                        <th>Resultado</th>
                        <th>Origen</th>
                        <th>Detalle</th>
                    </tr></thead>
                    <tbody>
                    <?php if (empty($movements)) : ?>
                        <tr><td colspan="10" class="text-center text-muted"><?= translate('no records found'); ?></td></tr>
                    <?php else : foreach ($movements as $m) :
                        $dir = (string) ($m['direction'] ?? '');
                        $amt = (float) ($m['amount'] ?? 0);
                        $amtClass = $dir === '+' ? 'text-success' : ($dir === '-' ? 'text-danger' : 'text-muted');
                        $balanceAfter = array_key_exists('balance_after', $m) ? (float) $m['balance_after'] : null;
                        $balanceClass = $balanceAfter === null
                            ? 'text-muted'
                            : ($balanceAfter < 0 ? 'text-danger' : 'text-dark fw-semibold');
                        $type = (string) ($m['type'] ?? '');
                        $typeBadge = match ($type) {
                            'deposit' => 'bg-success',
                            'retire' => 'bg-danger',
                            'purchase' => 'bg-primary',
                            'prize' => 'bg-warning text-dark',
                            'bonus' => 'bg-info text-dark',
                            'roulette' => 'bg-secondary',
                            default => 'bg-dark',
                        };
                        $resultKey = (string) ($m['result'] ?? '');
                        $resultBadge = match ($resultKey) {
                            'won' => 'ud-result-won',
                            'lost' => 'ud-result-lost',
                            'pending' => 'ud-result-pending',
                            default => '',
                        };
                        ?>
                        <tr>
                            <td class="text-nowrap"><?= esc($m['datetime'] ?? ''); ?></td>
                            <td><span class="badge <?= esc($typeBadge); ?>"><?= esc($m['type_label'] ?? $type); ?></span></td>
                            <td class="<?= esc($amtClass); ?> text-nowrap">
                                <?= esc($dir); ?> <?= esc($currency); ?> <?= number_format($amt, 2); ?>
                            </td>
                            <td class="<?= esc($balanceClass); ?> text-nowrap">
                                <?php if ($balanceAfter === null) : ?>
                                    —
                                <?php else : ?>
                                    <?= esc($currency); ?> <?= number_format($balanceAfter, 2); ?>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($m['status_label'] ?? ''); ?></td>
                            <td><?= esc($m['game'] ?? '—'); ?></td>
                            <td>
                                <?php if (! empty($m['carton_serial'])) : ?>
                                    <code><?= esc($m['carton_serial']); ?></code>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($resultKey !== '') : ?>
                                    <span class="badge <?= esc($resultBadge !== '' ? $resultBadge : 'bg-secondary'); ?>">
                                        <?= esc($m['result_label'] ?? $resultKey); ?>
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
                                    <?php if ((float) ($m['from_bonus'] ?? 0) > 0 || (float) ($m['from_recharge'] ?? 0) > 0 || (float) ($m['from_withdraw'] ?? 0) > 0) : ?>
                                        <div class="small text-muted">
                                            A <?= number_format((float) ($m['from_bonus'] ?? 0), 2); ?>
                                            / R <?= number_format((float) ($m['from_recharge'] ?? 0), 2); ?>
                                            / Ret <?= number_format((float) ($m['from_withdraw'] ?? 0), 2); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
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
