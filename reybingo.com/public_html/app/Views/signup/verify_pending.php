<div class="container">
    <div class="row d-flex justify-content-center">
        <div class="col-md-6 col-xl-5">
            <div class="text-center mb-3">
                <img src="<?= site_url('assets/img/logo_principal.png'); ?>?v=2" class="img-fluid logo" alt="img">
                <h5 class="mb-1 p-2"><?= translate('verify your email'); ?></h5>
                <p class="text-white-50 mb-0">
                    <?= translate('please verify your email before login'); ?>
                </p>
            </div>

            <?php if (session()->getFlashdata('success')) : ?>
                <div class="alert alert-success py-2"><?= esc(session()->getFlashdata('success')); ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')) : ?>
                <div class="alert alert-danger py-2"><?= esc(session()->getFlashdata('error')); ?></div>
            <?php endif; ?>

            <div class="p-3 mb-3" style="background:rgba(255,255,255,0.08);border-radius:14px;">
                <p class="mb-2 text-white">
                    <?= translate('we sent a verification link to'); ?>:
                </p>
                <p class="fw-bold text-white mb-3" id="pending-email-label"><?= esc($email ?: '—'); ?></p>
                <p class="small text-white-50 mb-0">
                    <?= translate('check spam folder verification'); ?>
                </p>
            </div>

            <div class="mb-3">
                <label for="resend_email" class="form-label"><?= translate('email'); ?></label>
                <input type="email" class="form-control form-control-lg form-bingo" id="resend_email"
                       value="<?= esc($email); ?>" placeholder="<?= translate('email'); ?>">
                <small id="resend-error" class="text-danger d-none"></small>
            </div>

            <button type="button" class="btn btn-primary btn-bingo d-block w-100 mb-2" id="resend-verification-btn">
                <?= translate('resend verification email'); ?>
            </button>
            <a href="<?= site_url('signin'); ?>" class="btn btn-secondary d-block w-100">
                <?= translate('login'); ?>
            </a>
        </div>
    </div>
</div>

<script>
(function () {
    $('#resend-verification-btn').on('click', function () {
        var btn = $(this);
        var email = ($('#resend_email').val() || '').trim();
        $('#resend-error').addClass('d-none').text('');
        if (!email) {
            $('#resend-error').text('<?= translate('email'); ?>').removeClass('d-none');
            return;
        }
        btn.prop('disabled', true);
        $.ajax({
            url: '<?= site_url('signup/resendVerification'); ?>',
            method: 'POST',
            dataType: 'json',
            data: {
                email: email,
                <?= csrf_token(); ?>: '<?= csrf_hash(); ?>'
            }
        }).done(function (response) {
            Toastify({
                text: response.message || 'OK',
                duration: 3500,
                gravity: 'top',
                position: 'right',
                style: { background: response.success ? '#198754' : '#dc3545' },
                stopOnFocus: true
            }).showToast();
            if (response.redirect) {
                setTimeout(function () { window.location.href = response.redirect; }, 800);
            }
        }).fail(function () {
            Toastify({
                text: '<?= translate('there was an error in the request to the server'); ?>',
                duration: 3000,
                gravity: 'top',
                position: 'right',
                style: { background: '#dc3545' },
                stopOnFocus: true
            }).showToast();
        }).always(function () {
            btn.prop('disabled', false);
        });
    });
})();
</script>
