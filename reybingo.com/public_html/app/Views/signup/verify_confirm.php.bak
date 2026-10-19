<div class="container py-4">
    <div class="row d-flex justify-content-center">
        <div class="col-md-6 col-xl-5">
            <div class="text-center mb-3">
                <img src="<?= site_url('assets/img/logo_principal.png'); ?>?v=2" class="img-fluid logo mb-2" alt="Rey Bingo" style="max-height: 80px;">
                <h4 class="mb-1 text-white fw-bold"><?= translate('verify your email'); ?></h4>
            </div>

            <?php if (session()->getFlashdata('success')) : ?>
                <div class="alert alert-success py-2 text-center"><?= esc(session()->getFlashdata('success')); ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')) : ?>
                <div class="alert alert-danger py-2 text-center"><?= esc(session()->getFlashdata('error')); ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-lg" style="background: rgba(25, 18, 52, 0.85); backdrop-filter: blur(10px); border-radius: 18px; border: 1px solid rgba(255, 255, 255, 0.12);">
                <div class="card-body p-4 text-center">

                    <?php if (($state ?? '') === 'already_verified') : ?>
                        <!-- ESTADO: CUENTA YA VERIFICADA -->
                        <div class="mb-3">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 72px; height: 72px; background: rgba(25, 135, 84, 0.2); border: 2px solid #198754;">
                                <i class="fas fa-check-double text-success fa-2x"></i>
                            </span>
                        </div>
                        <h5 class="text-white fw-bold mb-2">¡Correo ya verificado!</h5>
                        <p class="text-white-50 mb-4">
                            Tu correo ya está verificado. Puedes iniciar sesión y continuar jugando.
                        </p>
                        <a href="<?= site_url('play'); ?>" class="btn btn-primary btn-bingo d-block w-100 py-3 mb-2 fw-bold text-uppercase">
                            <i class="fas fa-play me-2"></i> Ir a Jugar
                        </a>
                        <a href="<?= site_url('signin'); ?>" class="btn btn-outline-light d-block w-100 py-2">
                            <?= translate('login'); ?>
                        </a>

                    <?php elseif (($state ?? '') === 'invalid') : ?>
                        <!-- ESTADO: ENLACE INVÁLIDO O EXPIRADO -->
                        <div class="mb-3">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 72px; height: 72px; background: rgba(220, 53, 69, 0.2); border: 2px solid #dc3545;">
                                <i class="fas fa-exclamation-triangle text-danger fa-2x"></i>
                            </span>
                        </div>
                        <h5 class="text-white fw-bold mb-2"><?= translate('invalid or expired verification link'); ?></h5>
                        <p class="text-white-50 mb-4 small">
                            Este enlace ya no es válido o ha caducado. Si tu cuenta aún no está activa, puedes solicitar un nuevo correo de verificación sin tener que registrarte de nuevo.
                        </p>
                        <a href="<?= site_url('signup/verifyPending' . (!empty($user['email']) ? ('?email=' . rawurlencode($user['email'])) : '')); ?>" class="btn btn-primary btn-bingo d-block w-100 py-3 mb-2 fw-bold">
                            <i class="fas fa-paper-plane me-2"></i> <?= translate('resend verification email'); ?>
                        </a>
                        <a href="<?= site_url('signin'); ?>" class="btn btn-outline-light d-block w-100 py-2">
                            <?= translate('login'); ?>
                        </a>

                    <?php else : ?>
                        <!-- ESTADO: CONFIRMACIÓN REQUERIDA (GET) -->
                        <div class="mb-3">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 72px; height: 72px; background: rgba(98, 54, 255, 0.2); border: 2px solid #6236ff;">
                                <i class="fas fa-envelope-open-text text-white fa-2x"></i>
                            </span>
                        </div>

                        <?php if (!empty($user['firstname'])) : ?>
                            <h5 class="text-white fw-bold mb-1">
                                <?= translate('hello'); ?>, <?= esc($user['firstname']); ?>!
                            </h5>
                        <?php endif; ?>

                        <p class="text-white-50 mb-3 small">
                            Para activar tu cuenta de Rey Bingo y comenzar a jugar, haz clic en el siguiente botón:
                        </p>

                        <?php if (!empty($user['email'])) : ?>
                            <div class="p-2 mb-3" style="background: rgba(255, 255, 255, 0.06); border-radius: 10px;">
                                <small class="text-white-50 d-block"><?= translate('email'); ?>:</small>
                                <span class="text-white fw-bold"><?= esc($user['email']); ?></span>
                            </div>
                        <?php endif; ?>

                        <form action="<?= site_url('verify/' . rawurlencode((string) $token)); ?>" method="POST" id="verify-confirm-form">
                            <?= csrf_field(); ?>
                            <button type="submit" class="btn btn-primary btn-bingo d-block w-100 py-3 mb-2 fw-bold fs-6" id="btn-confirm-submit">
                                <i class="fas fa-check-circle me-2"></i> Confirmar mi correo electrónico
                            </button>
                        </form>

                        <a href="<?= site_url('signin'); ?>" class="btn btn-link text-white-50 text-decoration-none small mt-2">
                            <?= translate('login'); ?>
                        </a>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    $('#verify-confirm-form').on('submit', function () {
        var btn = $('#btn-confirm-submit');
        if (btn.prop('disabled')) {
            return false;
        }
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Verificando...');
        return true;
    });
})();
</script>
