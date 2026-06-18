
<a class="btn btn-small btn-home" href="<?= site_url('play'); ?>"><i class="fa-duotone fa-solid fa-house"></i></a>

<button type="button" class="btn btn-small btn-wallet" onclick="paymentsGet();">
    <i class="fa-duotone fa-solid fa-wallet"></i>
</button>

<button type="button" class="btn btn-small btn-gamepad" onclick="gamesGet();">
    <i class="fa-duotone fa-solid fa-gamepad"></i>
</button>

<button class="btn btn-small btn-volume hidden" onclick="RemoveVolume();">
    <?php if ($user['sounds'] == 1): ?>
        <i class="fa-duotone fa-solid fa-volume"></i>
    <?php else : ?>
        <i class="fa-duotone fa-solid fa-volume-slash"></i>
    <?php endif; ?>
</button>

<a class="btn btn-small btn-lock hidden" href="<?= site_url('password'); ?>"><i class="fa-duotone fa-solid fa-lock"></i></a>

<button class="btn btn-small btn-sliders" onclick="ViewSliders();"><i class="fa-duotone fa-solid fa-sliders-simple"></i></button>

<a class="btn btn-small btn-logout" href="<?= site_url('logout'); ?>"><i class="fa-duotone fa-solid fa-arrow-right-from-arc"></i></a>

<?php if (session()->get('group') == 1) : ?>
    <button class="btn btn-small btn-gear" onclick="settingsGet();"><i class="fa-duotone fa-solid fa-gear"></i></button>
    <a class="btn btn-small btn-kyc-admin" href="<?= site_url('kycAdmin'); ?>"><i class="fa-duotone fa-solid fa-user-check"></i></a>
<?php endif; ?>

