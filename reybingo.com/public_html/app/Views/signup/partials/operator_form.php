<?php
$formId = $formId ?? 'operator-signup-form';
$fieldPrefix = $fieldPrefix ?? 'operator-signup';
$referrerName = $referrerName ?? '';
$showHeader = ! isset($showHeader) || $showHeader;
$showSigninLink = ! isset($showSigninLink) || $showSigninLink;
$submitLabel = $submitLabel ?? translate('operator enter');
?>
<div class="operator-signup-form-wrap <?= ! empty($embedded) ? 'operator-signup-form-embedded' : ''; ?>">
    <?php if ($showHeader) : ?>
        <div class="text-center operator-signup-form-head">
            <img src="<?= site_url('assets/img/logo.png'); ?>" class="img-fluid logo" alt="img">
            <h5 class="mb-2 p-2"><?= translate('create operator account'); ?></h5>
            <span class="badge bg-primary mb-2"><?= translate('operator'); ?></span>
            <?php if ($referrerName !== '') : ?>
                <p class="small text-muted mb-0">
                    <?= translate('operator affiliate signup invited by'); ?>:
                    <strong><?= esc($referrerName); ?></strong>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php echo form_open(site_url('signup/operatorSignupSubmit'), ['id' => $formId]); ?>
        <?= csrf_field() ?>

        <div class="row operator-signup-form-grid g-2 g-md-3">
            <div class="col-12 col-md-6 operator-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-firstname" class="form-label"><?= translate('first name'); ?></label>
                <input
                    type="text"
                    class="form-control form-control-lg form-bingo"
                    name="firstname"
                    id="<?= esc($fieldPrefix); ?>-firstname"
                    placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('first name')); ?>"
                    <?= empty($embedded) ? 'autofocus' : ''; ?>
                    autocomplete="given-name"
                >
                <small id="<?= esc($fieldPrefix); ?>-firstname-error" class="text-danger d-none"></small>
            </div>

            <div class="col-12 col-md-6 operator-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-lastname" class="form-label"><?= translate('last name'); ?></label>
                <input
                    type="text"
                    class="form-control form-control-lg form-bingo"
                    name="lastname"
                    id="<?= esc($fieldPrefix); ?>-lastname"
                    placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('last name')); ?>"
                    autocomplete="family-name"
                >
                <small id="<?= esc($fieldPrefix); ?>-lastname-error" class="text-danger d-none"></small>
            </div>

            <div class="col-12 col-md-6 operator-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-document" class="form-label"><?= translate('document'); ?></label>
                <input
                    type="text"
                    class="form-control form-control-lg form-bingo"
                    name="document"
                    id="<?= esc($fieldPrefix); ?>-document"
                    placeholder="<?= translate('document'); ?>"
                    autocomplete="off"
                    inputmode="numeric"
                >
                <small id="<?= esc($fieldPrefix); ?>-document-error" class="text-danger d-none"></small>
            </div>

            <div class="col-12 col-md-6 operator-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-email" class="form-label"><?= translate('email'); ?></label>
                <input
                    type="email"
                    class="form-control form-control-lg form-bingo"
                    name="email"
                    id="<?= esc($fieldPrefix); ?>-email"
                    placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('email')); ?>"
                    autocomplete="email"
                >
                <small id="<?= esc($fieldPrefix); ?>-email-error" class="text-danger d-none"></small>
            </div>

            <div class="col-12 col-md-6 operator-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-phone" class="form-label"><?= translate('phone'); ?></label>
                <input
                    type="text"
                    class="form-control form-control-lg form-bingo"
                    name="phone"
                    id="<?= esc($fieldPrefix); ?>-phone"
                    placeholder="<?= translate('enter a'); ?> <?= strtolower(translate('phone')); ?>"
                    autocomplete="tel"
                >
                <small id="<?= esc($fieldPrefix); ?>-phone-error" class="text-danger d-none"></small>
            </div>

            <div class="col-12 operator-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-address_line" class="form-label"><?= translate('address'); ?></label>
                <input
                    type="text"
                    class="form-control form-control-lg form-bingo"
                    name="address_line"
                    id="<?= esc($fieldPrefix); ?>-address_line"
                    placeholder="<?= translate('address'); ?>"
                    autocomplete="street-address"
                >
                <small id="<?= esc($fieldPrefix); ?>-address_line-error" class="text-danger d-none"></small>
            </div>

            <div class="col-12 col-md-6 operator-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-password" class="form-label"><?= translate('password'); ?></label>
                <input
                    type="password"
                    class="form-control form-control-lg form-bingo"
                    name="password"
                    id="<?= esc($fieldPrefix); ?>-password"
                    placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('password')); ?>"
                    autocomplete="new-password"
                >
                <small id="<?= esc($fieldPrefix); ?>-password-error" class="text-danger d-none"></small>
            </div>

            <div class="col-12 col-md-6 operator-signup-form-field">
                <label for="<?= esc($fieldPrefix); ?>-password_confirm" class="form-label"><?= translate('password confirm'); ?></label>
                <input
                    type="password"
                    class="form-control form-control-lg form-bingo"
                    name="password_confirm"
                    id="<?= esc($fieldPrefix); ?>-password_confirm"
                    placeholder="<?= translate('enter an'); ?> <?= strtolower(translate('password')); ?>"
                    autocomplete="new-password"
                >
                <small id="<?= esc($fieldPrefix); ?>-password_confirm-error" class="text-danger d-none"></small>
            </div>

            <div class="col-12 operator-signup-form-actions">
                <button type="submit" class="btn btn-primary btn-bingo mt-2" id="<?= esc($fieldPrefix); ?>-submit">
                    <?= esc($submitLabel); ?>
                </button>
            </div>
        </div>
    <?php echo form_close(); ?>

    <?php if ($showSigninLink && empty($embedded)) : ?>
        <div class="text-center mt-3">
            <a href="<?= site_url('signin'); ?>" class="text-white"><?= translate('already have an account'); ?> <?= translate('sign in'); ?></a>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
    $(function() {
        $('#<?= esc($formId, 'js'); ?>').on('submit', function(e) {
            e.preventDefault();

            const $form = $('#<?= esc($formId, 'js'); ?>');
            const $button = $('#<?= esc($fieldPrefix, 'js'); ?>-submit');
            const fieldPrefix = '<?= esc($fieldPrefix, 'js'); ?>';

            $button.prop('disabled', true);
            $form.find('.text-danger').addClass('d-none').text('');

            $.ajax({
                url: '<?= site_url('signup/operatorSignupSubmit') ?>',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.redirect) {
                        window.location.href = response.redirect;
                        return;
                    }

                    if (response.errors) {
                        $.each(response.errors, function(field, message) {
                            $('#' + fieldPrefix + '-' + field + '-error').text(message).removeClass('d-none');
                        });
                    } else if (response.error) {
                        alert(response.error);
                    }
                },
                error: function() {
                    alert('<?= esc(translate('there was an error in the request to the server.'), 'js'); ?>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });
    });
</script>
