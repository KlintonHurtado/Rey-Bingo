<style>
.btn-register-green {
    background: #28a745 !important;
    border-color: #28a745 !important;
    color: #ffffff !important;
}
.btn-register-green:hover, .btn-register-green:focus, .btn-register-green:active {
    background: #218838 !important;
    border-color: #1e7e34 !important;
    color: #ffffff !important;
}
#signin-button {
    background: #1a62e7 !important;
    border-color: #1a62e7 !important;
    color: #ffffff !important;
}
#signin-button:hover, #signin-button:focus, #signin-button:active {
    background: #144eb9 !important;
    border-color: #144eb9 !important;
    color: #ffffff !important;
}
.btn-register-green, #signin-button {
    font-family: 'Fredoka One', cursive !important;
}
#username, #password {
    border: 2px solid #1a62e7 !important;
    height: 46px !important;
    font-size: 0.95rem !important;
    border-radius: 30px !important;
    padding: 8px 20px !important;
}
#username:focus, #password:focus {
    border-color: #1a62e7 !important;
    box-shadow: 0 0 0 0.2rem rgba(26, 98, 231, 0.25) !important;
}
</style>

<div class="container">
    <div class="row d-flex justify-content-center">
        <div class="col-md-5 col-xl-5">
            <div class="row">
                <div class="col">
                    <div class="text-center mb-3">
                        <img src="<?= site_url('assets/img/logo_principal.png'); ?>?v=2" class="img-fluid logo" alt="img">
                        <?php if (($registeredType ?? '') === 'store' || service('request')->getGet('registered') === 'store') : ?>
                            <div class="alert alert-success py-2 px-3 small mb-2"><?= translate('store account created sign in'); ?></div>
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
                                <a class="link-offset-2 link-offset-3-hover link-underline link-underline-opacity-0 link-underline-opacity-75-hover" style="float: right !important;" href="<?= site_url('restore'); ?>"><?= translate('forgot your password?'); ?></a>
                            </div>

                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary d-block w-50 btn-bingo" id="signin-button"><?= translate('enter'); ?></button>
                            </div>

                            <div class="col-md-12 pt-3 text-center">
                                <span class="text-white font-weight-bold" style="color: #ffffff;"><?= translate('dont have an account yet?'); ?></span>
                                <a href="<?= site_url('signup'); ?>" class="btn btn-register-green d-block w-50 btn-bingo mx-auto mt-2">Registrarse aquí</a>
                            </div>

                            <div class="col-md-12 pt-3">
                                <a href="<?= site_url('signup/google') ?>" class="btn btn-primary d-block google"><img src="https://developers.google.com/identity/images/g-logo.png" style="width:20px; margin-right:10px;"> <?= translate('signin with google'); ?></a>
                            </div>
                        </div>
                    <?= form_close(); ?>
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