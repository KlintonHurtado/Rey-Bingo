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
        <p class="text-muted small mb-2">Solo es obligatoria para <strong>retirar fondos</strong>. Para depositar o jugar no necesitas completarla. Debes subir 3 fotos: frente, reverso y una selfie sosteniendo el documento en la barbilla.</p>
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
                    <div class="d-flex gap-2 align-items-center">
                        <input type="file" id="kyc_front" name="kyc_front" class="form-control form-control-sm" accept="image/*" required>
                        <button type="button" id="clear-kyc-front" class="btn btn-sm btn-outline-secondary d-none">Quitar</button>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Documento (reverso)</label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="file" id="kyc_back" name="kyc_back" class="form-control form-control-sm" accept="image/*" required>
                        <button type="button" id="clear-kyc-back" class="btn btn-sm btn-outline-secondary d-none">Quitar</button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Selfie con documento en la barbilla</label>
                    <p class="text-muted small mb-1">Toma una foto de tu rostro sosteniendo el documento junto a la barbilla, con buena iluminación.</p>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="file" id="kyc_selfie" name="kyc_selfie" class="form-control form-control-sm" accept="image/*" capture="user" required>
                        <button type="button" id="clear-kyc-selfie" class="btn btn-sm btn-outline-secondary d-none">Quitar</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-bingo btn-sm">Enviar documentos</button>
            <?= form_close(); ?>
            <script>
                (function() {
                    function setupClearButton(inputId, buttonId) {
                        var input = document.getElementById(inputId);
                        var button = document.getElementById(buttonId);
                        if (!input || !button) {
                            return;
                        }

                        input.addEventListener('change', function() {
                            button.classList.toggle('d-none', !input.files || input.files.length === 0);
                        });

                        button.addEventListener('click', function() {
                            input.value = '';
                            button.classList.add('d-none');
                        });
                    }

                    setupClearButton('kyc_front', 'clear-kyc-front');
                    setupClearButton('kyc_back', 'clear-kyc-back');
                    setupClearButton('kyc_selfie', 'clear-kyc-selfie');
                })();
            </script>
        <?php else: ?>
            <p class="small text-success mb-0"><i class="fa-duotone fa-solid fa-circle-check me-1"></i> Tu cuenta está verificada.</p>
        <?php endif; ?>
    </div>
</div>
