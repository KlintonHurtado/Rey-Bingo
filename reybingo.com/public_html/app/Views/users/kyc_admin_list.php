<a class="btn btn-small btn-home" href="<?= site_url('dashboard'); ?>"><i class="fa-duotone fa-solid fa-house"></i></a>

<div class="container py-3">
    <h5 class="mb-3">Revisión KYC — solicitudes pendientes</h5>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success'); ?></div>
    <?php endif; ?>

    <?php if (empty($pending)): ?>
        <p class="text-muted">No hay solicitudes pendientes.</p>
    <?php else: ?>
        <?php foreach ($pending as $u): ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-body">
                    <h6><?= esc($u['firstname'] . ' ' . $u['lastname']); ?></h6>
                    <p class="small text-muted mb-2"><?= esc($u['email']); ?> · <?= esc($u['code']); ?></p>
                    <p class="mb-2">
                        <a href="<?= site_url('uploads/kyc/' . $u['kyc_front']); ?>" target="_blank" class="btn btn-sm btn-outline-primary me-1">Ver frente</a>
                        <a href="<?= site_url('uploads/kyc/' . $u['kyc_back']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">Ver reverso</a>
                    </p>
                    <form action="<?= site_url('kycAdmin/review/' . $u['id']); ?>" method="post">
                        <?= csrf_field(); ?>
                        <textarea name="kyc_observations" class="form-control form-control-sm mb-2" rows="2" placeholder="Observaciones para el usuario"></textarea>
                        <div class="d-flex gap-2">
                            <button type="submit" name="action" value="verified" class="btn btn-success btn-sm flex-fill">Aprobar</button>
                            <button type="submit" name="action" value="rejected" class="btn btn-danger btn-sm flex-fill">Rechazar</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
