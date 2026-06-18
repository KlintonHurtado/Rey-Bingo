<div class="container">
    <div class="row d-flex justify-content-center">
        <div class="col-md-5 col-xl-5">
            <div class="row">
                <div class="col">
                    <div class="text-center">
                        <img src="<?= site_url('assets/img/logo.png'); ?>" class="img-fluid logo" alt="img">
                        <h5 class="mb-0 p-2"><?= translate('login'); ?></h5>
                        <p class="small text-muted mb-0"><?= translate('official client access'); ?>: <strong><?= esc(bingo_official_login_host()); ?></strong></p>
                        <?php if (bingo_client_domain_enabled()): ?>
                        <p class="small text-muted mb-0"><?= translate('client login url'); ?>: <strong><?= esc(bingo_client_login_url('/signin')); ?></strong></p>
                        <?php endif; ?>
                    </div>
            
                    <?php echo form_open(site_url() . 'signin/signinSubmit', array('enctype' => 'multipart/form-data', 'id' => 'signin-form'));?>
                    
                        <?= csrf_field() ?>
                        
                        <div class="row">
                            <div class="col-md-12 mb-1">
                                <label for="username" class="form-label"><?= translate('username'); ?></label>
                                <input type="text" class="form-control form-control-lg form-bingo" name="username" id="username" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('username')); ?>" autofocus autocomplete="off">
                                <small id="username-error" class="text-danger d-none"></small>
                            </div>
                            
                            <div class="col-md-12 mb-1">
                                <label for="password" class="form-label"><?= translate('password'); ?></label>
                                <input type="password" class="form-control form-control-lg form-bingo" name="password" id="password" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('password')); ?>" autocomplete="off">
                                <small id="password-error" class="text-danger d-none"></small>
                            </div>

                            <div class="col-md-12 mb-2 px-4">
                                <div class="form-check float-start">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" checked>
                                    <label for="remember" class="form-check-label"><?= translate('remember'); ?></label>
                                </div>
                                <a class="link-offset-2 link-offset-3-hover link-underline link-underline-opacity-0 link-underline-opacity-75-hover float-end" href="<?= site_url('restore'); ?>"><?= translate('forgot your password?'); ?></a>
                            </div>

                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary d-block w-50 btn-bingo" id="signin-button"><?= translate('enter'); ?></button>
                            </div>

                            <div class="col-md-12 pt-3">
                                <a href="<?= site_url('signup/google') ?>" class="btn btn-primary d-block google"><img src="https://developers.google.com/identity/images/g-logo.png" style="width:20px; margin-right:10px;"> <?= translate('signin with google'); ?></a>
                            </div>
                        </div>
                    <?= form_close(); ?>
                    
                    <hr />

                    <div class="text-center">
                        <?= translate('dont have an account yet?'); ?> <br /> <a class="link-offset-2 link-offset-3-hover link-underline link-underline-opacity-0 link-underline-opacity-75-hover" href="<?= site_url('signup'); ?>"><?= translate('create an account'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="whatsapp-plugin"></div>

<script src="https://accounts.google.com/gsi/client" async defer></script>

<div id="g_id_onload" data-client_id="171600430722-al53sbabidmetrr45v7t6l9ushl6fveb.apps.googleusercontent.com" data-callback="handleCredentialResponse" data-auto_prompt="true">
</div>

<script type="text/javascript">
    function handleCredentialResponse(response) {
        fetch("<?= site_url('signup/google') ?>", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify({ credential: response.credential })
        })
        .then(res => res.json())
        .then(data => {
            window.location.href = "<?= site_url('signup/google') ?>";
        });
    }
    $(document).ready(function() {
        $('#signin-form').on('submit', function(e) {
            e.preventDefault();
    
            var button = $('#signin-button');
            button.prop("disabled", true);
    
            $('.text-danger').addClass('d-none').text('');
            $('.form-control').removeClass('is-invalid');
    
            $.ajax({
                url: '<?= site_url('signin/signinSubmit') ?>',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.redirect;
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