<div class="container">
    <div class="row d-flex justify-content-center">
        <div class="col-md-6 col-xl-6">
            <div class="row">
                <div class="col">
                    <div class="text-center">
                        <img src="<?= site_url('assets/img/logo.png'); ?>" class="img-fluid logo" alt="img">
                        <h5 class="mb-0 p-2"><?= translate('create account'); ?></h5>
                        <p class="small text-muted mb-0"><?= translate('official client access'); ?>: <strong><?= esc(bingo_official_login_host()); ?></strong></p>
                        <?php if (bingo_client_domain_enabled()): ?>
                        <p class="small text-muted mb-0"><?= translate('client login url'); ?>: <strong><?= esc(bingo_client_login_url('/signup')); ?></strong></p>
                        <?php endif; ?>
                    </div>
                
                    <?php echo form_open(site_url() . 'signup/signupSubmit', array('enctype' => 'multipart/form-data', 'id' => 'signup-form'));?>
                    
                        <?= csrf_field() ?>
                        
                        <div class="row" id="signup-step-1">
                            <div class="col-md-6 mb-1">
                                <label for="firstname" class="form-label"><?= translate('first name'); ?></label>
                                <input type="text" class="form-control form-control-lg form-bingo" name="firstname" id="firstname" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('first name')); ?>" autofocus autocomplete="off">
                                <small id="firstname-error" class="text-danger d-none"></small>
                            </div>

                            <div class="col-md-6 mb-1">
                                <label for="lastname" class="form-label"><?= translate('last name'); ?></label>
                                <input type="text" class="form-control form-control-lg form-bingo" name="lastname" id="lastname" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('last name')); ?>" autocomplete="off">
                                <small id="lastname-error" class="text-danger d-none"></small>
                            </div>

                            <div class="col-md-6 mb-1">
                                <label for="document" class="form-label"><?= translate('document'); ?></label>
                                <input type="number" class="form-control form-control-lg form-bingo" name="document" id="document" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('document')); ?>" autocomplete="off">
                                <small id="document-error" class="text-danger d-none"></small>
                            </div>

                            <div class="col-md-6 mb-1">
                                <label for="phone" class="form-label"><?= translate('phone'); ?></label>
                                <input type="number" class="form-control form-control-lg form-bingo" name="phone" id="phone" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('phone')); ?>" autocomplete="off">
                                <small id="phone-error" class="text-danger d-none"></small>
                            </div>

                            <div class="col-md-12">
                                <button type="button" class="btn btn-primary d-block w-50 btn-bingo mt-3" id="signup-step-button"><?= translate('continue'); ?></button>
                            </div>
                        </div>

                        <div class="row" id="signup-step-2" style="display: none;">
                            <div class="col-md-5 mb-1">
                                <label for="username" class="form-label"><?= translate('username'); ?></label>
                                <div class="form-group">
                                    <input type="text" class="form-control form-control-lg form-bingo" name="username" id="username" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('username')); ?>" autocomplete="off">
                                </div>
                                <small id="username-error" class="text-danger d-none"></small>
                            </div>
                                                        
                            <div class="col-md-7 mb-1">
                                <label for="email" class="form-label"><?= translate('email'); ?></label>
                                <input type="text" class="form-control form-control-lg form-bingo" name="email" id="email" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('email')); ?>" autocomplete="off">
                                <small id="email-error" class="text-danger d-none"></small>
                            </div>
                            
                            <div class="col-md-6 mb-1">
                                <label for="password" class="form-label"><?= translate('password'); ?></label>
                                <input type="password" class="form-control form-control-lg form-bingo" name="password" id="password" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('password')); ?>" autocomplete="off">
                                <small id="password-error" class="text-danger d-none"></small>
                            </div>
                            
                            <div class="col-md-6 mb-1">
                                <label for="password_confirm" class="form-label"><?= translate('password confirm'); ?></label>
                                <input type="password" class="form-control form-control-lg form-bingo" name="password_confirm" id="password_confirm" placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('password')); ?>" autocomplete="off">
                                <small id="password_confirm-error" class="text-danger d-none"></small>
                            </div>

                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary d-block w-50 btn-bingo mt-3" id="signup-button"><?= translate('create'); ?></button>
                            </div>
                        </div>

                        <div class="col-md-12 pt-3">
                            <a href="<?= site_url('signup/google') ?>" class="btn btn-primary d-block google"><img src="https://developers.google.com/identity/images/g-logo.png" style="width:20px; margin-right:10px;"> <?= translate('signup with google'); ?></a>
                        </div>
                    <?= form_close(); ?>
                        
                    <hr />

                    <div class="text-center">
                        <?= translate('do you already have an account?'); ?> <br /> <a class="link-offset-2 link-offset-3-hover link-underline link-underline-opacity-0 link-underline-opacity-75-hover" href="<?= site_url('signin'); ?>"><?= translate('enter'); ?></a>
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
        $('#signup-step-button').on('click', function() {

            var button = $('#signup-step-button');
            button.prop("disabled", true); 
    
            $('.text-danger').addClass('d-none').text('');
            $('.form-control').removeClass('is-invalid');

            var formData = {
                firstname: $('#firstname').val(),
                lastname: $('#lastname').val(),
                document: $('#document').val(),
                phone: $('#phone').val()
            };

            $.ajax({
                url: '<?= site_url('signup/signupStepSubmit') ?>',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#signup-step-1').hide();
                        $('#signup-step-2').show();
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

        $('#signup-form').on('submit', function(e) {
            e.preventDefault(); 
    
            var button = $('#signup-button');
            button.prop("disabled", true); 
    
            $('.text-danger').addClass('d-none').text('');
            $('.form-control').removeClass('is-invalid');
    
            $.ajax({
                url: '<?= site_url('signup/signupSubmit') ?>',
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