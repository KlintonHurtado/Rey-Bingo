
<a class="btn btn-small btn-home" href="<?= site_url('play'); ?>"><i class="fa-duotone fa-solid fa-house"></i></a>

<button type="button" class="btn btn-small btn-wallet" onclick="paymentsGet();">
    <i class="fa-duotone fa-solid fa-wallet"></i>
</button>

<button type="button" class="btn btn-small btn-gamepad" onclick="gamesGet();">
    <i class="fa-duotone fa-solid fa-gamepad"></i>
</button>

<?php if ((int) (session()->get('group') ?? 0) !== 1): ?>
<button class="btn btn-small btn-volume hidden" onclick="RemoveVolume();">
    <?php if ($user['sounds'] == 1): ?>
        <i class="fa-duotone fa-solid fa-volume"></i>
    <?php else : ?>
        <i class="fa-duotone fa-solid fa-volume-slash"></i>
    <?php endif; ?>
</button>
<?php endif; ?>

<a class="btn btn-small btn-user hidden" href="<?= site_url('profile'); ?>"><i class="fa-duotone fa-solid fa-user-hair"></i></a>

<button class="btn btn-small btn-sliders" onclick="ViewSliders();"><i class="fa-duotone fa-solid fa-sliders-simple"></i></button>

<a class="btn btn-small btn-logout" href="<?= site_url('logout'); ?>"><i class="fa-duotone fa-solid fa-arrow-right-from-arc"></i></a>

<?php if (function_exists('bingo_can') && bingo_can('settings.manage')) : ?>
    <button class="btn btn-small btn-gear" onclick="settingsGet();"><i class="fa-duotone fa-solid fa-gear"></i></button>
<?php endif; ?>

<div class="container">
    <div class="row d-flex justify-content-center">
        <div class="col-md-5 col-xl-5">
            <div class="row">
                <div class="col">
      
                    <?php echo form_open(site_url() . 'users/passwordSubmit', array('enctype' => 'multipart/form-data', 'id' => 'password-form'));?>
                    
                        <?= csrf_field() ?>
                        
                        <div class="row">
                            <div class="col-md-12 mb-1">
                                <label for="username" class="form-label"><?= translate('current password'); ?></label>
                                <input type="password" class="form-control form-control-lg form-bingo" name="password_current" id="password_current" placeholder="<?= translate('current password'); ?>" autocomplete="off">
                                <small id="password_current-error" class="text-danger d-none"></small>
                            </div>

                            <div class="col-md-12 mb-1">
                                <label for="username" class="form-label"><?= translate('password'); ?></label>
                                <input type="password" class="form-control form-control-lg form-bingo" name="password" id="password" placeholder="<?= translate('password'); ?>" autocomplete="off">
                                <small id="password-error" class="text-danger d-none"></small>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="username" class="form-label"><?= translate('confirm password'); ?></label>
                                <input type="password" class="form-control form-control-lg form-bingo" name="password_confirm" id="password_confirm" placeholder="<?= translate('confirm password'); ?>" autocomplete="off">
                                <small id="password_confirm-error" class="text-danger d-none"></small>
                            </div>

                            <div class="col-md-12">
                                <button type="submit" class="btn btn-small btn-primary d-block w-50 btn-bingo" id="password-button"><?= translate('update'); ?></button>
                            </div>
                        </div>
                    <?= form_close(); ?>

                    <hr />

                    <div class="text-center">
                        <?= translate('dont want to change your password?'); ?> <br /> <a class="link-offset-2 link-offset-3-hover link-underline link-underline-opacity-0 link-underline-opacity-75-hover" href="<?= site_url('play'); ?>"><?= translate('go back'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    
<script type="text/javascript">
    $(document).ready(function() {
        $('#password-form').on('submit', function(e) {
            e.preventDefault();
    
            var button = $('#password-button');
            button.prop("disabled", true);
    
            $('.text-danger').addClass('d-none').text('');
            $('.form-control').removeClass('is-invalid');

            $.ajax({
                url: '<?= site_url('users/passwordSubmit') ?>',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const password_current = document.getElementById("password_current");
                        const password = document.getElementById("password");
                        const password_confirm = document.getElementById("password_confirm");

                        password_current.value = "";
                        password.value = "";
                        password_confirm.value = "";

                        Toastify({
                            text: "<?= translate('password updated successfully'); ?>",
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            style: { background: "#198754" },
                            stopOnFocus: true
                        }).showToast();
                    } else {
                        if (response.error) {
                            Toastify({
                                text: response.error,
                                duration: 3000,
                                gravity: "top",
                                position: "right",
                                style: { background: "#dc3545" },
                                stopOnFocus: true
                            }).showToast();
                        }
                        
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