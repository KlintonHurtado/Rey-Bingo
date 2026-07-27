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
    return match ((string) $source) {
        'roulette' => translate('roulette cartons'),
        'bonus' => translate('bonus balance'),
        'wallet_legacy' => translate('wallet historical'),
        default => translate('real money wallet'),
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
.user-details-admin .scroll-pane { max-height: 280px; overflow:auto; }
.user-details-admin .alert-doc { font-size: .9rem; }
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
                <div class="col-6 col-md-3">
                    <div class="stat-chip">
                        <small><?= translate('wallet'); ?></small>
                        <strong><?= esc($currency); ?> <?= number_format((float) ($stats['wallet_total'] ?? 0), 2); ?></strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-chip" style="background:rgba(25,135,84,.12);">
                        <small><?= translate('bonus balance'); ?></small>
                        <strong><?= esc($currency); ?> <?= number_format((float) ($stats['wallet_bonus'] ?? 0), 2); ?></strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-chip">
                        <small><?= translate('recharge balance'); ?></small>
                        <strong><?= esc($currency); ?> <?= number_format((float) ($stats['wallet_recharge'] ?? 0), 2); ?></strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-chip">
                        <small><?= translate('withdraw balance'); ?></small>
                        <strong><?= esc($currency); ?> <?= number_format((float) ($stats['wallet_withdraw'] ?? 0), 2); ?></strong>
                    </div>
                </div>
            </div>
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
                <button type="button" class="btn btn-sm btn-success" onclick="grantBonusGet(<?= (int) $user['id']; ?>)">
                    <i class="fa-duotone fa-solid fa-gift"></i> <?= translate('grant bonus'); ?>
                </button>
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

    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#ud-deposits" type="button"><?= translate('deposits'); ?></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ud-retires" type="button"><?= translate('retires'); ?></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ud-prizes" type="button"><?= translate('prizes'); ?></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ud-purchases" type="button"><?= translate('carton purchases'); ?></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ud-roulette" type="button"><?= translate('roulette'); ?></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ud-access" type="button"><?= translate('access logs'); ?></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ud-kyc" type="button">KYC</button></li>
    </ul>

    <div class="tab-content border border-top-0 p-2 bg-white text-dark">
        <div class="tab-pane fade show active" id="ud-deposits">
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

        <div class="tab-pane fade" id="ud-purchases">
            <div class="scroll-pane">
                <table class="table table-sm table-striped mb-0">
                    <thead><tr>
                        <th><?= translate('date'); ?></th><th><?= translate('game'); ?></th><th><?= translate('cartons'); ?></th>
                        <th><?= translate('amount'); ?></th><th><?= translate('source'); ?></th>
                        <th><?= translate('bonus'); ?></th><th><?= translate('recharge'); ?></th><th><?= translate('withdraw'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php if (empty($purchases)) : ?>
                        <tr><td colspan="8" class="text-center text-muted"><?= translate('no records found'); ?></td></tr>
                    <?php else : foreach ($purchases as $row) : ?>
                        <tr>
                            <td><?= esc($row['created_at'] ?? ''); ?></td>
                            <td><?= esc($row['game'] ?? ''); ?></td>
                            <td><?= (int) ($row['cartons_count'] ?? 0); ?></td>
                            <td><?= esc($currency); ?> <?= number_format((float) ($row['amount'] ?? 0), 2); ?></td>
                            <td><?= esc($sourceLabel($row['source'] ?? 'wallet')); ?></td>
                            <td><?= number_format((float) ($row['from_bonus'] ?? 0), 2); ?></td>
                            <td><?= number_format((float) ($row['from_recharge'] ?? 0), 2); ?></td>
                            <td><?= number_format((float) ($row['from_withdraw'] ?? 0), 2); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

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
                            <a href="<?= site_url('uploads/kyc/' . $user[$field]); ?>" target="_blank" rel="noopener">
                                <img src="<?= site_url('uploads/kyc/' . $user[$field]); ?>" alt="<?= esc($label); ?>" class="img-fluid rounded border" style="max-height:120px;object-fit:cover;">
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
