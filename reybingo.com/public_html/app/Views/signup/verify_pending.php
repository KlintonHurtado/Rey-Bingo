<div class="container py-4">
    <div class="row d-flex justify-content-center">
        <div class="col-md-6 col-xl-5">
            <div class="text-center mb-3">
                <img src="<?= site_url('assets/img/logo_principal.png'); ?>?v=2" class="img-fluid logo mb-2" alt="Rey Bingo" style="max-height: 80px;">
                <h4 class="mb-1 text-white fw-bold"><?= translate('verify your email'); ?></h4>
                <p class="text-white-50 mb-0 small">
                    <?= translate('please verify your email before login'); ?>
                </p>
            </div>

            <?php if (session()->getFlashdata('success')) : ?>
                <div class="alert alert-success py-2 text-center"><?= esc(session()->getFlashdata('success')); ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')) : ?>
                <div class="alert alert-danger py-2 text-center"><?= esc(session()->getFlashdata('error')); ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-lg" style="background: rgba(25, 18, 52, 0.85); backdrop-filter: blur(10px); border-radius: 18px; border: 1px solid rgba(255, 255, 255, 0.12);">
                <div class="card-body p-4">
                    <div class="p-3 mb-3 text-center" style="background: rgba(255, 255, 255, 0.06); border-radius: 14px; border: 1px dashed rgba(255, 255, 255, 0.2);">
                        <i class="fas fa-paper-plane fa-2x mb-2 text-info"></i>
                        <h6 class="text-white fw-bold mb-1">
                            Te enviamos un correo de verificación.
                        </h6>
                        <p class="text-white-50 small mb-2">Revisa tu bandeja de entrada y la carpeta de spam.</p>
                        <p class="fw-bold text-white mb-0 fs-6" id="pending-email-label" style="word-break: break-all;">
                            <?= esc($email ?: '—'); ?>
                        </p>
                    </div>

                    <div class="mb-3">
                        <label for="resend_email" class="form-label text-white-50 small"><?= translate('email'); ?></label>
                        <input type="email" class="form-control form-control-lg form-bingo" id="resend_email"
                               value="<?= esc($email); ?>" placeholder="<?= translate('email'); ?>" autocomplete="email">
                        <small id="resend-error" class="text-danger d-none"></small>
                    </div>

                    <button type="button" class="btn btn-primary btn-bingo d-block w-100 py-3 mb-2 fw-bold" id="resend-verification-btn" disabled>
                        <i class="fas fa-redo-alt me-1"></i> <span id="resend-btn-text"><?= translate('resend verification email'); ?></span>
                    </button>
                    
                    <a href="<?= site_url('signin'); ?>" class="btn btn-outline-light d-block w-100 py-2">
                        <?= translate('login'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var cooldown = <?= (int) ($cooldownRemaining ?? 60); ?>;
    var timerInterval = null;
    var defaultBtnText = '<?= esc(translate('resend verification email'), 'js'); ?>';

    function setCooldownTimer(seconds) {
        cooldown = Math.max(0, parseInt(seconds, 10) || 0);
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }

        if (cooldown <= 0) {
            $('#resend-verification-btn').prop('disabled', false);
            $('#resend-btn-text').text(defaultBtnText);
            return;
        }

        $('#resend-verification-btn').prop('disabled', true);
        $('#resend-btn-text').text(defaultBtnText + ' (' + cooldown + 's)');

        timerInterval = setInterval(function () {
            cooldown--;
            if (cooldown <= 0) {
                clearInterval(timerInterval);
                timerInterval = null;
                $('#resend-verification-btn').prop('disabled', false);
                $('#resend-btn-text').text(defaultBtnText);
            } else {
                $('#resend-btn-text').text(defaultBtnText + ' (' + cooldown + 's)');
            }
        }, 1000);
    }

    // Inicializar cooldown en carga de página
    setCooldownTimer(cooldown);

    $('#resend-verification-btn').on('click', function () {
        var btn = $(this);
        if (btn.prop('disabled')) {
            return;
        }

        var email = ($('#resend_email').val() || '').trim();
        $('#resend-error').addClass('d-none').text('');
        if (!email) {
            $('#resend-error').text('<?= esc(translate('email') . ' ' . strtolower(translate('it is mandatory')), 'js'); ?>').removeClass('d-none');
            return;
        }

        btn.prop('disabled', true);
        $('#resend-btn-text').html('<i class="fas fa-spinner fa-spin me-1"></i> <?= esc(translate('sending'), 'js'); ?>...');

        $.ajax({
            url: '<?= site_url('signup/resendVerification'); ?>',
            method: 'POST',
            dataType: 'json',
            data: {
                email: email,
                <?= csrf_token(); ?>: '<?= csrf_hash(); ?>'
            }
        }).done(function (response) {
            var isSuccess = !!response.success;
            Toastify({
                text: response.message || 'OK',
                duration: 4000,
                gravity: 'top',
                position: 'right',
                style: { background: isSuccess ? '#198754' : '#dc3545' },
                stopOnFocus: true
            }).showToast();

            if (response.redirect) {
                setTimeout(function () {
                    window.location.href = response.redirect;
                }, 1000);
                return;
            }

            if (isSuccess) {
                // Iniciar cuenta regresiva de 60 segundos tras envío exitoso
                setCooldownTimer(response.cooldown || 60);
            } else if (response.rate_limited && response.remaining) {
                // Si el backend aplicó rate-limit, respetar los segundos restantes
                setCooldownTimer(response.remaining);
            } else {
                btn.prop('disabled', false);
                $('#resend-btn-text').text(defaultBtnText);
            }
        }).fail(function () {
            Toastify({
                text: '<?= esc(translate('there was an error in the request to the server'), 'js'); ?>',
                duration: 3500,
                gravity: 'top',
                position: 'right',
                style: { background: '#dc3545' },
                stopOnFocus: true
            }).showToast();
            btn.prop('disabled', false);
            $('#resend-btn-text').text(defaultBtnText);
        });
    });
})();
</script>
