<?php
$currency = $currency ?? systemGet('currency');
$isOperator = (int) ($user['group'] ?? 0) === bingo_group_operator();
$isOperatorRole = $isOperator || (function_exists('bingo_is_operator') && bingo_is_operator());
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
    <?php if (! $isOperatorRole) : ?>
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
                    <div class="col-6 col-md-3">
                        <div class="stat-chip">
                            <small><?= translate('wallet'); ?></small>
                            <strong id="admin-wallet-total"><?= esc($currency); ?> <?= number_format((float) ($stats['wallet_total'] ?? 0), 2); ?></strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-chip" style="background:rgba(25,135,84,.12);">
                            <small><?= translate('bonus balance'); ?></small>
                            <?php if ($isOperator) : ?>
                                <strong class="d-block mt-1"><?= esc($currency); ?> <?= number_format((float) ($stats['wallet_bonus'] ?? 0), 2); ?></strong>
                            <?php else : ?>
                                <div class="input-group input-group-sm mt-1">
                                    <span class="input-group-text"><?= esc($currency); ?></span>
                                    <input type="number" step="0.01" min="0" class="form-control" id="admin-wallet-bonus"
                                           value="<?= number_format((float) ($stats['wallet_bonus'] ?? 0), 2, '.', ''); ?>">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-chip">
                            <small><?= translate('recharge balance'); ?></small>
                            <?php if ($isOperator) : ?>
                                <strong class="d-block mt-1"><?= esc($currency); ?> <?= number_format((float) ($stats['wallet_recharge'] ?? 0), 2); ?></strong>
                            <?php else : ?>
                                <div class="input-group input-group-sm mt-1">
                                    <span class="input-group-text"><?= esc($currency); ?></span>
                                    <input type="number" step="0.01" min="0" class="form-control" id="admin-wallet-recharge"
                                           value="<?= number_format((float) ($stats['wallet_recharge'] ?? 0), 2, '.', ''); ?>">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-chip">
                            <small><?= translate('withdraw balance'); ?></small>
                            <?php if ($isOperator) : ?>
                                <strong class="d-block mt-1"><?= esc($currency); ?> <?= number_format((float) ($stats['wallet_withdraw'] ?? 0), 2); ?></strong>
                            <?php else : ?>
                                <div class="input-group input-group-sm mt-1">
                                    <span class="input-group-text"><?= esc($currency); ?></span>
                                    <input type="number" step="0.01" min="0" class="form-control" id="admin-wallet-withdraw"
                                           value="<?= number_format((float) ($stats['wallet_withdraw'] ?? 0), 2, '.', ''); ?>">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if (! $isOperator) : ?>
                    <div class="mb-2">
                        <button type="button" class="btn btn-sm btn-primary" onclick="savePlayerWallets(<?= (int) $user['id']; ?>)">
                            <i class="fa-duotone fa-solid fa-floppy-disk"></i> <?= translate('save wallets'); ?>
                        </button>
                        <small class="text-muted ms-2"><?= translate('edit wallets help'); ?></small>
                    </div>
                <?php endif; ?>
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
                    <?php if (! $isOperator) : ?>
                        <button type="button" class="btn btn-sm btn-success" onclick="grantBonusGet(<?= (int) $user['id']; ?>)">
                            <i class="fa-duotone fa-solid fa-gift"></i> <?= translate('grant bonus'); ?>
                        </button>
                    <?php endif; ?>
                    <a class="btn btn-sm btn-primary" href="<?= site_url('users/exportUserMovements/' . (int) $user['id']); ?>">
                        <i class="fa-duotone fa-solid fa-file-excel"></i> Descargar movimientos
                    </a>
                    <?php if (! $isOperator) : ?>
                        <a class="btn btn-sm btn-warning" href="<?= site_url('users/exportRiskAnalysis/' . (int) $user['id']); ?>">
                            <i class="fa-duotone fa-solid fa-file-arrow-down"></i> <?= translate('download risk analysis'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if (! $isOperatorRole && ($kycStatus ?? '') === 'verified') : ?>
                        <button type="button" class="btn btn-sm btn-danger" onclick="revokeUserKyc(<?= (int) $user['id']; ?>)">
                            <i class="fa-duotone fa-solid fa-user-shield"></i> <?= translate('remove kyc verification'); ?>
                        </button>
                    <?php endif; ?>
                    <?php if (! $isOperatorRole) : ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('kycAdmin'); ?>" target="_blank" rel="noopener">
                            <?= translate('kyc admin'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#ud-movements" type="button">Movimientos</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ud-access" type="button"><?= translate('access logs'); ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ud-kyc" type="button">KYC</button></li>
        </ul>
    <?php endif; ?>

    <div class="tab-content border <?= ! $isOperatorRole ? 'border-top-0' : ''; ?> p-2 bg-white text-dark">
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

        <?php if (! $isOperatorRole) : ?>
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
        <?php endif; ?>
    </div>
</div>
