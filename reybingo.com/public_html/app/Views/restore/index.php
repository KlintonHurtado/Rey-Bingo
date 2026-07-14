<div class="container">
    <div class="row d-flex justify-content-center">
        <div class="col-md-5 col-xl-5">
            <div class="row">
                <div class="col">
                    <div class="text-center">
                        <img src="<?= site_url('assets/img/logo_principal.png'); ?>?v=2" class="img-fluid logo" alt="img">
                        <h5 class="mb-0 p-2"><?= translate('forgot your password?'); ?></h5>
                    </div>

                    <?php if (!empty($token)): ?>
                        <?php echo form_open(site_url() . 'restore/changeSubmit', array('enctype' => 'multipart/form-data', 'id' => 'change-form'));?>
                    
                            <?= csrf_field() ?>

                            <input type="hidden" name="token" id="token" value="<?= $token ?>">

                            <div class="row">
                                <div class="col-md-12 mb-1">
                                    <label for="code" class="form-label"><?= translate('code'); ?></label>
                                    <input type="text" class="form-control form-control-lg form-bingo" name="code" id="code" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('code')); ?>" autofocus autocomplete="off">
                                    <small id="code-error" class="text-danger d-none"></small>
                                </div>

                                <div class="col-md-12 mb-1">
                                    <label for="password" class="form-label"><?= translate('new password'); ?></label>
                                    <input type="password" class="form-control form-control-lg form-bingo" name="password" id="password" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('new password')); ?>" autocomplete="off">
                                    <small id="password-error" class="text-danger d-none"></small>
                                </div>
                                
                                <div class="col-md-12 mb-1">
                                    <label for="password_confirm" class="form-label"><?= translate('password confirm'); ?></label>
                                    <input type="password" class="form-control form-control-lg form-bingo" name="password_confirm" id="password_confirm" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('password')); ?>" autocomplete="off">
                                    <small id="password_confirm-error" class="text-danger d-none"></small>
                                </div>

                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary d-block w-50 btn-bingo mt-3" id="change-button"><?= translate('change'); ?></button>
                                </div>
                            </div>
                        <?= form_close(); ?>
                    <?php else: ?>
                        <?php echo form_open(site_url() . 'restore/restoreSubmit', array('enctype' => 'multipart/form-data', 'id' => 'restore-form'));?>
                    
                            <?= csrf_field() ?>
                            
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="email" class="form-label"><?= translate('email'); ?></label>
                                    <input type="text" class="form-control form-control-lg form-bingo" name="email" id="email" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('email')); ?>" autocomplete="off">
                                    <small id="email-error" class="text-danger d-none"></small>
                                </div>

                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary d-block w-50 btn-bingo" id="restore-button"><?= translate('restore'); ?></button>
                                </div>
                            </div>
                        <?= form_close(); ?>
                    <?php endif; ?>
                    
                    <hr />

                    <div class="text-center">
                        <?= translate('do not reset password?'); ?> <br /> <a class="link-offset-2 link-offset-3-hover link-underline link-underline-opacity-0 link-underline-opacity-75-hover" href="<?= site_url('signin'); ?>"><?= translate('enter'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="whatsapp-plugin"></div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#restore-form').on('submit', function(e) {
            e.preventDefault();
    
            var button = $('#restore-button');
            button.prop("disabled", true);
    
            $('.text-danger').addClass('d-none').text('');
            $('.form-control').removeClass('is-invalid');
    
            $.ajax({
                url: '<?= site_url('restore/restoreSubmit') ?>',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Toastify({
                            text: response.message,
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            style: { background: "#198754" },
                            stopOnFocus: true
                        }).showToast();

                        setTimeout(function () {
                            window.location.href = response.redirect;
                        }, 500);
                    } else {
                        if (response.errors) {
                            $.each(response.errors, function(field, message) {
                                $('#' + field + '-error').text(message).removeClass('d-none');
                                $('#' + field).addClass('is-invalid');
                            });
                        }
                    }
                },
                error: function() {
                    Toastify({
                        text: "<?= translate('there was an error in the request to the server'); ?>",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: { background: "#dc3545" },
                        stopOnFocus: true
                    }).showToast();
                },
                complete: function() {
                    button.prop("disabled", false);
                }
            });
        });

        $('#change-form').on('submit', function(e) {
            e.preventDefault();
    
            var button = $('#change-button');
            button.prop("disabled", true);
    
            $('.text-danger').addClass('d-none').text('');
            $('.form-control').removeClass('is-invalid');
    
            $.ajax({
                url: '<?= site_url('restore/changeSubmit') ?>',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Toastify({
                            text: response.message,
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            style: { background: "#198754" },
                            stopOnFocus: true
                        }).showToast();

                        setTimeout(function () {
                            window.location.href = response.redirect;
                        }, 500);
                    } else {
                        if (response.errors) {
                            $.each(response.errors, function(field, message) {
                                $('#' + field + '-error').text(message).removeClass('d-none');
                                $('#' + field).addClass('is-invalid');
                            });
                        }
                    }
                },
                error: function() {
                    Toastify({
                        text: "<?= translate('there was an error in the request to the server'); ?>",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: { background: "#dc3545" },
                        stopOnFocus: true
                    }).showToast();
                },
                complete: function() {
                    button.prop("disabled", false);
                }
            });
        });
    });
</script>