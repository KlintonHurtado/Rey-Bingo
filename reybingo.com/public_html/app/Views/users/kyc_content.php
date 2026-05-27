<?php
$kycStatus = $user['kyc_status'] ?? 'pending';
$kycLabels = [
    'pending'  => ['Pendiente', 'warning'],
    'verified' => ['Verificado', 'success'],
    'rejected' => ['Rechazado', 'danger'],
];
[$kycLabel, $kycClass] = $kycLabels[$kycStatus] ?? ['Pendiente', 'secondary'];
?>
<div class="card border-0 shadow-sm mt-3">
    <div class="card-body">
        <h6 class="mb-2"><i class="fa-duotone fa-solid fa-id-card me-2"></i> Verificación KYC</h6>
        <p class="text-muted small mb-2">Necesaria para solicitar retiros de fondos.</p>
        <p class="mb-2">Estado: <span class="badge bg-<?= $kycClass; ?>"><?= $kycLabel; ?></span></p>
        <?php if (! empty($user['kyc_observations'])): ?>
            <p class="small text-muted mb-2"><strong>Observaciones:</strong> <?= esc($user['kyc_observations']); ?></p>
        <?php endif; ?>
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success py-2 small"><?= session()->getFlashdata('success'); ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger py-2 small"><?= session()->getFlashdata('error'); ?></div>
        <?php endif; ?>
        <?php if ($kycStatus !== 'verified'): ?>
            <?= form_open(site_url('kyc/submit'), ['enctype' => 'multipart/form-data']); ?>
                <?= csrf_field(); ?>
                <div class="mb-2">
                    <label class="form-label small">Documento (frente)</label>
                    <input type="file" name="kyc_front" class="form-control form-control-sm" accept="image/*" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Documento (reverso)</label>
                    <input type="file" name="kyc_back" class="form-control form-control-sm" accept="image/*" required>
                </div>
                <button type="submit" class="btn btn-primary btn-bingo btn-sm">Enviar documentos</button>
            <?= form_close(); ?>
        <?php else: ?>
            <p class="small text-success mb-0"><i class="fa-duotone fa-solid fa-circle-check me-1"></i> Tu cuenta está verificada.</p>
        <?php endif; ?>
    </div>
</div>