<div class="container admin-profile-scroll">
    <div class="row d-flex justify-content-center">
        <div class="col-md-5 col-xl-5">
            <div class="row">
                <div class="col">

                    <?php echo form_open(site_url() . 'users/profileSubmit', array('enctype' => 'multipart/form-data', 'id' => 'profile-form'));?>
                    
                        <?= csrf_field() ?>
                        
                        <div class="row" id="profile-step-1">
                            <div class="col-md-3 my-auto text-center">
                                <div class="col-md-12">
                                    <div class="profile-picture">
                                        <img id="profileImage" src="<?= $imagePath ?>" alt="img">
                                        <label for="fileInput" class="edit-button"><i class="fa-duotone fa-solid fa-camera"></i></label>
                                        <input type="file" id="fileInput" accept="image/*" style="display: none;" onchange="previewImage(event)">
                                        <input type="hidden" id="profile_image_input" name="image">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-9">
                                <div class="col-md-12 mb-1">
                                    <label for="firstname" class="form-label"><?= translate('first name'); ?></label>
                                    <input type="text" class="form-control form-control-lg form-bingo" name="firstname" id="firstname" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('first name')); ?>" autofocus autocomplete="off" value="<?= $user['firstname']; ?>">
                                    <small id="firstname-error" class="text-danger d-none"></small>
                                </div>

                                <div class="col-md-12 mb-1">
                                    <label for="lastname" class="form-label"><?= translate('last name'); ?></label>
                                    <input type="text" class="form-control form-control-lg form-bingo" name="lastname" id="lastname" placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('last name')); ?>" autocomplete="off" value="<?= $user['lastname']; ?>">
                                    <small id="lastname-error" class="text-danger d-none"></small>
                                </div>

                                <div class="col-md-12 mb-1">
                                    <label for="document" class="form-label"><?= translate('document'); ?></label>
                                    <input type="number" class="form-control form-control-lg form-bingo" name="document" id="document" placeholder="<?= translate('document'); ?>" autocomplete="off" value="<?= $user['document']; ?>">
                                    <small id="document-error" class="text-danger d-none"></small>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <button type="button" class="btn btn-small btn-primary d-block w-50 btn-bingo mt-3" id="profile-step-button"><?= translate('continue'); ?></button>
                            </div>
                        </div>

                        <div class="row" id="profile-step-2" style="display: none;">
                            <div class="col-md-12 mb-1">
                                <label for="username" class="form-label"><?= translate('username'); ?></label>
                                <input type="text" class="form-control form-control-lg form-bingo" name="username" id="username" placeholder="<?= translate('username'); ?>" autocomplete="off" value="<?= $user['username']; ?>">
                                <small id="username-error" class="text-danger d-none"></small>
                            </div>

                            <div class="col-md-12 mb-1">
                                <label for="phone" class="form-label"><?= translate('phone'); ?></label>
                                <input type="number" class="form-control form-control-lg form-bingo" name="phone" id="phone" placeholder="<?= translate('phone'); ?>" autocomplete="off" value="<?= $user['phone']; ?>">
                                <small id="phone-error" class="text-danger d-none"></small>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="email" class="form-label"><?= translate('email'); ?></label>
                                <input type="text" class="form-control form-control-lg form-bingo" name="email" id="email" placeholder="<?= translate('email'); ?>" autocomplete="off" value="<?= $user['email']; ?>">
                                <small id="email-error" class="text-danger d-none"></small>
                            </div>

                            <div class="col-md-12 mb-1">
                                <label for="address_line" class="form-label">Dirección</label>
                                <input type="text" class="form-control form-control-lg form-bingo" name="address_line" id="address_line" placeholder="Calle, avenida, edificio..." autocomplete="off" value="<?= esc($user['address_line'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-1">
                                <label for="city" class="form-label">Ciudad</label>
                                <input type="text" class="form-control form-control-lg form-bingo" name="city" id="city" placeholder="Ciudad" autocomplete="off" value="<?= esc($user['city'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="state" class="form-label">Estado / Provincia</label>
                                <input type="text" class="form-control form-control-lg form-bingo" name="state" id="state" placeholder="Estado" autocomplete="off" value="<?= esc($user['state'] ?? ''); ?>">
                            </div>

                            <div class="col-md-12">
                                <button type="submit" class="btn btn-small btn-primary d-block w-50 btn-bingo" id="profile-button"><?= translate('update'); ?></button>
                            </div>
                        </div>
                    <?= form_close(); ?>

                    <hr />

                    <div class="text-center">
                        <?= translate('do you want to update your password?'); ?> <br /> <a class="link-offset-2 link-offset-3-hover link-underline link-underline-opacity-0 link-underline-opacity-75-hover" href="<?= site_url('password'); ?>"><?= translate('change here'); ?></a>
                    </div>

                    <?= view('users/kyc_content', ['user' => $user]); ?>
                </div>
            </div>
        </div>
    </div>
</div>
    
<script type="text/javascript">
    /*$(document).ready(function() {
        $('#profile-step-button').on('click', function() {

            var button = $('#profile-step-button');
            button.prop("disabled", true); 
    
            $('.text-danger').addClass('d-none').text('');
            $('.form-control').removeClass('is-invalid');

            var formData = {
                firstname: $('#firstname').val(),
                lastname: $('#lastname').val(),
                document: $('#document').val()
            };

            $.ajax({
                url: '<?= site_url('users/profileStepSubmit') ?>',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#profile-step-1').hide();
                        $('#profile-step-2').show();
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

        $('#profile-form').on('submit', function(e) {
            e.preventDefault();
    
            var button = $('#profile-button');
            button.prop("disabled", true);
    
            $('.text-danger').addClass('d-none').text('');
            $('.form-control').removeClass('is-invalid');

            var formData = new FormData(this);
            formData.append('profile_image', $('#fileInput')[0].files[0]);

            $.ajax({
                url: '<?= site_url('users/profileSubmit') ?>',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Toastify({
                            text: "<?= translate('profile'); ?> <?= translate('updated successfully'); ?>",
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

    function previewImage(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const output = document.getElementById('profileImage');
            output.src = reader.result;

            document.getElementById('profile_image_input').value = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }*/
</script>